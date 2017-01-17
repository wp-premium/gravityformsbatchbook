<?php
	
GFForms::include_feed_addon_framework();

class GFBatchbook extends GFFeedAddOn {
	
	protected $_version = GF_BATCHBOOK_VERSION;
	protected $_min_gravityforms_version = '1.9.14.26';
	protected $_slug = 'gravityformsbatchbook';
	protected $_path = 'gravityformsbatchbook/batchbook.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Batchbook Add-On';
	protected $_short_title = 'Batchbook';
	protected $_enable_rg_autoupgrade = true;
	protected $api = null;
	private static $_instance = null;

	/* Permissions */
	protected $_capabilities_settings_page = 'gravityforms_batchbook';
	protected $_capabilities_form_settings = 'gravityforms_batchbook';
	protected $_capabilities_uninstall = 'gravityforms_batchbook_uninstall';

	/* Members plugin integration */
	protected $_capabilities = array( 'gravityforms_batchbook', 'gravityforms_batchbook_uninstall' );

	/**
	 * Get instance of this class.
	 * 
	 * @access public
	 * @static
	 * @return $_instance
	 */	
	public static function get_instance() {
		
		if ( self::$_instance == null )
			self::$_instance = new self;

		return self::$_instance;
		
	}

	/**
	 * Register needed plugin hooks and PayPal delayed payment support.
	 * 
	 * @access public
	 * @return void
	 */
	public function init() {
		
		parent::init();
		
		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Create person in Batchbook only when payment is received.', 'gravityformsbatchbook' )
			)
		);
		
	}

	/**
	 * Register needed styles.
	 * 
	 * @access public
	 * @return array $styles
	 */
	public function styles() {
		
		$styles = array(
			array(
				'handle'  => 'gform_batchbook_form_settings_css',
				'src'     => $this->get_base_url() . '/css/form_settings.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_settings' ) ),
				)
			)
		);
		
		return array_merge( parent::styles(), $styles );
		
	}

	/**
	 * Setup plugin settings fields.
	 * 
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {
						
		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'account_url',
						'label'             => esc_html__( 'Account URL', 'gravityformsbatchbook' ),
						'type'              => 'text',
						'class'             => 'small',
						'after_input'       => '.batchbook.com',
						'feedback_callback' => array( $this, 'validate_account_url' )
					),
					array(
						'name'              => 'api_token',
						'label'             => esc_html__( 'API Token', 'gravityformsbatchbook' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => esc_html__( 'Batchbook settings have been updated.', 'gravityformsbatchbook' )
						),
					),
				),
			),
		);
		
	}

	/**
	 * Prepare plugin settings description.
	 * 
	 * @access public
	 * @return string $description
	 */
	public function plugin_settings_description() {
		
		$description  = '<p>';
		$description .= sprintf(
			esc_html__( 'Batchbook is a contact management tool that makes it easy to track communications, deals and people. Use Gravity Forms to collect customer information and automatically add it to your Batchbook account. If you don\'t have a Batchbook account, you can %1$s sign up for one here.%2$s', 'gravityformsbatchbook' ),
			'<a href="http://www.batchbook.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';
		
		if ( ! $this->initialize_api() ) {
			
			$description .= '<p>';
			$description .= esc_html__( 'Gravity Forms Batchbook Add-On requires your account URL and API Key, which can be found on your Personal Settings page.', 'gravityformsbatchbook' );
			$description .= '</p>';
			
		}
				
		return $description;
		
	}

	/**
	 * Setup fields for feed settings.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_settings_fields() {
		
		/* Build base fields array. */
		$base_fields = array(
			'title'  => '',
			'fields' => array(
				array(
					'name'           => 'feed_name',
					'label'          => esc_html__( 'Feed Name', 'gravityformsbatchbook' ),
					'type'           => 'text',
					'required'       => true,
					'class'          => 'medium',
					'default_value'  => $this->get_default_feed_name(),
					'tooltip'        => '<h6>'. esc_html__( 'Name', 'gravityformsbatchbook' ) .'</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsbatchbook' )
				),
			)
		);
		
		/* Build person fields array. */
		$person_fields = array(
			'title'  => esc_html__( 'Person Details', 'gravityformsbatchbook' ),
			'fields' => array(
				array(
					'name'           => 'person_standard_fields',
					'label'          => esc_html__( 'Map Fields', 'gravityformsbatchbook' ),
					'type'           => 'field_map',
					'field_map'      => $this->standard_fields_for_feed_mapping(),
					'tooltip'        => '<h6>'. esc_html__( 'Map Fields', 'gravityformsbatchbook' ) .'</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Batchbook fields. Batchbook custom fields must be a text field type to be mappable.', 'gravityformsbatchbook' )
				),
				array(
					'name'           => 'person_custom_fields',
					'label'          => '',
					'type'           => 'dynamic_field_map',
					'field_map'      => $this->custom_fields_for_feed_mapping(),
					'disable_custom' => true
				),
				array(
					'name'           => 'person_tags',
					'label'          => esc_html__( 'Tags', 'gravityformsbatchbook' ),
					'type'           => 'text',
					'class'          => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
				),
				array(
					'name'           => 'person_about',
					'label'          => esc_html__( 'About', 'gravityformsbatchbook' ),
					'type'           => 'textarea',
					'class'          => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
				),		
				array(
					'name'           => 'update_person',
					'label'          => esc_html__( 'Update Person', 'gravityformsbatchbook' ),
					'type'           => 'checkbox_and_select',
					'tooltip'        => '<h6>'. esc_html__( 'Update Person', 'gravityformsbatchbook' ) .'</h6>' . esc_html__( 'If enabled and an existing person is found, their contact details will either be replaced or appended. Job title and company will be replaced whether replace or append is chosen.', 'gravityformsbatchbook' ),
					'checkbox'       => array(
						'name'          => 'person_update_enable',
						'label'         => esc_html__( 'Update Person if already exists', 'gravityformsbatchbook' ),
					),
					'select'         => array(
						'name'          => 'person_update_action',
						'choices'       => array(
							array(
								'label' => esc_html__( 'and replace existing data', 'gravityformsbatchbook' ),
								'value' => 'replace'
							),
							array(
								'label' => esc_html__( 'and append new data', 'gravityformsbatchbook' ),
								'value' => 'append'
							)
						)	
					),
				),
				array(
					'name'           => 'champion',
					'label'          => esc_html__( 'Mark as Champion', 'gravityformsbatchbook' ),
					'type'           => 'checkbox',
					'choices'        => array(
						array(
							'name'  => 'person_mark_as_champion',
							'label' => esc_html__( 'Mark Person as Champion', 'gravityformsbatchbook' ),
						),
					)
				)
			)
		);

		/* Build conditional logic fields array. */
		$conditional_fields = array(
			'title'      => esc_html__( 'Feed Conditional Logic', 'gravityformsbatchbook' ),
			'fields'     => array(
				array(
					'name'           => 'feed_condition',
					'type'           => 'feed_condition',
					'label'          => esc_html__( 'Conditional Logic', 'gravityformsbatchbook' ),
					'checkbox_label' => esc_html__( 'Enable', 'gravityformsbatchbook' ),
					'instructions'   => esc_html__( 'Export to Batchbook if', 'gravityformsbatchbook' ),
					'tooltip'        => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformsbatchbook' ) . '</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Batchbook when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsbatchbook' )
				),
				
			)
		);
		
		return array( $base_fields, $person_fields, $conditional_fields );
		
	}

	/**
	 * Prepare standard fields for feed field mapping.
	 * 
	 * @access public
	 * @return array
	 */
	public function standard_fields_for_feed_mapping() {
		
		return array(
			array(	
				'name'          => 'first_name',
				'label'         => esc_html__( 'First Name', 'gravityformsbatchbook' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name', '3' ),
			),
			array(	
				'name'          => 'last_name',
				'label'         => esc_html__( 'Last Name', 'gravityformsbatchbook' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name', '6' ),
			),
			array(	
				'name'          => 'email_address',
				'label'         => esc_html__( 'Email Address', 'gravityformsbatchbook' ),
				'required'      => true,
				'field_type'    => array( 'email', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'email' ),
			),
		);
		
	}

	/**
	 * Prepare contact and custom fields for feed field mapping.
	 * 
	 * @access public
	 * @return array
	 */
	public function custom_fields_for_feed_mapping() {
		
		$fields = array(
			array(
				'label'   => esc_html__( 'Choose a Field', 'gravityformsbatchbook' ),	
			),
			array(	
				'value'    => 'title',
				'label'    => esc_html__( 'Job Title', 'gravityformsbatchbook' ),
			),
			array(	
				'value'    => 'company',
				'label'    => esc_html__( 'Company Name', 'gravityformsbatchbook' ),
			),
			array(	
				'label'   => esc_html__( 'Email Address', 'gravityformsbatchbook' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Work', 'gravityformsbatchbook' ),
						'value' => 'email_work'	
					),
					array(
						'label' => esc_html__( 'Home', 'gravityformsbatchbook' ),
						'value' => 'email_home'	
					),
					array(
						'label' => esc_html__( 'Other', 'gravityformsbatchbook' ),
						'value' => 'email_other'	
					),
				)
			),
			array(	
				'label'   => esc_html__( 'Phone Number', 'gravityformsbatchbook' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Main', 'gravityformsbatchbook' ),
						'value' => 'phone_main'	
					),
					array(
						'label' => esc_html__( 'Work', 'gravityformsbatchbook' ),
						'value' => 'phone_work'	
					),
					array(
						'label' => esc_html__( 'Mobile', 'gravityformsbatchbook' ),
						'value' => 'phone_mobile'	
					),
					array(
						'label' => esc_html__( 'Home', 'gravityformsbatchbook' ),
						'value' => 'phone_home'	
					),
					array(
						'label' => esc_html__( 'Fax', 'gravityformsbatchbook' ),
						'value' => 'phone_fax'	
					),
					array(
						'label' => esc_html__( 'Other', 'gravityformsbatchbook' ),
						'value' => 'phone_other'	
					),
				)
			),
			array(	
				'label'   => esc_html__( 'Address', 'gravityformsbatchbook' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Main', 'gravityformsbatchbook' ),
						'value' => 'address_main'	
					),
					array(
						'label' => esc_html__( 'Work', 'gravityformsbatchbook' ),
						'value' => 'address_work'	
					),
					array(
						'label' => esc_html__( 'Home', 'gravityformsbatchbook' ),
						'value' => 'address_home'	
					),
					array(
						'label' => esc_html__( 'Billing', 'gravityformsbatchbook' ),
						'value' => 'address_billing'	
					),
					array(
						'label' => esc_html__( 'Shipping', 'gravityformsbatchbook' ),
						'value' => 'address_shipping'	
					),
					array(
						'label' => esc_html__( 'Other', 'gravityformsbatchbook' ),
						'value' => 'address_other'	
					),
				)
			),
			array(	
				'label'   => esc_html__( 'Website', 'gravityformsbatchbook' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Main', 'gravityformsbatchbook' ),
						'value' => 'website_main'	
					),
					array(
						'label' => esc_html__( 'Work', 'gravityformsbatchbook' ),
						'value' => 'website_work'	
					),
					array(
						'label' => esc_html__( 'Home', 'gravityformsbatchbook' ),
						'value' => 'website_home'	
					),
					array(
						'label' => esc_html__( 'Other', 'gravityformsbatchbook' ),
						'value' => 'website_other'	
					),
				)
			),
		);
		
		$custom_fields = $this->get_batchbook_custom_fields();
		
		if ( ! empty( $custom_fields ) ) {
			
			$fields = array_merge( $fields, $custom_fields );

		}
		
		return $fields;
				
	}
	
	/**
	 * Get Batchbook custom fields for feed field mapping.
	 * 
	 * @access public
	 * @return array $custom_fields
	 */
	public function get_batchbook_custom_fields() {
		
		$custom_fields = array();
		
		/* If API instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			
			return $custom_fields;
			
		}
		
		/* Get Batchbook custom field sets */
		$custom_field_sets = $this->api->get_custom_field_sets();
		
		/* Add custom field sets to custom fields array. */
		if ( ! empty( $custom_field_sets ) ) {
			
			foreach ( $custom_field_sets as $custom_field_set ) {
				
				if ( ! empty( $custom_field_set['custom_field_definitions_attributes'] ) ) {
					
					$field_set = array(
						'label'   => $custom_field_set['name'],
						'choices' => array()
					);
					
					foreach ( $custom_field_set['custom_field_definitions_attributes'] as $custom_field ) {
						
						if ( $custom_field['custom_field_type'] == 'CustomField::Text' ) {
						
							$field_set['choices'][] = array(
								'label' => $custom_field['name'],
								'value' => 'custom_field_' . $custom_field_set['id'] . '_' . $custom_field['id']
							);
							
						}
						
					}
					
					if ( ! empty( $field_set['choices'] ) ) {
					
						$custom_fields[] = $field_set;
						
					}
					
				}
				
			}
			
		}
		
		return $custom_fields;
		
	}

	/**
	 * Set feed creation control.
	 * 
	 * @access public
	 * @return bool
	 */
	public function can_create_feed() {
		
		return $this->initialize_api();
		
	}

	/**
	 * Enable feed duplication.
	 *
	 * @access public
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {
		
		return true;
		
	}

	/**
	 * Setup columns for feed list table.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_list_columns() {
		
		return array(
			'feed_name' => esc_html__( 'Name', 'gravityformsbatchbook' ),
			'action'    => esc_html__( 'Action', 'gravityformsbatchbook' )
		);
		
	}

	/**
	 * Get value for action feed list column.
	 * 
	 * @access public
	 * @param array $feed
	 * @return string $action
	 */
	public function get_column_value_action( $feed ) {
		
		return esc_html__( 'Create New Person', 'gravityformsbatchbook' );			
		
	}

	/**
	 * Process feed.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
		
		/* If API instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			
			$this->add_feed_error( esc_html__( 'Feed was not processed because API was not initialized.', 'gravityformsbatchbook' ), $feed, $entry, $form );
			return;
			
		}
		
		/* Either update or create a new person. */	
		if ( rgars( $feed, 'meta/person_update_enable' ) == '1' ) {
			
			$email_address = $this->get_field_value( $form, $entry, $feed['meta']['person_standard_fields_email_address'] );
			
			/* If the email address is empty, exit. */
			if ( GFCommon::is_invalid_or_empty_email( $email_address ) ) {
				
				/* Add feed error. */
				$this->add_feed_error( esc_html__( 'Person was not created because email address was not provided.', 'gravityformsbatchbook' ), $feed, $entry, $form );
				
				return false;
				
			}
			
			$existing_people = $this->api->get_people_by_email( $email_address );
			
			if ( empty( $existing_people ) ) {
				
				$person = $this->create_person( $feed, $entry, $form );
			
			} else {

				$person = $this->update_person( $existing_people[0], $feed, $entry, $form );

			}
		
		} else {
			
			$person = $this->create_person( $feed, $entry, $form );
		
		}
			
	}

	/**
	 * Create person.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return array $person|$original_person
	 */
	public function create_person( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Creating person.' );

		/* Setup mapped fields array. */
		$person_standard_fields = $this->get_field_map_fields( $feed, 'person_standard_fields' );
		$person_custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		/* Prepare person object */
		$person = array(
			'first_name'           => $this->get_field_value( $form, $entry, $person_standard_fields['first_name'] ),
			'last_name'            => $this->get_field_value( $form, $entry, $person_standard_fields['last_name'] ),
			'champion'             => ( isset( $feed['meta']['person_mark_as_champion'] ) && $feed['meta']['person_mark_as_champion'] == '1' ) ? true : false,
			'about'                => GFCommon::replace_variables( $feed['meta']['person_about'], $form, $entry, false, false, false, 'text' ),
			'emails'               => array(
				array(
					'address' => $this->get_field_value( $form, $entry, $person_standard_fields['email_address'] ),
					'label'   => 'main',
					'primary' => true
				),
			),
			'phones'               => array(),
			'addresses'            => array(),
			'websites'             => array(),
			'company_affiliations' => array(),
			'cf_records'           => array(),
			'tags'                 => array()
		);

		/* If the name is empty, exit. */
		if ( rgblank( $person['first_name'] ) || rgblank( $person['last_name'] ) ) {
			
			$this->add_feed_error( esc_html__( 'Person was not created because first and/or last name were not provided.', 'gravityformsbatchbook' ), $feed, $entry, $form );
			return null;
			
		}

		/* If the email address is empty, exit. */
		if ( rgblank( $person['emails'][0]['address'] ) ) {
			
			$this->add_feed_error( esc_html__( 'Person was not created because email address was not provided.', 'gravityformsbatchbook' ), $feed, $entry, $form );
			return null;
			
		}

		/* Add any mapped addresses. */
		$person = $this->add_person_address_data( $person, $feed, $entry, $form );

		/* Add any mapped email addresses. */
		$person = $this->add_person_email_data( $person, $feed, $entry, $form );

		/* Add any mapped phone numbers. */
		$person = $this->add_person_phone_data( $person, $feed, $entry, $form );

		/* Add any mapped websites. */
		$person = $this->add_person_website_data( $person, $feed, $entry, $form );
		
		/* Add tags */
		$person = $this->add_person_tags( $person, $feed, $entry, $form );

		/* Add company */
		$person = $this->add_person_company_data( $person, $feed, $entry, $form );		

		/* Add custom field records */
		$person = $this->add_person_custom_field_data( $person, $feed, $entry, $form );

		$this->log_debug( __METHOD__ . '(): Creating person: ' . print_r( $person, true ) );

		/* Create contact. */
		try {
			
			$person = $this->api->create_person( $person );
			
			gform_update_meta( $entry['id'], 'batchbook_person_id', $person['id'] );

			$this->log_debug( __METHOD__ . '(): Person #' . $person['id'] . ' created.' );
			
		} catch ( Exception $e ) {
			
			$this->add_feed_error( sprintf(
				esc_html__( 'Person could not be created. %s', 'gravityformsbatchbook' ),
				$e->getMessage()
			), $feed, $entry, $form );
			
			return null;
			
		}
				
		return $person;
		
	}

	/**
	 * Update person.
	 * 
	 * @access public
	 * @param array $person
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return array $person|$original_person
	 */
	public function update_person( $person, $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Updating person #' . $person['id'] . '.' );

		/* Save original person object in case. */
		$original_person = $person;

		/* Setup mapped fields array. */
		$contact_data_types = array( 'emails', 'phones', 'addresses', 'websites', 'company_affiliations', 'tags' );
		$person_standard_fields = $this->get_field_map_fields( $feed, 'person_standard_fields' );
		$person_custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );
		
		/* Add standard data. */
		$person['first_name'] = $this->get_field_value( $form, $entry, $person_standard_fields['first_name'] );
		$person['last_name']  = $this->get_field_value( $form, $entry, $person_standard_fields['last_name'] );

		/* Either replace or append new contact information. */
		if ( $feed['meta']['person_update_action'] == 'replace' ) {
			
			$primary_email = $this->get_field_value( $form, $entry, $person_standard_fields['email_address'] );
			
			/* Remove current contact information. */
			foreach ( $contact_data_types as $contact_data_type ) {
				
				if ( ! empty( $person[$contact_data_type] ) ) {
					
					foreach ( $person[$contact_data_type] as &$contact_data ) {
						
						if ( $contact_data_type === 'emails' && $primary_email === $contact_data['address'] )
							continue;
						
						$contact_data['_destroy'] = true;
						
					}
					
				}
				
			}
			
			/* Remove current custom fields. */
			if ( ! empty( $person['cf_records'] ) ) {
				
				foreach ( $person['cf_records'] as &$cf_record ) {
					
					foreach ( $cf_record['custom_field_values'] as &$custom_field ) {
						
						$custom_field['_destroy'] = true;
						
					}
					
				}
				
			}
			
			/* Add standard data. */
			$person['about']      = GFCommon::replace_variables( $feed['meta']['person_about'], $form, $entry, false, false, false, 'text' );
			$person['champion']   = ( isset( $feed['meta']['person_mark_as_champion'] ) && $feed['meta']['person_mark_as_champion'] == '1' ) ? true : false;
			
			/* Add any mapped addresses. */
			$person = $this->add_person_address_data( $person, $feed, $entry, $form, true );
	
			/* Add any mapped email addresses. */
			$person = $this->add_person_email_data( $person, $feed, $entry, $form, true );
	
			/* Add any mapped phone numbers. */
			$person = $this->add_person_phone_data( $person, $feed, $entry, $form, true );
	
			/* Add any mapped websites. */
			$person = $this->add_person_website_data( $person, $feed, $entry, $form, true );
			
			/* Add tags */
			$person = $this->add_person_tags( $person, $feed, $entry, $form, true );

			/* Add company */
			$person = $this->add_person_company_data( $person, $feed, $entry, $form, true );		

			/* Add custom field records */
			$person = $this->add_person_custom_field_data( $person, $feed, $entry, $form, true );

		} else if ( $feed['meta']['person_update_action'] == 'append' ) {
			
			$about = GFCommon::replace_variables( $feed['meta']['person_about'], $form, $entry, false, false, false, 'text' );
			
			/* Add standard data. */
			$person['champion'] = ( isset( $feed['meta']['person_mark_as_champion'] ) && $feed['meta']['person_mark_as_champion'] == '1' ) ? true : false;
			$person['about']    = ( isset( $person['about'] ) ) ? $person['about'] . ' ' . $about : $about;
			
			/* Add any mapped addresses. */
			$person = $this->add_person_address_data( $person, $feed, $entry, $form, true);
	
			/* Add any mapped email addresses. */
			$person = $this->add_person_email_data( $person, $feed, $entry, $form, true );
	
			/* Add any mapped phone numbers. */
			$person = $this->add_person_phone_data( $person, $feed, $entry, $form, true );
	
			/* Add any mapped websites. */
			$person = $this->add_person_website_data( $person, $feed, $entry, $form, true );
	
			/* Add tags */
			$person = $this->add_person_tags( $person, $feed, $entry, $form, true );
		
			/* Add custom field records */
			$person = $this->add_person_custom_field_data( $person, $feed, $entry, $form, true );
			
			/* Remove current company data. */
			if ( ! empty( $person['company_affiliations'] ) ) {
					
				foreach ( $person['company_affiliations'] as &$contact_data ) {
					
					$contact_data['_destroy'] = true;
					
				}
				
			}
			
			/* Add company */
			$person = $this->add_person_company_data( $person, $feed, $entry, $form, true );		

		}
		
		/* Loop through all contact data types and remove "primary" flag if it's set to be destroyed. */
		foreach ( $contact_data_types as $contact_data_type ) {
			
			if ( ! empty( $person[$contact_data_type] ) ) {
				
				foreach ( $person[$contact_data_type] as &$contact_data ) {
					
					if ( isset( $contact_data['_destroy'] ) ) {
						
						$contact_data['primary'] = false;
						
					}
					
				}
				
			}
			
		}
		
		$this->log_debug( __METHOD__ . '(): Updating person: ' . print_r( $person, true ) );

		/* Update person. */
		try {
			
			$this->api->update_person( $person['id'], $person );

			$this->log_debug( __METHOD__ . '(): Person #' . $person['id'] . ' updated.' );
			
		} catch ( Exception $e ) {
			
			$this->add_feed_error( sprintf(
				esc_html__( 'Person could not be updated. %s', 'gravityformsbatchbook' ),
				$e->getMessage()
			), $feed, $entry, $form );
			
			return $original_person;
			
		}

		return $person;
		
	}

	/**
	 * Add address data to person object.
	 * 
	 * @access public
	 * @param array $person
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @param bool $check_for_existing (default: true)
	 * @return $person
	 */
	public function add_person_address_data( $person, $feed, $entry, $form, $check_for_existing = false ) {
		
		$person_custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		/* Add any mapped addresses. */
		foreach ( $person_custom_fields as $field_key => $field ) {
			
			/* If this is not an address mapped field, move on. */
			if ( strpos( $field_key, 'address_' ) !== 0 )
				continue;
			
			$address_field = GFFormsModel::get_field( $form, $field );
			
			/* If the selected field is not an address field, move on. */
			if ( GFFormsModel::get_input_type( $address_field ) !== 'address' )
				continue;
				
			/* Prepare the label field. */
			$label = str_replace( 'address_', '', $field_key );

			/* Get the address field ID. */
			$address_field_id = $address_field->id;

			/* If any of the fields are empty, move on. */
			if ( rgblank( $entry[$address_field_id . '.1'] ) || rgblank( $entry[$address_field_id . '.3'] ) || rgblank( $entry[$address_field_id . '.4'] ) || rgblank( $entry[$address_field_id . '.5'] ) )
				continue;

			/* Check if this address is already in the address data. */
			if ( $check_for_existing && ! empty( $person['addresses'] ) && $this->exists_in_array( $person['addresses'], 'address_1', $entry[$address_field_id . '.1'] ) ) {
			
				foreach ( $person['addresses'] as &$address ) {
					
					if ( $address['address_1'] === $entry[$address_field_id . '.1'] ) {
						
						unset( $address['_destroy'] );
						
					}
					
				}

			} else {
	
				/* Add the address to the contact. */
				$person['addresses'][] = array(
					'address_1'   => $entry[$address_field_id . '.1'],
					'address_2'   => $entry[$address_field_id . '.2'],
					'city'        => $entry[$address_field_id . '.3'],
					'state'       => $entry[$address_field_id . '.4'],
					'postal_code' => $entry[$address_field_id . '.5'],
					'country'     => $entry[$address_field_id . '.6'],
					'label'       => $label,
					'primary'     => ( $label == 'main' && ! $this->primary_data_exists( $person['addresses'] ) ) ? true : false
				);
				
			}
			
		}

		return $person;
		
	}
	
	/**
	 * Add company data to person object.
	 * 
	 * @access public
	 * @param array $person
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @param bool $check_for_existing (default: true)
	 * @return $person
	 */
	public function add_person_company_data( $person, $feed, $entry, $form, $check_for_existing = false ) {
		
		$person_custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );
		
		/* If company field is mapped, search for company and create if doesn't exist. */
		if ( isset( $person_custom_fields['company'] ) ) {
			
			/* Prepare variables for company affiliation mapping. */
			$create_company = false;
			$company_id     = null;
			$company_name   = $this->get_field_value( $form, $entry, $person_custom_fields['company'] );
			$job_title      = isset( $person_custom_fields['title'] ) ? $this->get_field_value( $form, $entry, $person_custom_fields['title'] ) : '';
			
			/* If the company name is empty, move on. */
			if ( rgblank( $company_name ) ) {
				return $person;
			}
			
			/* Do a search for existing companies. */
			$existing_companies = $this->api->get_companies_by_name( $company_name );
			
			/* If no companies match the search, mark $create_company as true. */
			if ( empty( $existing_companies ) ) {
				
				$create_company = true;
				
			} else { 
				
				/* Loop through companies for an exact match. */
				foreach ( $existing_companies as $company ) {
					
					if ( $company['name'] === $company_name ) {
						
						$company_id = $company['id'];
						
					}
					
				}
				
			}
			
			/* Create company if needed. */
			if ( $create_company ) {
				
				try { 
					
					/* Create company. */
					$company = array( 'name' => $company_name );
					$company = $this->api->create_company( $company );
					
					/* Log that company was created. */
					$this->log_debug( __METHOD__ . '(): Company #' . $company['id'] . ' created.' );

					/* Set company id. */
					$company_id = $company['id'];
					
				} catch ( Exception $e ) {
			
					$this->add_feed_error( sprintf(
						esc_html__( 'Company could not be created. %s', 'gravityformsbatchbook' ),
						$e->getMessage()
					), $feed, $entry, $form );
										
				}
				
			}
			
			/* If company ID is set, add affiliation. */
			if ( empty( $company_id ) ) {
				
				return $person;
				
			}

			/* Check if this website is already in the website data. */
			if ( $check_for_existing && ! empty( $person['company_affiliations'] ) && $this->exists_in_array( $person['company_affiliations'], 'company_id', $company_id ) ) {
				
				foreach ( $person['company_affiliations'] as &$_company ) {
					
					if ( $_company['company_id'] === $company_id ) {
						
						unset( $_company['_destroy'] );
						
					}
					
				}
				
			} else {

				$person['company_affiliations'][] = array(
					'company_id' => $company_id,
					'current'    => true,
					'job_title'  => $job_title	
				);
				
			}

			
		}

		return $person;
		
	}
	
	/**
	 * Add custom field data to person object.
	 * 
	 * @access public
	 * @param array $person
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @param bool $check_for_existing (default: true)
	 * @return $person
	 */
	public function add_person_custom_field_data( $person, $feed, $entry, $form, $check_for_existing = false ) {
		
		$person_custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );
		
		/* Add any mapped custom fields. */
		foreach ( $person_custom_fields as $field_key => $field ) {
			
			/* Get the field value. */
			$field_value = $this->get_field_value( $form, $entry, $field );

			/* If this is not an custom field or the field value is blank, move on. */
			if ( strpos( $field_key, 'custom_field_' ) !== 0 || rgblank( $field_value ) )
				continue;
				
			$field_key_exploded = explode( '_', $field_key );
			$custom_field_set_id = $field_key_exploded[2];
			$custom_field_definition_id = $field_key_exploded[3];
				
			/* Check if this custom field is already in the custom field data. */
			if ( $check_for_existing && ! empty( $person['cf_records'] ) ) {
			
				$found_custom_field_set = $found_custom_field = false;
			
				foreach ( $person['cf_records'] as &$cf_record ) {
					
					if ( $cf_record['custom_field_set_id'] == $custom_field_set_id ) {
						
						$found_custom_field_set = true;
						
						foreach ( $cf_record['custom_field_values'] as &$custom_field ) {
							
							if ( $custom_field['custom_field_definition_id'] == $custom_field_definition_id ) {
								
								unset( $custom_field['_destroy'] );
								
								$custom_field['text_value'] = $field_value;
								
							}
														
						}
												
					}
					
				}
				
				if ( ! $found_custom_field_set ) {
				
					$person['cf_records'][] = array(
						'custom_field_set_id' => $custom_field_set_id,
						'custom_field_values' => array(
							array(
								'custom_field_definition_id' => $custom_field_definition_id,
								'text_value'                 => $field_value
							)
						)	
					);
					
				}

			} else {
			
				if ( empty( $person['cf_records'] ) ) {
					
					$person['cf_records'][] = array(
						'custom_field_set_id' => $custom_field_set_id,
						'custom_field_values' => array(
							array(
								'custom_field_definition_id' => $custom_field_definition_id,
								'text_value'                 => $field_value
							)
						)	
					);
					
				} else {
					
					$found_custom_field_set = false;
					
					foreach ( $person['cf_records'] as &$cf_record ) {
						
						if ( $cf_record['custom_field_set_id'] == $custom_field_set_id ) {
							
							$found_custom_field_set = true;
							
							$cf_record['custom_field_values'][] = array(
								'custom_field_definition_id' => $custom_field_definition_id,
								'text_value'                 => $field_value
							);
							
						}
						
					}
					
					if ( ! $found_custom_field_set ) {
					
						$person['cf_records'][] = array(
							'custom_field_set_id' => $custom_field_set_id,
							'custom_field_values' => array(
								array(
									'custom_field_definition_id' => $custom_field_definition_id,
									'text_value'                 => $field_value
								)
							)	
						);
						
					}
					
				}
				
			}
			
		}

		return $person;
		
	}
	
	/**
	 * Add email address data to person object.
	 * 
	 * @access public
	 * @param array $person
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @param bool $check_for_existing (default: true)
	 * @return $person
	 */
	public function add_person_email_data( $person, $feed, $entry, $form, $check_for_existing = false ) {
		
		$person_custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		/* Add any mapped email fields. */
		foreach ( $person_custom_fields as $field_key => $field ) {
			
			/* Get the email address. */
			$email_address = $this->get_field_value( $form, $entry, $field );

			/* If this is not an email address field or the email address is blank, move on. */
			if ( strpos( $field_key, 'email_' ) !== 0 || rgblank( $email_address ) )
				continue;
				
			/* Check if this email address is already in the email address data. */
			if ( $check_for_existing && ! empty( $person['emails'] ) && $this->exists_in_array( $person['emails'], 'address', $email_address ) ) {
			
				foreach ( $person['emails'] as &$email ) {
					
					if ( $email['address'] === $email_address ) {
						
						unset( $email['_destroy'] );
						
					}
					
				}
				
			} else {
			
				/* Prepare the label field. */
				$label = str_replace( 'email_', '', $field_key );
				
				/* Add the email address to the contact. */
				$person['emails'][] = array(
					'address' => $email_address,
					'label'   => $label,
					'primary' => false
				);
				
			}
			
		}

		return $person;

	}

	/**
	 * Add phone number data to person object.
	 * 
	 * @access public
	 * @param array $person
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @param bool $check_for_existing (default: true)
	 * @return $person
	 */
	public function add_person_phone_data( $person, $feed, $entry, $form, $check_for_existing = false ) {
		
		$person_custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		/* Add any mapped phone numbers. */
		foreach ( $person_custom_fields as $field_key => $field ) {
			
			/* Get the phone number. */
			$phone_number = $this->get_field_value( $form, $entry, $field );

			/* If this is not an phone number field or the phone number is blank, move on. */
			if ( strpos( $field_key, 'phone_' ) !== 0 || rgblank( $phone_number ) ) {
				continue;
			}
			
			/* Check if this phone number is already in the phone number data. */
			if ( $check_for_existing && ! empty( $person['phones'] ) && $this->exists_in_array( $person['phones'], 'number', $phone_number ) ) {
			
				foreach ( $person['phones'] as &$phone ) {
					
					if ( $phone['number'] === $phone_number ) {
						
						unset( $phone['_destroy'] );
											
					}
					
				}
				
			} else {
			
				/* Prepare the label field. */
				$label = str_replace( 'phone_', '', $field_key );
				
				/* Add the phone nubmer to the contact. */
				$person['phones'][] = array(
					'number'  => $phone_number,
					'label'   => $label,
					'primary' => ( $label == 'main' && ! $this->primary_data_exists( $person['phones'] ) ) ? true : false
				);
				
			}
			
		}

		return $person;
		
	}

	/**
	 * Add website data to person object.
	 * 
	 * @access public
	 * @param array $person
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @param bool $check_for_existing (default: true)
	 * @return $person
	 */
	public function add_person_website_data( $person, $feed, $entry, $form, $check_for_existing = false ) {
		
		$person_custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		/* Add any mapped websites. */
		foreach ( $person_custom_fields as $field_key => $field ) {
			
			/* Get the website address. */
			$website_address = $this->get_field_value( $form, $entry, $field );

			/* If this is not an website address field or the website address is blank, move on. */
			if ( strpos( $field_key, 'website_' ) !== 0 || rgblank( $website_address ) )
				continue;
			
			/* Check if this website is already in the website data. */
			if ( $check_for_existing && ! empty( $person['websites'] ) && $this->exists_in_array( $person['websites'], 'address', $website_address ) ) {
				
				foreach ( $person['websites'] as &$website ) {
					
					if ( $website['address'] === $website_address ) {
						
						unset( $website['_destroy'] );
						
					}
					
				}
				
			} else {

				/* Prepare the label field. */
				$label = str_replace( 'website_', '', $field_key );
				
				/* Add the website to the contact. */
				$person['websites'][] = array(
					'address' => $website_address,
					'label'   => $label,
					'primary' => ( $label == 'main' && ! $this->primary_data_exists( $person['websites'] ) ) ? true : false
				);
				
			}
			
		}

		return $person;
		
	}

	/**
	 * Add tags to person object.
	 * 
	 * @access public
	 * @param array $person
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @param bool $check_for_existing (default: true)
	 * @return $person
	 */
	public function add_person_tags( $person, $feed, $entry, $form, $check_for_existing = true ) {
		
		if ( isset( $feed['meta']['person_tags'] ) ) {
			
			$tags = explode( ',', GFCommon::replace_variables( $feed['meta']['person_tags'], $form, $entry, false, false, false, 'text' ) );
			
		} else {
			
			$tags = array();
			
		}
		
		/* Apply filters on tags. */
		$tags = apply_filters( 'gform_batchbook_tags_' . $form['id'], apply_filters( 'gform_batchbook_tags', $tags, $feed, $entry, $form ), $feed, $entry, $form );
		
		/* Add tags to person. */
		if ( ! empty( $tags ) ) {
			
			foreach ( $tags as $tag ) {
				
				/* Check if this tag is already in the tags data. */
				if ( $check_for_existing && ! empty( $person['tags'] ) && $this->exists_in_array( $person['tags'], 'name', $tag ) ) {
					
					foreach ( $person['tags'] as &$_tag ) {
						
						if ( $_tag['name'] === $tag ) {
							
							unset( $_tag['_destroy'] );
							
						}
						
					}
					
				} else {
	
					$person['tags'][] = array( 'name' => trim( $tag ) );
					
				}

			}
			
		}
		
		return $person;
		
	}

	/**
	 * Check if value exists in multidimensional array.
	 * 
	 * @access public
	 * @param array $array
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	public function exists_in_array( $array = array(), $key, $value ) {
		
		foreach ( $array as $item ) {
			
			if ( ! isset( $item[$key] ) )
				continue;
				
			if ( $item[$key] == $value )
				return true;
			
		}
		
		return false;
		
	}

	/**
	 * Check if primary value is set for data array.
	 * 
	 * @access public
	 * @param array $data
	 * @return bool
	 */
	public function primary_data_exists( $data ) {
		
		foreach ( $data as $item ) {
			if ( rgar( $item, 'primary' ) && ! rgar( $item, '_destroy' ) ) {
				return true;
			}
		}
		
		return false;
		
	}

	/**
	 * Initializes Batchbook API if credentials are valid.
	 * 
	 * @access public
	 * @return bool
	 */
	public function initialize_api() {

		if ( ! is_null( $this->api ) ) {
			return true;
		}
		
		/* Load the Batchbook API library. */
		if ( ! class_exists( 'Batchbook' ) ) {
			require_once 'includes/class-batchbook.php';
		}

		/* Get the plugin settings */
		$settings = $this->get_plugin_settings();
		
		/* If any of the account information fields are empty, return null. */
		if ( rgblank( $settings['account_url'] ) || rgblank( $settings['api_token'] ) ) {
			return null;
		}
			
		$this->log_debug( __METHOD__ . "(): Validating API info." );
		
		$batchbook = new Batchbook( $settings['account_url'], $settings['api_token'] );
		
		try {
			
			/* Run API test. */
			$batchbook->get_users();
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );
			
			/* Assign Batchbook object to the class. */
			$this->api = $batchbook;
			
			return true;
			
		} catch ( Exception $e ) {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );			

			return false;
			
		}
		
	}
	
	/**
	 * Checks validity of Batchbook account URL.
	 * 
	 * @access public
	 * @param mixed $account_url
	 * @return void
	 */
	public function validate_account_url( $account_url ) {
		
		/* Load the Batchbook API library. */
		if ( ! class_exists( 'Batchbook' ) ) {
			require_once 'includes/class-batchbook.php';
		}

		/* If the account URL is empty, return null. */
		if ( rgblank( $account_url ) ) {
			return null;
		}
			
		$this->log_debug( __METHOD__ . "(): Validating account URL: {$account_url}.batchbook.com" );
		
		try {
					
			$batchbook = new Batchbook( $account_url );
			
			/* Run account URL test. */
			$batchbook->validate_account_url();
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): Account URL is valid.' );
						
			return true;
			
		} catch ( Exception $e ) {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): Account URL is invalid.' );			

			return false;
			
		}
		
	}

}
