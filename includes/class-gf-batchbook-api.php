<?php

class GF_Batchbook_API {

	/**
	 * Batchbook account URL.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $account_url Batchbook account URL.
	 */
	protected $account_url;

	/**
	 * Batchbook API token.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $api_token Batchbook API token.
	 */
	protected $api_token;

	/**
	 * Initialize Batchbook API library.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $account_url Batchbook account URL.
	 * @param string $api_key     Batchbook API key.
	 */
	public function __construct( $account_url, $api_token = null ) {

		$this->account_url = $account_url;
		$this->api_token   = $api_token;

	}

	/**
	 * Create a new company.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $company Company object.
	 *
	 * @uses GF_Batchbook_API::make_request()
	 *
	 * @return array
	 */
	public function create_company( $company ) {

		// Prepare company object for creation.
		$company = array( 'company' => $company );

		return $this->make_request( 'companies', $company, 'POST', 201, 'company' );

	}

	/**
	 * Create a new person.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person Person object.
	 *
	 * @uses GF_Batchbook_API::make_request()
	 *
	 * @return array
	 */
	public function create_person( $person ) {

		// Prepare person object for creation.
		$person = array( 'person' => $person );

		return $this->make_request( 'people', $person, 'POST', 201, 'person' );

	}

	/**
	 * Search companies by name.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $company_name Company name.
	 *
	 * @uses GF_Batchbook_API::make_request()
	 *
	 * @return array
	 */
	public function get_companies_by_name( $company_name ) {

		return $this->make_request( 'companies', array( 'name' => $company_name ), 'GET', 200, 'companies' );

	}

	/**
	 * Get custom field sets.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GF_Batchbook_API::make_request()
	 *
	 * @return array
	 */
	public function get_custom_field_sets() {

		return $this->make_request( 'custom_field_sets', array(), 'GET', 200, 'custom_field_sets' );

	}

	/**
	 * Search people by email.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $email_address Email address to search.
	 *
	 * @uses GF_Batchbook_API::make_request()
	 *
	 * @return array
	 */
	public function get_people_by_email( $email_address ) {

		return $this->make_request( 'people', array( 'exact_email' => $email_address ), 'GET', 200, 'people' );

	}

	/**
	 * Get users.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GF_Batchbook_API::make_request()
	 *
	 * @return array
	 */
	public function get_users() {

		return $this->make_request( 'users' );

	}

	/**
	 * Update person.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param int   $person_id Person ID.
	 * @param array $person    Person object.
	 *
	 * @uses GF_Batchbook_API::make_request()
	 *
	 * @return array
	 */
	public function update_person( $person_id, $person ) {

		// Prepare person object for update.
		$person = array( 'person' => $person );

		return $this->make_request( 'people/' . $person_id, $person, 'PUT', 200, 'person' );

	}

	/**
	 * Check if account URL is valid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function validate_account_url() {

		// Execute request.
		$response = wp_remote_request( 'https://' . $this->account_url . '.batchbook.com/api/v1/users.json?auth_token=' . $this->api_token );

		if ( is_wp_error( $response ) || strpos( $response['headers']['content-type'], 'application/json' ) === FALSE ) {
			throw new Exception( 'Invalid account URL.' );
		}

		return true;

	}

	/**
	 * Make API request.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @param string $action        Request action.
	 * @param array  $options       Request options.
	 * @param string $method        HTTP method. Defaults to GET.
	 * @param int    $expected_code Expected HTTP response code. Defaults to 200.
	 * @param string $return_key    Array key from response to return. Defaults to null (return full response).
	 *
	 * @return array|string
	 */
	private function make_request( $action = null, $options = array(), $method = 'GET', $expected_code = 200, $return_key = null ) {

		// Build request options string.
		$request_options  = 'auth_token=' . $this->api_token;
		$request_options .= ( $method == 'GET' && ! empty( $options ) ) ? '&' . http_build_query( $options ) : '';

		// Build request URL.
		$request_url = 'https://' . $this->account_url . '.batchbook.com/api/v1/' . $action . '.json?' . $request_options;

		// Prepare request.
		$args = array(
			'method'  => $method,
			'headers' => array( 'Content-Type' => 'application/json' ),
		);

		// If this is a PUT or POST request, add JSON payload.
		if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
			$args['body'] = json_encode( $options );
		}

		// Execute API request.
		$response = wp_remote_request( $request_url, $args );

		// If API request returns a WordPress error, throw an exception.
		if ( is_wp_error( $response ) ) {
			throw new Exception( 'Request failed. '. $response->get_error_message() );
		}

		// If account URL is invalid, throw an exception.
		if ( strpos( $response['headers']['content-type'], 'application/json' ) === FALSE ) {
			throw new Exception( 'Invalid account URL.' );
		}

		// Convert JSON response to array.
		$response_body = json_decode( $response['body'], true );

		// If there is an error in the result, throw an exception.
		if ( isset( $response_body['error'] ) ) {
			throw new Exception( $response_body['error'] );
		}

		// If expected response code was not returned, throw an exception.
		if ( isset( $response_body['code'] ) && $response_body['code'] !== $expected_code ) {
			throw new Exception( $response_body['message'] );
		}

		// If response body is empty, return created object ID.
		if ( empty( $response_body ) ) {

			// Explode location header.
			$id = explode( '/', $response['headers']['location'] );

			return end( $id );

		}

		return empty( $return_key ) || ( ! empty( $return_key ) && ! isset( $response_body[ $return_key ] ) ) ? $response_body : $response_body[ $return_key ];

	}

}