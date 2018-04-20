<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Batchbook Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2017, Rocketgenius
 */
class GFBatchbook extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Batchbook Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from batchbook.php
	 */
	protected $_version = GF_BATCHBOOK_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.14.26';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsbatchbook';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsbatchbook/batchbook.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Batchbook Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Batchbook';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_batchbook';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_batchbook';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_batchbook_uninstall';

	/**
	 * Defines the capabilities needed for the Batchbook Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_batchbook', 'gravityforms_batchbook_uninstall' );

	/**
	 * Contains an instance of the Batchbook API libray, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    object $api If available, contains an instance of the Batchbook API library.
	 */
	protected $api = null;

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return GFBatchbook
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Register needed plugin hooks and PayPal delayed payment support.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @uses GFFeedAddOn::add_delayed_payment_support()
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
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'  => 'gform_batchbook_form_settings_css',
				'src'     => $this->get_base_url() . '/css/form_settings.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_settings' ) ),
				),
			),
		);

		return array_merge( parent::styles(), $styles );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Setup plugin settings fields.
	 *
	 * @since  1.0
	 * @access public
	 *
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
						'feedback_callback' => array( $this, 'validate_account_url' ),
					),
					array(
						'name'              => 'api_token',
						'label'             => esc_html__( 'API Token', 'gravityformsbatchbook' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => esc_html__( 'Batchbook settings have been updated.', 'gravityformsbatchbook' ),
						),
					),
				),
			),
		);

	}

	/**
	 * Prepare plugin settings description.
	 *
	 * @since  1.0
	 * @access public
	 *
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

			$description .= sprintf(
				'<p>%s</p>',
				esc_html__( 'Gravity Forms Batchbook Add-On requires your account URL and API Key, which can be found on your Personal Settings page.', 'gravityformsbatchbook' )
			);

		}

		return $description;

	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFBatchbook::custom_fields_for_feed_mapping()
	 * @uses GFBatchbook::standard_fields_for_feed_mapping()
	 * @uses GFFeedAddOn::get_default_feed_name()
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		return array(
			array(
				'fields' => array(
					array(
						'name'           => 'feed_name',
						'label'          => esc_html__( 'Feed Name', 'gravityformsbatchbook' ),
						'type'           => 'text',
						'required'       => true,
						'class'          => 'medium',
						'default_value'  => $this->get_default_feed_name(),
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformsbatchbook' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsbatchbook' )
						),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Person Details', 'gravityformsbatchbook' ),
				'fields' => array(
					array(
						'name'           => 'person_standard_fields',
						'label'          => esc_html__( 'Map Fields', 'gravityformsbatchbook' ),
						'type'           => 'field_map',
						'field_map'      => $this->standard_fields_for_feed_mapping(),
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'gravityformsbatchbook' ),
							esc_html__( 'Select which Gravity Form fields pair with their respective Batchbook fields. Batchbook custom fields must be a text field type to be mappable.', 'gravityformsbatchbook' )
						),
					),
					array(
						'name'           => 'person_custom_fields',
						'label'          => null,
						'type'           => 'dynamic_field_map',
						'field_map'      => $this->custom_fields_for_feed_mapping(),
						'disable_custom' => true,
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
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Update Person', 'gravityformsbatchbook' ),
							esc_html__( 'If enabled and an existing person is found, their contact details will either be replaced or appended. Job title and company will be replaced whether replace or append is chosen.', 'gravityformsbatchbook' )
						),
						'checkbox'       => array(
							'name'          => 'person_update_enable',
							'label'         => esc_html__( 'Update Person if already exists', 'gravityformsbatchbook' ),
						),
						'select'         => array(
							'name'          => 'person_update_action',
							'choices'       => array(
								array(
									'label' => esc_html__( 'and replace existing data', 'gravityformsbatchbook' ),
									'value' => 'replace',
								),
								array(
									'label' => esc_html__( 'and append new data', 'gravityformsbatchbook' ),
									'value' => 'append',
								),
							),
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
						),
					),
				),
			),
			array(
				'title' => esc_html__( 'Feed Conditional Logic', 'gravityformsbatchbook' ),
				'fields' => array(
					array(
						'name'           => 'feed_condition',
						'type'           => 'feed_condition',
						'label'          => esc_html__( 'Conditional Logic', 'gravityformsbatchbook' ),
						'checkbox_label' => esc_html__( 'Enable', 'gravityformsbatchbook' ),
						'instructions'   => esc_html__( 'Export to Batchbook if', 'gravityformsbatchbook' ),
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformsbatchbook' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Batchbook when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsbatchbook' )
						),
					),
				),
			),
		);

	}

	/**
	 * Prepare standard fields for feed field mapping.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_first_field_by_type()
	 *
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
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFBatchbook::get_batchbook_custom_fields()
	 *
	 * @return array
	 */
	public function custom_fields_for_feed_mapping() {

		$field_map = array(
			array(
				'value'   => null,
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
						'value' => 'email_work',
					),
					array(
						'label' => esc_html__( 'Home', 'gravityformsbatchbook' ),
						'value' => 'email_home',
					),
					array(
						'label' => esc_html__( 'Other', 'gravityformsbatchbook' ),
						'value' => 'email_other',
					),
				)
			),
			array(
				'label'   => esc_html__( 'Phone Number', 'gravityformsbatchbook' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Main', 'gravityformsbatchbook' ),
						'value' => 'phone_main',
					),
					array(
						'label' => esc_html__( 'Work', 'gravityformsbatchbook' ),
						'value' => 'phone_work',
					),
					array(
						'label' => esc_html__( 'Mobile', 'gravityformsbatchbook' ),
						'value' => 'phone_mobile',
					),
					array(
						'label' => esc_html__( 'Home', 'gravityformsbatchbook' ),
						'value' => 'phone_home',
					),
					array(
						'label' => esc_html__( 'Fax', 'gravityformsbatchbook' ),
						'value' => 'phone_fax',
					),
					array(
						'label' => esc_html__( 'Other', 'gravityformsbatchbook' ),
						'value' => 'phone_other',
					),
				)
			),
			array(
				'label'   => esc_html__( 'Address', 'gravityformsbatchbook' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Main', 'gravityformsbatchbook' ),
						'value' => 'address_main',
					),
					array(
						'label' => esc_html__( 'Work', 'gravityformsbatchbook' ),
						'value' => 'address_work',
					),
					array(
						'label' => esc_html__( 'Home', 'gravityformsbatchbook' ),
						'value' => 'address_home',
					),
					array(
						'label' => esc_html__( 'Billing', 'gravityformsbatchbook' ),
						'value' => 'address_billing',
					),
					array(
						'label' => esc_html__( 'Shipping', 'gravityformsbatchbook' ),
						'value' => 'address_shipping',
					),
					array(
						'label' => esc_html__( 'Other', 'gravityformsbatchbook' ),
						'value' => 'address_other',
					),
				)
			),
			array(
				'label'   => esc_html__( 'Website', 'gravityformsbatchbook' ),
				'choices' => array(
					array(
						'label' => esc_html__( 'Main', 'gravityformsbatchbook' ),
						'value' => 'website_main',
					),
					array(
						'label' => esc_html__( 'Work', 'gravityformsbatchbook' ),
						'value' => 'website_work',
					),
					array(
						'label' => esc_html__( 'Home', 'gravityformsbatchbook' ),
						'value' => 'website_home',
					),
					array(
						'label' => esc_html__( 'Other', 'gravityformsbatchbook' ),
						'value' => 'website_other',
					),
				),
			),
		);

		// Get Batchbook custom fields.
		$custom_fields = $this->get_batchbook_custom_fields();

		// If Batcbhook custom fields were found, add them to the field map.
		if ( ! empty( $custom_fields ) ) {
			$field_map = array_merge( $field_map, $custom_fields );
		}

		return $field_map;

	}

	/**
	 * Get Batchbook custom fields for feed field mapping.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFBatchbook::initialize_api()
	 * @uses GF_Batchbook_API::get_custom_field_sets()
	 *
	 * @return array $custom_fields
	 */
	public function get_batchbook_custom_fields() {

		// Initialize custom fields array.
		$custom_fields = array();

		// If API is not initialized, return.
		if ( ! $this->initialize_api() ) {
			return $custom_fields;
		}

		try {

			// Get Batchbook custom field sets.
			$custom_field_sets = $this->api->get_custom_field_sets();

		} catch ( Exception $e ) {

			// Log that custom field sets could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to retrieve custom field sets; ' . $e->getMessage() );

			return $custom_fields;

		}

		// If no custom field sets were found, return.
		if ( empty( $custom_field_sets ) ) {
			return $custom_fields;
		}

		// Loop through custom field sets.
		foreach ( $custom_field_sets as $custom_field_set ) {

			// If custom field set has no definitions, skip it.
			if ( empty( $custom_field_set['custom_field_definitions_attributes'] ) ) {
				continue;
			}

			// Initialize field set.
			$field_set = array(
				'label'   => rgar( $custom_field_set, 'name' ),
				'choices' => array()
			);

			// Loop through custom fields.
			foreach ( $custom_field_set['custom_field_definitions_attributes'] as $custom_field ) {

				// If custom field is not an allowed type, skip it.
				if ( 'CustomField::Text' !== $custom_field['custom_field_type'] ) {
					continue;
				}

				// Add custom field as choice.
				$field_set['choices'][] = array(
					'label' => $custom_field['name'],
					'value' => 'custom_field_' . $custom_field_set['id'] . '_' . $custom_field['id'],
				);

			}

			// If custom fields were found for custom field set, add to custom fields array.
			if ( ! empty( $field_set['choices'] ) ) {
				$custom_fields[] = $field_set;
			}

		}

		return $custom_fields;

	}





	// # FEED LIST -----------------------------------------------------------------------------------------------------

	/**
	 * Set feed creation control.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFBatchbook::initialize_api()
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.1
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
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feed_name' => esc_html__( 'Name', 'gravityformsbatchbook' ),
			'action'    => esc_html__( 'Action', 'gravityformsbatchbook' ),
		);

	}

	/**
	 * Get value for action feed list column.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed The current feed.
	 *
	 * @return string
	 */
	public function get_column_value_action( $feed ) {

		return esc_html__( 'Create New Person', 'gravityformsbatchbook' );

	}





	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process feed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @uses GFAddOn::get_field_value()
	 * @uses GFAddOn::log_debug()
	 * @uses GFBatchbook::create_person()
	 * @uses GFBatchbook::initialize_api()
	 * @uses GFBatchbook::update_person()
	 * @uses GF_Batchbook_API::get_people_by_email()
	 * @uses GFCommon::is_invalid_or_empty_email()
	 * @uses GFFeedAddOn::add_feed_error()
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {

			// Log that feed could not be processed.
			$this->add_feed_error( esc_html__( 'Feed was not processed because API was not initialized.', 'gravityformsbatchbook' ), $feed, $entry, $form );

			return;

		}

		// Get person email address.
		$email_address = rgars( $feed, 'meta/person_standard_fields_email_address' );
		$email_address = $this->get_field_value( $form, $entry, $email_address );

		// If the email address is invalid, exit. */
		if ( GFCommon::is_invalid_or_empty_email( $email_address ) ) {

			// Log that feed could not be processed.
			$this->add_feed_error( esc_html__( 'Person was not created because email address was not provided.', 'gravityformsbatchbook' ), $feed, $entry, $form );

			return;

		}

		// Create or update person based on existing records.
		if ( rgars( $feed, 'meta/person_update_enable' ) == '1' ) {

			// Log that updating is not enabled.
			$this->log_debug( __METHOD__ . "(): Search for existing people with email address \"$email_address\"." );

			// Search for existing people.
			$people = $this->api->get_people_by_email( $email_address );

			// If no existing people were found, create person.
			if ( empty( $people ) ) {

				// Log that updating is not enabled.
				$this->log_debug( __METHOD__ . '(): No person was found. Creating new person.' );

				// Create person.
				$person = $this->create_person( $feed, $entry, $form );

			} else {

				// Get person to update.
				$person = $people[0];

				// Log that updating is not enabled.
				$this->log_debug( __METHOD__ . '(): Updating existing person #' . $person['id'] . '.' );

				// Update person.
				$person = $this->update_person( $person, $feed, $entry, $form );

			}

		} else {

			// Log that updating is not enabled.
			$this->log_debug( __METHOD__ . '(): Person updating is not enabled. Creating new person.' );

			// Create person.
			$person = $this->create_person( $feed, $entry, $form );

		}

	}

	/**
	 * Create person.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFAddOn::log_debug()
	 * @uses GFBatchbook::add_person_address_data()
	 * @uses GFBatchbook::add_person_company_data()
	 * @uses GFBatchbook::add_person_custom_field_data()
	 * @uses GFBatchbook::add_person_email_data()
	 * @uses GFBatchbook::add_person_phone_data()
	 * @uses GFBatchbook::add_person_tags()
	 * @uses GFBatchbook::add_person_website_data()
	 * @uses GF_Batchbook_API::create_person()
	 * @uses GFCommon::is_invalid_or_empty_email()
	 * @uses GFCommon::replace_variables()
	 * @uses GFFeedAddon::add_feed_error()
	 *
	 * @return array
	 */
	public function create_person( $feed, $entry, $form ) {

		// Get mapped fields.
		$standard_fields = $this->get_field_map_fields( $feed, 'person_standard_fields' );

		// Initialize person object.
		$person = array(
			'first_name'           => $this->get_field_value( $form, $entry, $standard_fields['first_name'] ),
			'last_name'            => $this->get_field_value( $form, $entry, $standard_fields['last_name'] ),
			'champion'             => '1' == rgars( $feed, 'meta/person_mark_as_champion' ) ? true : false,
			'about'                => GFCommon::replace_variables( $feed['meta']['person_about'], $form, $entry, false, false, false, 'text' ),
			'emails'               => array(
				array(
					'address' => $this->get_field_value( $form, $entry, $standard_fields['email_address'] ),
					'label'   => 'main',
					'primary' => true
				),
			),
			'phones'               => array(),
			'addresses'            => array(),
			'websites'             => array(),
			'company_affiliations' => array(),
			'cf_records'           => array(),
			'tags'                 => array(),
		);

		// If no name is provided, exit.
		if ( rgblank( $person['first_name'] ) || rgblank( $person['last_name'] ) ) {

			// Log that person could not be created.
			$this->add_feed_error( esc_html__( 'Person was not created because first and/or last name were not provided.', 'gravityformsbatchbook' ), $feed, $entry, $form );

			return null;

		}

		// If email address is invalid, exit.
		if ( GFCommon::is_invalid_or_empty_email( $person['emails'][0]['address'] ) ) {

			// Log that person could not be created.
			$this->add_feed_error( esc_html__( 'Person was not created because an empty or invalid email address was provided.', 'gravityformsbatchbook' ), $feed, $entry, $form );

			return null;

		}

		// Add mapped data.
		$person = $this->add_person_address_data( $person, $feed, $entry, $form );
		$person = $this->add_person_email_data( $person, $feed, $entry, $form );
		$person = $this->add_person_phone_data( $person, $feed, $entry, $form );
		$person = $this->add_person_website_data( $person, $feed, $entry, $form );
		$person = $this->add_person_tags( $person, $feed, $entry, $form );
		$person = $this->add_person_company_data( $person, $feed, $entry, $form );
		$person = $this->add_person_custom_field_data( $person, $feed, $entry, $form );

		/**
		 * Modify the person object before it is created.
		 *
		 * @since 1.2.1
		 *
		 * @param array $person  The person object being created.
		 * @param array $feed    The feed object.
		 * @param array $entry   The entry object.
		 * @param array $form    The form object.
		 * @param bool  $updated If the person object is being updated.
		 */
		$person = gf_apply_filters( array( 'gform_batchbook_person', $form['id'] ), $person, $feed, $entry, $form, false );

		// Log the person being created.
		$this->log_debug( __METHOD__ . '(): Creating person: ' . print_r( $person, true ) );

		try {

			// Create person.
			$person = $this->api->create_person( $person );

			// Save person ID to entry meta.
			gform_update_meta( $entry['id'], 'batchbook_person_id', $person['id'] );

			// Log that person was created.
			$this->log_debug( __METHOD__ . '(): Person #' . $person['id'] . ' created.' );

		} catch ( Exception $e ) {

			// Log that person could not be created.
			$this->add_feed_error( sprintf( esc_html__( 'Person could not be created. %s', 'gravityformsbatchbook' ), $e->getMessage() ), $feed, $entry, $form );

			return null;

		}

		return $person;

	}

	/**
	 * Update person.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person Person object.
	 * @param array $feed   Feed object.
	 * @param array $entry  Entry object.
	 * @param array $form   Form object.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFAddOn::log_debug()
	 * @uses GFBatchbook::add_person_address_data()
	 * @uses GFBatchbook::add_person_company_data()
	 * @uses GFBatchbook::add_person_custom_field_data()
	 * @uses GFBatchbook::add_person_email_data()
	 * @uses GFBatchbook::add_person_phone_data()
	 * @uses GFBatchbook::add_person_tags()
	 * @uses GFBatchbook::add_person_website_data()
	 * @uses GF_Batchbook_API::update_person()
	 * @uses GFCommon::replace_variables()
	 * @uses GFFeedAddon::add_feed_error()
	 *
	 * @return array $person
	 */
	public function update_person( $person, $feed, $entry, $form ) {

		// Store original person object.
		$original_person = $person;

		// Prepare contact data types.
		$contact_data_types = array( 'emails', 'phones', 'addresses', 'websites', 'company_affiliations', 'tags' );

		// Get mapped fields.
		$standard_fields = $this->get_field_map_fields( $feed, 'person_standard_fields' );

		// Add person name.
		$person['first_name'] = $this->get_field_value( $form, $entry, $standard_fields['first_name'] );
		$person['last_name']  = $this->get_field_value( $form, $entry, $standard_fields['last_name'] );

		// Replace replace or append new contact information.
		if ( 'replace' === $feed['meta']['person_update_action'] ) {

			// Get primary email address.
			$primary_email = $this->get_field_value( $form, $entry, $standard_fields['email_address'] );

			// Remove current contact information.
			foreach ( $contact_data_types as $contact_data_type ) {

				if ( ! empty( $person[ $contact_data_type ] ) ) {

					foreach ( $person[ $contact_data_type ] as &$contact_data ) {

						if ( $contact_data_type === 'emails' && $primary_email === $contact_data['address'] ) {
							continue;
						}

						$contact_data['_destroy'] = true;

					}

				}

			}

			// Remove current custom fields.
			if ( ! empty( $person['cf_records'] ) ) {
				foreach ( $person['cf_records'] as &$cf_record ) {
					foreach ( $cf_record['custom_field_values'] as &$custom_field ) {
						$custom_field['_destroy'] = true;
					}
				}
			}

			// Add standard data.
			$person['about']    = GFCommon::replace_variables( $feed['meta']['person_about'], $form, $entry, false, false, false, 'text' );
			$person['champion'] = '1' == rgars( $feed, 'meta/person_mark_as_champion' ) ? true : false;

			// Add mapped data.
			$person = $this->add_person_address_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_email_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_phone_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_website_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_tags( $person, $feed, $entry, $form, true );
			$person = $this->add_person_company_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_custom_field_data( $person, $feed, $entry, $form, true );

		} else if ( 'append' === $feed['meta']['person_update_action'] ) {

			// Replace variables in about data.
			$about = GFCommon::replace_variables( $feed['meta']['person_about'], $form, $entry, false, false, false, 'text' );

			// Add standard data.
			$person['about']    = isset( $person['about'] ) ? $person['about'] . ' ' . $about : $about;
			$person['champion'] = '1' == rgars( $feed, 'meta/person_mark_as_champion' ) ? true : false;

			// Remove current company affiliations.
			if ( ! empty( $person['company_affiliations'] ) ) {
				foreach ( $person['company_affiliations'] as &$contact_data ) {
					$contact_data['_destroy'] = true;
				}
			}

			// Add mapped data.
			$person = $this->add_person_address_data( $person, $feed, $entry, $form, true);
			$person = $this->add_person_email_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_phone_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_website_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_tags( $person, $feed, $entry, $form, true );
			$person = $this->add_person_custom_field_data( $person, $feed, $entry, $form, true );
			$person = $this->add_person_company_data( $person, $feed, $entry, $form, true );

		}

		// Remove primary flag from contact data to be destroyed.
		foreach ( $contact_data_types as $contact_data_type ) {
			if ( ! empty( $person[ $contact_data_type ] ) ) {
				foreach ( $person[ $contact_data_type ] as &$contact_data ) {
					if ( isset( $contact_data['_destroy'] ) ) {
						$contact_data['primary'] = false;
					}
				}
			}
		}

		/**
		 * Modify the person object before it is updated.
		 *
		 * @since 1.2.1
		 *
		 * @param array $person  The person object being updated.
		 * @param array $feed    The feed object.
		 * @param array $entry   The entry object.
		 * @param array $form    The form object.
		 * @param bool  $updated If the person object is being updated.
		 */
		$person = gf_apply_filters( array( 'gform_batchbook_person', $form['id'] ), $person, $feed, $entry, $form, true );

		// Log the person being updated.
		$this->log_debug( __METHOD__ . '(): Updating person: ' . print_r( $person, true ) );

		try {

			// Update person.
			$this->api->update_person( $person['id'], $person );

			// Save person ID to entry meta.
			gform_update_meta( $entry['id'], 'batchbook_person_id', $person['id'] );

			// Log that person was updated.
			$this->log_debug( __METHOD__ . '(): Person #' . $person['id'] . ' updated.' );

		} catch ( Exception $e ) {

			// Log that person could not be updated.
			$this->add_feed_error( sprintf( esc_html__( 'Person could not be updated. %s', 'gravityformsbatchbook' ), $e->getMessage() ), $feed, $entry, $form );

			return $original_person;

		}

		return $person;

	}

	/**
	 * Add address data to person object.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person   Person object.
	 * @param array $feed     Feed object.
	 * @param array $entry    Entry object.
	 * @param array $form     Form object.
	 * @param bool  $existing Look for existing address before adding.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFBatchbook::exists_in_array()
	 * @uses GFBatchbook::primary_data_exists()
	 * @uses GFFormsModel::get_field()
	 * @uses GFFormsModel::get_input_type()
	 *
	 * @return array
	 */
	public function add_person_address_data( $person, $feed, $entry, $form, $existing = false ) {

		// Get mapped custom fields.
		$custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		// Loop through custom fields.
		foreach ( $custom_fields as $key => $value ) {

			// If this is not an address custom field, skip it.
			if ( strpos( $key, 'address_' ) !== 0 ) {
				continue;
			}

			// Get selected field.
			$address_field = GFFormsModel::get_field( $form, $value );

			// If the selected field is not an address field, skip it.
			if ( GFFormsModel::get_input_type( $address_field ) !== 'address' ) {
				continue;
			}

			// Get the address field ID.
			$field_id = $address_field->id;

			// If any of the fields are empty, skip it.
			if ( rgblank( $entry[ $field_id . '.1' ] ) || rgblank( $entry[ $field_id . '.3' ] ) || rgblank( $entry[ $field_id . '.4' ] ) || rgblank( $entry[ $field_id . '.5' ] ) ) {
				continue;
			}

			// If looking for existing address, remove destroy flag if it exists.
			if ( $existing && ! empty( $person['addresses'] ) && $this->exists_in_array( $person['addresses'], 'address_1', $entry[ $field_id . '.1' ] ) ) {

				// Loop through person addresses and remove destroy flag.
				foreach ( $person['addresses'] as &$address ) {
					if ( $address['address_1'] === $entry[ $field_id . '.1' ] ) {
						unset( $address['_destroy'] );
					}
				}

			} else {

				// Prepare the field label.
				$label = str_replace( 'address_', '', $key );

				// Add the address to person.
				$person['addresses'][] = array(
					'address_1'   => $entry[ $field_id . '.1' ],
					'address_2'   => $entry[ $field_id . '.2' ],
					'city'        => $entry[ $field_id . '.3' ],
					'state'       => $entry[ $field_id . '.4' ],
					'postal_code' => $entry[ $field_id . '.5' ],
					'country'     => $entry[ $field_id . '.6' ],
					'label'       => $label,
					'primary'     => ( 'main' == $label && ! $this->primary_data_exists( $person['addresses'] ) ) ? true : false
				);

			}

		}

		return $person;

	}

	/**
	 * Add company data to person object.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person   Person object.
	 * @param array $feed     Feed object.
	 * @param array $entry    Entry object.
	 * @param array $form     Form object.
	 * @param bool  $existing Look for existing company before adding.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFAddOn::log_error()
	 * @uses GFBatchbook::exists_in_array()
	 * @uses GFFeedAddOn::add_feed_error()
	 * @uses GF_Batchbook_API::create_company()
	 * @uses GF_Batchbook_API::get_companies_by_name()
	 *
	 * @return array
	 */
	public function add_person_company_data( $person, $feed, $entry, $form, $existing = false ) {

		// Get mapped custom fields.
		$custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		// If company field is not mapped, exit.
		if ( ! rgar( $custom_fields, 'company' ) ) {
			return $person;
		}

		// Get company name.
		$company_name = $this->get_field_value( $form, $entry, $custom_fields['company'] );

		// If the company name is empty, exit.
		if ( rgblank( $company_name ) ) {
			return $person;
		}

		// Set create company flag.
		$create_company = true;

		try {

			// Search for existing companies.
			$companies = $this->api->get_companies_by_name( $company_name );

		} catch ( Exception $e ) {

			// Log that search could not be completed.
			$this->log_error( __METHOD__ . '(): Unable to search for existing companies; ' . $e->getMessage() );

			return $person;

		}

		// If companies were found, look for exact match.
		if ( ! empty( $companies ) ) {

			// Loop through companies.
			foreach ( $companies as $company ) {

				// If company name does not match, skip it.
				if ( $company['name'] !== $company_name ) {
					continue;
				}

				// Set company ID.
				$company_id = $company['id'];

				// Disable company creation.
				$create_company = false;

				break;

			}

		}


		// Create company if flag enabled.
		if ( $create_company ) {

			try {

				// Prepare company.
				$company = array( 'name' => $company_name );

				// Create company.
				$company = $this->api->create_company( $company );

				// Log that company was created.
				$this->log_debug( __METHOD__ . '(): Company #' . $company['id'] . ' created.' );

				// Set company id.
				$company_id = $company['id'];

			} catch ( Exception $e ) {

				// Log that company could not be created.
				$this->add_feed_error( sprintf( esc_html__( 'Company could not be created. %s', 'gravityformsbatchbook' ), $e->getMessage() ), $feed, $entry, $form );

				return $person;

			}

		}

		// If company ID is not set, exit.
		if ( empty( $company_id ) || ! isset( $company_id ) ) {
			return $person;
		}

		// If looking for existing company, remove destroy flag if it exists.
		if ( $existing && ! empty( $person['company_affiliations'] ) && $this->exists_in_array( $person['company_affiliations'], 'company_id', $company_id ) ) {

			// Loop through person company affiliations and remove destroy flag.
			foreach ( $person['company_affiliations'] as &$_company ) {
				if ( $_company['company_id'] === $company_id ) {
					unset( $_company['_destroy'] );
				}
			}

		} else {

			// Add the company to person.
			$person['company_affiliations'][] = array(
				'company_id' => $company_id,
				'current'    => true,
				'job_title'  => rgar( $custom_fields, 'title' ) ? $this->get_field_value( $form, $entry, $custom_fields['title'] ) : '',
			);

		}

		return $person;

	}

	/**
	 * Add custom field data to person object.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person   Person object.
	 * @param array $feed     Feed object.
	 * @param array $entry    Entry object.
	 * @param array $form     Form object.
	 * @param bool  $existing Look for existing website before adding.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 *
	 * @return array
	 */
	public function add_person_custom_field_data( $person, $feed, $entry, $form, $existing = false ) {

		// Get mapped custom fields.
		$custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		// Loop through custom fields.
		foreach ( $custom_fields as $key => $value ) {

			// If this is not a custom field, skip it.
			if ( strpos( $key, 'custom_field_' ) !== 0 ) {
				continue;
			}

			// Get the field value.
			$field_value = $this->get_field_value( $form, $entry, $value );

			// If the field value is blank, skip it.
			if ( rgblank( $field_value ) ) {
				continue;
			}

			// Explode field key.
			$exploded_key = explode( '_', $key );

			// Get custom field set and definition IDs
			$set_id        = $exploded_key[2];
			$definition_id = $exploded_key[3];

			// If looking for existing custom field, remove destroy flag if it exists.
			if ( $existing && ! empty( $person['cf_records'] ) ) {

				// Set found flags.
				$found_field_set = $found_field = false;

				// Loop through person custom fields.
				foreach ( $person['cf_records'] as &$cf_record ) {

					if ( $cf_record['custom_field_set_id'] == $set_id ) {

						$found_field_set = true;

						foreach ( $cf_record['custom_field_values'] as &$custom_field ) {

							if ( $custom_field['custom_field_definition_id'] == $definition_id ) {

								unset( $custom_field['_destroy'] );

								$custom_field['text_value'] = $field_value;

							}

						}

					}

				}

				// If custom field was not found, add it.
				if ( ! $found_field_set ) {

					$person['cf_records'][] = array(
						'custom_field_set_id' => $set_id,
						'custom_field_values' => array(
							array(
								'custom_field_definition_id' => $definition_id,
								'text_value'                 => $field_value,
							),
						),
					);

				}

			} else {

				if ( empty( $person['cf_records'] ) ) {

					$person['cf_records'][] = array(
						'custom_field_set_id' => $set_id,
						'custom_field_values' => array(
							array(
								'custom_field_definition_id' => $definition_id,
								'text_value'                 => $field_value,
							),
						),
					);

				} else {

					$found_field_set = false;

					foreach ( $person['cf_records'] as &$cf_record ) {

						if ( $cf_record['custom_field_set_id'] == $set_id ) {

							$found_field_set = true;

							$cf_record['custom_field_values'][] = array(
								'custom_field_definition_id' => $definition_id,
								'text_value'                 => $field_value,
							);

						}

					}

					if ( ! $found_field_set ) {

						$person['cf_records'][] = array(
							'custom_field_set_id' => $set_id,
							'custom_field_values' => array(
								array(
									'custom_field_definition_id' => $definition_id,
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
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person   Person object.
	 * @param array $feed     Feed object.
	 * @param array $entry    Entry object.
	 * @param array $form     Form object.
	 * @param bool  $existing Look for existing website before adding.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFBatchbook::exists_in_array()
	 *
	 * @return array
	 */
	public function add_person_email_data( $person, $feed, $entry, $form, $existing = false ) {

		// Get mapped custom fields.
		$custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		// Loop through custom fields.
		foreach ( $custom_fields as $key => $value ) {

			// If this is not an email address custom field, skip it.
			if ( strpos( $key, 'email_' ) !== 0 ) {
				continue;
			}

			// Get the email address.
			$email_address = $this->get_field_value( $form, $entry, $value );

			// If the website address is blank, skip it.
			if ( rgblank( $email_address ) ) {
				continue;
			}

			// If looking for existing email address, remove destroy flag if it exists.
			if ( $existing && ! empty( $person['emails'] ) && $this->exists_in_array( $person['emails'], 'address', $email_address ) ) {

				// Loop through person email addresses and remove destroy flag.
				foreach ( $person['emails'] as &$email ) {
					if ( $email['address'] === $email_address ) {
						unset( $email['_destroy'] );
					}
				}

			} else {

				// Prepare the field label.
				$label = str_replace( 'email_', '', $key );

				// Add the email address to person.
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
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person   Person object.
	 * @param array $feed     Feed object.
	 * @param array $entry    Entry object.
	 * @param array $form     Form object.
	 * @param bool  $existing Look for existing phone number before adding.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFBatchbook::exists_in_array()
	 * @uses GFBatchbook::primary_data_exists()
	 *
	 * @return array
	 */
	public function add_person_phone_data( $person, $feed, $entry, $form, $existing = false ) {

		// Get mapped custom fields.
		$custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		// Loop through custom fields.
		foreach ( $custom_fields as $key => $value ) {

			// If this is not a phone number custom field, skip it.
			if ( strpos( $key, 'phone_' ) !== 0 ) {
				continue;
			}

			// Get the phone number.
			$phone_number = $this->get_field_value( $form, $entry, $value );

			// If the phone number is blank, skip it.
			if ( rgblank( $phone_number ) ) {
				continue;
			}

			// If looking for existing phone number, remove destroy flag if it exists.
			if ( $existing && ! empty( $person['phones'] ) && $this->exists_in_array( $person['phones'], 'number', $phone_number ) ) {

				// Loop through person phone numbers and remove destroy flag.
				foreach ( $person['phones'] as &$phone ) {
					if ( $phone['number'] === $phone_number ) {
						unset( $phone['_destroy'] );
					}
				}

			} else {

				// Prepare the field label.
				$label = str_replace( 'phone_', '', $key );

				// Add the phone number to person.
				$person['phones'][] = array(
					'number'  => $phone_number,
					'label'   => $label,
					'primary' => ( 'main' == $label && ! $this->primary_data_exists( $person['phones'] ) ) ? true : false
				);

			}

		}

		return $person;

	}

	/**
	 * Add website data to person object.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person   Person object.
	 * @param array $feed     Feed object.
	 * @param array $entry    Entry object.
	 * @param array $form     Form object.
	 * @param bool  $existing Look for existing website before adding.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFBatchbook::exists_in_array()
	 * @uses GFBatchbook::primary_data_exists()
	 *
	 * @return array
	 */
	public function add_person_website_data( $person, $feed, $entry, $form, $existing = false ) {

		// Get mapped custom fields.
		$custom_fields = $this->get_dynamic_field_map_fields( $feed, 'person_custom_fields' );

		// Loop through custom fields.
		foreach ( $custom_fields as $key => $value ) {

			// If this is not a website custom field, skip it.
			if ( strpos( $key, 'website_' ) !== 0 ) {
				continue;
			}

			// Get the website address.
			$website_address = $this->get_field_value( $form, $entry, $value );

			// If the website address is blank, skip it.
			if ( rgblank( $website_address ) ) {
				continue;
			}

			// If looking for existing website, remove destroy flag if it exists.
			if ( $existing && ! empty( $person['websites'] ) && $this->exists_in_array( $person['websites'], 'address', $website_address ) ) {

				// Loop through person websites and remove destroy flag.
				foreach ( $person['websites'] as &$website ) {
					if ( $website['address'] === $website_address ) {
						unset( $website['_destroy'] );
					}
				}

			} else {

				// Prepare the field label.
				$label = str_replace( 'website_', '', $key );

				// Add the website to person.
				$person['websites'][] = array(
					'address' => $website_address,
					'label'   => $label,
					'primary' => ( 'main' == $label && ! $this->primary_data_exists( $person['websites'] ) ) ? true : false
				);

			}

		}

		return $person;

	}

	/**
	 * Add tags to person object.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $person   Person object.
	 * @param array $feed     Feed object.
	 * @param array $entry    Entry object.
	 * @param array $form     Form object.
	 * @param bool  $existing Look for existing tag before adding.
	 *
	 * @uses GFBatchbook::exists_in_array()
	 * @uses GFCommon::replace_variables()
	 *
	 * @return array
	 */
	public function add_person_tags( $person, $feed, $entry, $form, $existing = true ) {

		// Initialize tags array.
		$tags = array();

		// Get mapped tags.
		if ( rgars( $feed, 'meta/person_tags' ) ) {

			// Replace merge tags.
			$tags = GFCommon::replace_variables( $feed['meta']['person_tags'], $form, $entry, false, false, false, 'text' );

			// Explode string.
			$tags = explode( ',', $tags );

		}

		/**
		 * Modify the tags to be assigned to a Batchbook person.
		 *
		 * @since 1.0
		 *
		 * @param array $tags  An array of tags to be assigned to the person.
		 * @param array $feed  The feed object.
		 * @param array $entry The entry object.
		 * @param array $form  The form object.
		 */
		$tags = gf_apply_filters( array( 'gform_batchbook_tags', $form['id'] ), $tags, $feed, $entry, $form );

		// If no tags were found, return person.
		if ( empty( $tags ) ) {
			return $person;
		}

		// Loop through tags.
		foreach ( $tags as $tag ) {

			// If looking for existing tag, remove destroy flag if it exists.
			if ( $existing && ! empty( $person['tags'] ) && $this->exists_in_array( $person['tags'], 'name', $tag ) ) {

				// Loop through person tags and remove destroy flag.
				foreach ( $person['tags'] as &$_tag ) {
					if ( $_tag['name'] === $tag ) {
						unset( $_tag['_destroy'] );
					}
				}

			} else {

				// Add new tag.
				$person['tags'][] = array( 'name' => trim( $tag ) );

			}

		}

		return $person;

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes Batchbook API if credentials are valid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GF_Batchbook_API::get_users()
	 *
	 * @return bool
	 */
	public function initialize_api() {

		// If API library is already loaded, return.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Load the Batchbook API library.
		if ( ! class_exists( 'GF_Batchbook_API' ) ) {
			require_once 'includes/class-gf-batchbook-api.php';
		}

		// Get plugin settings.
		$settings = $this->get_plugin_settings();

		// If any of the account information fields are empty, return.
		if ( rgblank( $settings['account_url'] ) || rgblank( $settings['api_token'] ) ) {
			return null;
		}

		// Log that we are validating the API credentials.
		$this->log_debug( __METHOD__ . "(): Validating API info." );

		try {

			// Initialize a new Batchbook API object.
			$batchbook = new GF_Batchbook_API( $settings['account_url'], $settings['api_token'] );

			// Run a test request.
			$batchbook->get_users();

			// Assign Batchbook object to this instance.
			$this->api = $batchbook;

			// Log that test passed.
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

			return true;

		} catch ( Exception $e ) {

			// Log that test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );

			return false;

		}

	}

	/**
	 * Checks validity of Batchbook account URL.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $account_url Batchbook account URL.
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GF_Batchbook_API::validate_account_url()
	 *
	 * @return bool
	 */
	public function validate_account_url( $account_url = '' ) {

		// Load the Batchbook API library.
		if ( ! class_exists( 'GF_Batchbook_API' ) ) {
			require_once 'includes/class-gf-batchbook-api.php';
		}

		// If the account URL is empty, return null.
		if ( rgblank( $account_url ) ) {
			return null;
		}

		// Log that we are validating the account URL.
		$this->log_debug( __METHOD__ . "(): Validating account URL: {$account_url}.batchbook.com" );

		try {

			// Initialize a new Batchbook API object.
			$batchbook = new GF_Batchbook_API( $account_url );

			// Run an account URL test.
			$batchbook->validate_account_url();

			// Log that test passed.
			$this->log_debug( __METHOD__ . '(): Account URL is valid.' );

			return true;

		} catch ( Exception $e ) {

			// Log that test failed.
			$this->log_error( __METHOD__ . '(): Account URL is invalid.' );

			return false;

		}

	}

	/**
	 * Determine if value exists in multidimensional array.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $array Array to search.
	 * @param string $key   Key to search for.
	 * @param string $value Value to match against.
	 *
	 * @return bool
	 */
	public function exists_in_array( $array = array(), $key, $value ) {

		foreach ( $array as $item ) {

			if ( ! isset( $item[ $key ] ) ) {
				continue;
			}

			if ( $item[ $key ] == $value ) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Determine if primary value is set for data array.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $data Array to search.
	 *
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

}
