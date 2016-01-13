<?php
	
	class Batchbook {
		
		protected $api_url = 'https://app.icontact.com/icp/a/';

		public function __construct( $account_url, $api_token = null ) {
			
			$this->account_url = $account_url;
			$this->api_token   = $api_token;
			
		}

		/**
		 * Make API request.
		 * 
		 * @access public
		 * @param string $action
		 * @param array $options (default: array())
		 * @param string $method (default: 'GET')
		 * @return void
		 */
		public function make_request( $action = null, $options = array(), $method = 'GET', $expected_code = 200, $return_key = null ) {
			
			/* Build request options string. */
			$request_options  = 'auth_token=' . $this->api_token;
			$request_options .= ( $method == 'GET' && ! empty( $options ) ) ? '&' . http_build_query( $options ) : '';
			
			/* Build request URL. */
			$request_url = 'https://' . $this->account_url . '.batchbook.com/api/v1/' . $action . '.json?' . $request_options;
			
			/* Prepare request and execute. */
			$args = array( 
				'method'  => $method,
				'headers' => array(
					'Content-Type' => 'application/json'
				)
			);
			
			if ( $method == 'POST' || $method == 'PUT' ) {
				
				$args['body'] = json_encode( $options );
				
			}

			$response = wp_remote_request( $request_url, $args );
						
			/* If WP_Error, die. Otherwise, return decoded JSON. */
			if ( is_wp_error( $response ) ) {
				
				die( 'Request failed. '. $response->get_error_messages() );
				
			} else if ( strpos( $response['headers']['content-type'], 'application/json' ) === FALSE ) {
				
				throw new Exception( 'Invalid account URL.' );

			} else {
				
				$response_body = json_decode( $response['body'], true );

				if ( isset( $response_body['error'] ) ) {
					
					throw new Exception( $response_body['error'] );
					
				}

				if ( isset( $response_body['code'] ) && $response_body['code'] !== $expected_code ) {
					
					throw new Exception( $response_body['message'] );
					
				}
				
				if ( ! rgblank( $response_body ) ) {
					
					return ( empty( $return_key ) || ( ! empty( $return_key ) && ! isset( $response_body[$return_key] ) ) ) ? $response_body : $response_body[$return_key];	
					
				}
				
				/* If the body is empty, retrieve the ID from the location header. */
				$id = explode( '/', $response['headers']['location'] );
				return end( $id );
				
			}
			
		}
		
		/**
		 * Create new company.
		 * 
		 * @access public
		 * @param array $company
		 * @return array $company
		 */
		function create_company( $company ) {
			
			/* Prepare company object for creation. */
			$company = array( 'company' => $company );
			
			/* Create person. */
			return $this->make_request( 'companies', $company, 'POST', 201, 'company' );

		}

		/**
		 * Create new person.
		 * 
		 * @access public
		 * @param array $person
		 * @return array $person
		 */
		function create_person( $person ) {
			
			/* Prepare person object for creation. */
			$person = array( 'person' => $person );
			
			/* Create person. */
			return $this->make_request( 'people', $person, 'POST', 201, 'person' );

		}

		/**
		 * Search companies by name.
		 * 
		 * @access public
		 * @param mixed $company_name
		 * @return array
		 */
		function get_companies_by_name( $company_name ) {
			
			return $this->make_request( 'companies', array( 'name' => $company_name ), 'GET', 200, 'companies' );
			
		}
		
		/**
		 * Get custom field sets.
		 * 
		 * @access public
		 * @return array $custom_field_sets
		 */
		function get_custom_field_sets() {
			
			return $this->make_request( 'custom_field_sets', array(), 'GET', 200, 'custom_field_sets' );
			
		}

		/**
		 * Search people by email.
		 * 
		 * @access public
		 * @param mixed $company_name
		 * @return array
		 */
		function get_people_by_email( $email_address ) {
			
			return $this->make_request( 'people', array( 'exact_email' => $email_address ), 'GET', 200, 'people' );
			
		}

		/**
		 * Get users.
		 * 
		 * @access public
		 * @return array $users
		 */
		function get_users() {
			
			return $this->make_request( 'users' );
			
		}
		
		/**
		 * Update person.
		 * 
		 * @access public
		 * @param int $person_id
		 * @param array $person
		 * @return array $person
		 */
		function update_person( $person_id, $person ) {
			
			/* Prepare person object for creation. */
			$person = array( 'person' => $person );
			
			/* Create person. */
			return $this->make_request( 'people/' . $person_id, $person, 'PUT', 200, 'person' );

		}

		/**
		 * Check if account URL is valid.
		 * 
		 * @access public
		 * @return boolean
		 */
		function validate_account_url() {
			
			/* Execute request. */
			$response = wp_remote_request( 'https://' . $this->account_url . '.batchbook.com/api/v1/users.json?auth_token=' . $this->api_token );

			if ( strpos( $response['headers']['content-type'], 'application/json' ) === FALSE ) {
				
				throw new Exception( 'Invalid account URL.' );
				
			}
			
			return true;
			
		}
		
	}