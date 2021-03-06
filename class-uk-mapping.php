<?php
/**
 * Sources and Footnotes
 *
 * @package   UK_Mapping
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */

/**
 * Plugin class
 *
 * @package UK_Mapping
 * @author  Steve Taylor
 */
class Pilau_UK_Mapping {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.1
	 * @var     string
	 */
	protected $version = '0.1';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    0.1
	 * @var      string
	 */
	protected $plugin_slug = 'pukm';

	/**
	 * Instance of this class.
	 *
	 * @since    0.1
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    0.1
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * The plugin's settings.
	 *
	 * @since    0.1
	 * @var      array
	 */
	protected $settings = null;

	/**
	 * The raw area codes table name
	 *
	 * @since    0.1
	 * @var      string
	 */
	protected $table_area_codes_raw = 'pukm_area_codes_raw';

	/**
	 * The raw postcodes table name
	 *
	 * @since    0.1
	 * @var      string
	 */
	protected $table_postcodes_raw = 'pukm_postcodes_raw';

	/**
	 * The postcode sectors table name
	 *
	 * @since    0.1
	 * @var      string
	 */
	protected $table_postcode_sectors = 'pukm_postcode_sectors';

	/**
	 * Is the raw data present?
	 *
	 * @since    0.1
	 * @var      boolean
	 */
	protected $raw_data_present = false;

	/**
	 * Plugin options
	 *
	 * @since    0.1
	 * @var      array
	 */
	protected $options = array();

	/**
	 * Code type equivalents
	 *
	 * These describe which code types might be found in the 3 area code columns
	 * in the raw postcodes dataset
	 *
	 * @since    0.1
	 * @var      array
	 */
	protected $code_type_equivalents = array(
		'CTY'	=> array( 'CTY' ),
		'DIS'	=> array( 'DIS', 'LBO', 'MTD', 'UTA' ),
		'DIW'	=> array( 'DIW', 'LBW', 'MTW', 'UTW' ),
	);

	/**
	 * Code type names
	 *
	 * @since    0.1
	 * @var      array
	 */
	protected $code_type_names = array(
		'CTY'	=> 'County',
		'DIS'	=> 'District',
		'DIW'	=> 'District Ward',
		'LBO'	=> 'London Borough',
		'LBW'	=> 'London Borough Ward',
		'MTD'	=> 'Metropolitan District',
		'MTW'	=> 'Metropolitan District Ward',
		'UTA'	=> 'Unitary Authority',
		'UTE'	=> 'Unitary Authority Electoral Division',
		'UTW'	=> 'Unitary Authority Ward'
	);

	/**
	 * Postcode levels
	 *
	 * @link	https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom#Formatting
	 * @since    0.1
	 * @var      array
	 */
	protected $postcode_levels = array( 'area', 'district', 'sector', 'unit' );

	/**
	 * KML files to import
	 *
	 * @since    0.1
	 * @var      array
	 */
	protected $import_kml = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1
	 */
	private function __construct() {

		// Load the options
		// This is REQUIRED to initialize the options when the plugin is loaded!
		$this->load_options();

		// Global init
		add_action( 'init', array( $this, 'init' ) );

		// Admin init
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Add the admin pages and menu items
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menus' ) );
		add_action( 'admin_init', array( $this, 'process_plugin_admin_page' ) );
		//add_action( 'admin_init', array( $this, 'process_kml_import' ) );
		add_action( 'admin_init', array( $this, 'populate_data' ) );

		// Load admin style sheet and JavaScript.
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Other hooks
		add_action( 'init', array( $this, 'register_custom_post_types' ), 0 );
		//add_action( 'init', array( $this, 'register_custom_taxonomies' ), 0 );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		// This is a trick used to get around the difficulty of adding hooks and calling non-static methods here
		// The actual activation stuff is done in admin_init
		// @link http://codex.wordpress.org/Function_Reference/register_activation_hook#Process_Flow
		add_option( __CLASS__ . '_activating', 1 );

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

	}

	/**
	 * Initialize
	 *
	 * @since    0.1
	 */
	public function init() {

		// Load plugin text domain
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Admin stuff that needs to happen early, e.g. before admin_menu
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			global $wpdb;

			// Is the raw data present?
			$tables = $wpdb->get_col( "SHOW TABLES" );
			$this->raw_data_present = in_array( $this->table_area_codes_raw, $tables ) && in_array( $this->table_postcodes_raw, $tables );

		}

	}

	/**
	 * Initialize admin
	 *
	 * @since	0.1
	 * @return	void
	 */
	public function admin_init() {

		// Any activation stuff to do?
		if ( get_option( __CLASS__ . '_activating' ) ) {

			// Clear activation flag
			delete_option( __CLASS__ . '_activating' );

		}

	}

	/**
	 * Loads the plugin options from the database
	 *
	 * @since	0.1
	 * @uses	update_option()
	 * @return	array
	 */
	private function load_options() {

		// Are the options present?
		if ( ! $options = get_option( $this->plugin_slug . '_options' ) ) {

			// Set defaults
			$options = array(
				'postcode_post_type'	=> 'none'
			);

			// Save to database
			$this->update_options( $options );

		}

		// Set options
		$this->options = $options;

	}

	/**
	 * Update options
	 *
	 * @since	0.1
	 * @uses	update_option()
	 * @param	array		$options
	 * @return	bool
	 */
	private function update_options( $options ) {
		return update_option( $this->plugin_slug . '_options', $options );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, array() ) ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.1
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, array() ) ) {
			$script = defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ? plugins_url( 'js/admin.js', __FILE__ ) : plugins_url( 'js/admin.min.js', __FILE__ );
			wp_enqueue_script( $this->plugin_slug . '-admin-script', $script, array( 'jquery' ), $this->version );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.1
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1
	 */
	public function enqueue_scripts() {
		$script = defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ? plugins_url( 'js/public.js', __FILE__ ) : plugins_url( 'js/public.min.js', __FILE__ );
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', $script, array( 'jquery', 'jquery-ui-tooltip' ), $this->version );
	}

	/**
	 * Register the administration menus for this plugin.
	 *
	 * @since    0.1
	 */
	public function add_plugin_admin_menus() {

		// Main page
		$this->plugin_screen_hook_suffix = add_menu_page(
			__( 'UK mapping', $this->plugin_slug ),
			__( 'UK mapping', $this->plugin_slug ),
			'update_core',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-location-alt',
			81
		);

		// Raw data queries
		if ( $this->raw_data_present ) {
			add_submenu_page(
				$this->plugin_slug,
				__( 'Raw data', $this->plugin_slug ),
				__( 'Raw data', $this->plugin_slug ),
				'update_core',
				$this->plugin_slug . '_raw_data',
				array( $this, 'display_plugin_raw_page' )
			);
		}

	}

	/**
	 * Render the admin page for this plugin.
	 *
	 * @since    0.1
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Render the raw data page for this plugin.
	 *
	 * @since    0.1
	 */
	public function display_plugin_raw_page() {
		$this->import_kml = glob( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'import-data/*.kml' );
		include_once( 'views/raw-data.php' );
	}

	/**
	 * Process the admin page for this plugin.
	 *
	 * @since    0.1
	 */
	public function process_plugin_admin_page() {

		// Submitted?
		if ( isset( $_POST[ $this->plugin_slug . '_admin_page_admin_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_admin_page', $this->plugin_slug . '_admin_page_admin_nonce' ) ) {

			// Gather options
			$options = array();
			$options['postcode_post_type']			= in_array( $_POST[ $this->plugin_slug . '_postcode_post_type' ], array_keys( $this->custom_post_type_args() ) ) && strpos( $_POST[ $this->plugin_slug . '_postcode_post_type' ], 'postcode' ) ? $_POST[ $this->plugin_slug . '_postcode_post_type' ] : 'none';

			// Update options
			$this->update_options( $options );

			// Redirect
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_slug . '&done=1' ) );

		}

	}

	/**
	 * Process the KML raw data import
	 *
	 * @since    0.1
	 */
	public function process_kml_import() {

		// Submitted?
		if ( isset( $_POST[ $this->plugin_slug . '_kml_import_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_kml_import', $this->plugin_slug . '_kml_import_nonce' ) ) {



			// Redirect
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_slug . '_raw_data' . '&kml=1' ) );

		}

	}

	/**
	 * Populate data
	 *
	 * @since    0.1
	 */
	public function populate_data() {
		global $wpdb;

		// Submitted?
		if ( isset( $_POST[ $this->plugin_slug . '_populate_data_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_populate_data', $this->plugin_slug . '_populate_data_nonce' ) ) {

			// Init
			$post_type = $this->options['postcode_post_type'];
			$postcode_col = str_replace( 'pukm_', '', $post_type );
			if ( $postcode_col == 'postcode_unit' ) {
				$postcode_col = 'postcode';
			}
			$la_type = $_REQUEST[ $this->plugin_slug . '-populate-la-type' ];
			$la_type_col = $la_type . '_code';
			$prefix = $_REQUEST[ $this->plugin_slug . '-populate-custom-field-prefix' ];
			$field_code_type = $prefix . $_REQUEST[ $this->plugin_slug . '-populate-custom-field-name-postcode-la-code-type' ];
			$field_code = $prefix . $_REQUEST[ $this->plugin_slug . '-populate-custom-field-name-postcode-la-code' ];
			$field_other_las = $prefix . $_REQUEST[ $this->plugin_slug . '-populate-custom-field-name-other-las' ];
			/*
			echo '<pre>'; print_r( $post_type ); echo '</pre>';
			echo '<pre>'; print_r( $postcode_col ); echo '</pre>';
			echo '<pre>'; print_r( $la_type ); echo '</pre>';
			echo '<pre>'; print_r( $la_type_col ); echo '</pre>';
			echo '<pre>'; print_r( $prefix ); echo '</pre>';
			echo '<pre>'; print_r( $field_code_type ); echo '</pre>';
			echo '<pre>'; print_r( $field_code ); echo '</pre>'; exit;
			echo '<pre>'; print_r( $field_other_las ); echo '</pre>'; exit;
			*/

			// Get raw data
			$postcode_data = $wpdb->get_results("
				SELECT		DISTINCT $postcode_col as postcode, $la_type_col as la_code
				FROM		$this->table_postcodes_raw
				WHERE		( $la_type_col IS NOT NULL AND $la_type_col <> '' )
				ORDER BY	LENGTH( $postcode_col ), $postcode_col
			");
			/*
			echo '<pre>';
			foreach ( $postcode_data as $postcode_datum ) {
				echo $postcode_datum->postcode . "\t\t" .$postcode_datum->la_code . "\n";
			}
			echo '</pre>'; exit;
			*/

			// Go through and create postcode posts
			// Also log other (straddled) codes
			$other_straddled_la_codes = array();
			foreach ( $postcode_data as $postcode_datum ) {

				if ( isset( $other_straddled_la_codes[ $postcode_datum->postcode ] ) ) {

					// This postcode's already been done - log this extra LA code that it straddles
					$other_straddled_la_codes[ $postcode_datum->postcode ][] = $postcode_datum->la_code;

				} else {

					// Create the postcode post
					$postcode_post_id = $this->create_postcode_post(
						$postcode_datum->postcode,
						$field_code,
						$postcode_datum->la_code,
						$field_code_type,
						$la_type
					);

					if ( ! is_wp_error( $postcode_post_id ) && $postcode_post_id ) {

						// Create an empty array for potential straddled LA codes
						$other_straddled_la_codes[ $postcode_datum->postcode ] = array();

					}

				}
			}

			// And again for DIS column if CTY was the aim
			if ( $la_type == 'CTY' ) {

				// Get raw data
				$postcode_data = $wpdb->get_results("
					SELECT		DISTINCT $postcode_col as postcode, dis_code as la_code
					FROM		$this->table_postcodes_raw
					WHERE		( cty_code IS NULL OR cty_code = '' )
					ORDER BY	LENGTH( $postcode_col ), $postcode_col
				");

				// Go through and create postcode posts
				// Also log other (straddled) codes
				foreach ( $postcode_data as $postcode_datum ) {
					if ( isset( $other_straddled_la_codes[ $postcode_datum->postcode ] ) ) {
						$other_straddled_la_codes[ $postcode_datum->postcode ][] = $postcode_datum->la_code;
					} else {
						$postcode_post_id = $this->create_postcode_post(
							$postcode_datum->postcode,
							$field_code,
							$postcode_datum->la_code,
							$field_code_type,
							'DIS'
						);
						if ( ! is_wp_error( $postcode_post_id ) && $postcode_post_id ) {
							$other_straddled_la_codes[ $postcode_datum->postcode ] = array();
						}
					}
				}

			}

			// Log straddled LA codes in custom fields
			if ( $other_straddled_la_codes ) {
				foreach ( $other_straddled_la_codes as $postcode => $straddled_la_codes ) {

					// Get the ID of the postcode
					$postcode_post = get_page_by_title(
						$postcode,
						OBJECT,
						$this->options['postcode_post_type']
					);

					if ( $postcode_post ) {
						update_post_meta( $postcode_post->ID, $field_other_las, $straddled_la_codes );
					}

				}
			}

			// Redirect
			wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_slug . '_raw_data' . '&populate=1' ) );

		}

	}

	/**
	 * Create a postcode post
	 *
	 * @since	0.1
	 * @param	string	$postcode
	 * @param	string	$la_code_field
	 * @param	string	$la_code
	 * @param	string	$la_code_type_field
	 * @param	string	$la_code_type
	 * @return	mixed
	 */
	public function create_postcode_post( $postcode, $la_code_field, $la_code, $la_code_type_field, $la_code_type ) {

		// Create post
		$post_id = wp_insert_post( array(
			'post_type'		=> $this->options['postcode_post_type'],
			'post_title'	=> $postcode,
			'post_status'	=> 'publish'
		) );

		if ( ! is_wp_error( $post_id ) && $post_id ) {

			// Add metadata
			update_post_meta( $post_id, $la_code_field, $la_code );
			update_post_meta( $post_id, $la_code_type_field, strtoupper( $la_code_type ) );

		}

		return $post_id;
	}

	/**
	 * Return custom post type arguments (filtered)
	 *
	 * @since	0.1
	 */
	public function custom_post_type_args() {
		$post_type_args = array();

		foreach ( $this->postcode_levels as $postcode_level ) {

			$post_type_args['pukm_postcode_' . $postcode_level] = apply_filters( 'pukm_post_type_args_postcode_' . $postcode_level, array(
				'label'					=> 'postcode ' . $postcode_level . 's',
				'labels'				=> array(
					'name'					=> 'Postcode ' . $postcode_level . 's',
					'singular_name'			=> 'Postcode ' . $postcode_level . '',
					'menu_name'				=> 'Postcode ' . $postcode_level . 's',
					'name_admin_bar'		=> 'Postcode ' . $postcode_level . '',
					'add_new'				=> 'Add New',
					'add_new_item'			=> 'Add New Postcode ' . $postcode_level . '',
					'new_item'				=> 'New Postcode ' . $postcode_level . '',
					'edit_item'				=> 'Edit Postcode ' . $postcode_level . '',
					'view_item'				=> 'View Postcode ' . $postcode_level . '',
					'all_items'				=> 'All Postcode ' . $postcode_level . 's',
					'search_items'			=> 'Search Postcode ' . $postcode_level . 's',
					'parent_item_colon'		=> 'Parent Postcode ' . $postcode_level . 's:',
					'not_found'				=> 'No Postcode ' . $postcode_level . 's found.',
					'not_found_in_trash'	=> 'No Postcode ' . $postcode_level . 's found in Trash.'
				),
				'public'				=> false,
				'publicly_queryable'	=> false,
				'show_ui'				=> true,
				'show_in_nav_menus'		=> false,
				'show_in_menu'			=> true,
				'show_in_admin_bar'		=> false,
				'menu_position'			=> 90,
				'menu_icon'				=> 'dashicons-location-alt', // @link https://developer.wordpress.org/resource/dashicons/
				'query_var'				=> false,
				'rewrite'				=> false,
				'capability_type'		=> 'postcode_pukm_' . $postcode_level,
				'map_meta_cap'			=> false,
				'capabilities' => array(
					'publish_posts'			=> 'publish_pukm_postcode_' . $postcode_level . 's',
					'edit_posts'			=> 'edit_pukm_postcode_' . $postcode_level . 's',
					'edit_others_posts'		=> 'edit_pukm_others_postcode_' . $postcode_level . 's',
					'delete_posts'			=> 'delete_pukm_postcode_' . $postcode_level . 's',
					'delete_others_posts'	=> 'delete_pukm_others_postcode_' . $postcode_level . 's',
					'read_private_posts'	=> 'read_pukm_private_postcode_' . $postcode_level . 's',
					'edit_post'				=> 'edit_pukm_postcode_sector',
					'delete_post'			=> 'delete_pukm_postcode_sector',
					'read_post'				=> 'read_pukm_postcode_sector',
				),
				'has_archive'			=> false,
				'hierarchical'			=> false,
				'supports'				=> array( 'title', 'custom-fields' ),
				'taxonomies'			=> array(),
			));

		}

		return $post_type_args;
	}

	/**
	 * Register custom post types
	 *
	 * @since	0.1
	 */
	public function register_custom_post_types() {

		// Only register the post types set to be registered
		$post_type_args = $this->custom_post_type_args();
		if ( $this->options[ 'postcode_post_type' ] != 'none' ) {
			register_post_type( $this->options[ 'postcode_post_type' ], $post_type_args[ $this->options[ 'postcode_post_type' ] ] );
		}

	}

	/**
	 * Register custom taxonomies
	 *
	 * @since	0.1
	 */
	public function register_custom_taxonomies() {


	}

	/*
	 * All functions for dealing with raw data are private and prefixed
	 **************************************************************************************/

	/**
	 * Check for postcodes straddling more than one local authority
	 *
	 * @since    0.1
	 * @param	string	$postcode_level
	 * @param	string	$la_type
	 * @return	array
	 */
	private function raw_check_postcode_straddle( $postcode_level = 'unit', $la_type = 'CTY' ) {
		global $wpdb;
		$results = array( 'straddling_postcodes' => array(), 'percentage_of_total' => null );

		// Only bother if raw data present
		if ( $this->raw_data_present ) {
			$la_type = preg_replace( '/[^A-Z]+/', '', strtoupper( $la_type ) );
			$la_type_col = strtolower( $la_type ) . '_code';
			$postcode_level_col = 'postcode';
			if ( $postcode_level != 'unit' ) {
				$postcode_level_col .= '_' . $postcode_level;
			}

			// Count number of postcodes at selected level that have more than one value for specified local authority type
			$results['straddling_postcodes'] = $wpdb->get_col("
				SELECT		$postcode_level_col
				FROM		$this->table_postcodes_raw
				WHERE		( $la_type_col IS NOT NULL AND $la_type_col <> '' )
				GROUP BY	$postcode_level_col
				HAVING		MIN( $la_type_col ) <> MAX( $la_type_col )
			");
			//echo '<pre>'; print_r( $results ); echo '</pre>';

			// If checking at county level, add in results at DIS level for those without a CTY (e.g. unitary authorities)
			if ( $la_type == 'CTY' ) {
				$results2 = $wpdb->get_col("
					SELECT		$postcode_level_col
					FROM		$this->table_postcodes_raw
					WHERE		( $la_type_col IS NULL OR $la_type_col = '' )
					GROUP BY	$postcode_level_col
					HAVING		MIN( 'dis_code' ) <> MAX( 'dis_code' )
				");
				//echo '<pre>'; print_r( $results2 ); echo '</pre>'; exit;
				if ( $results2 ) {
					$results['straddling_postcodes'] = array_merge( $results['straddling_postcodes'], $results2 );
				}
			}

			// Get total to calculate percentage
			$postcodes_total = $wpdb->get_var("
				SELECT	COUNT( DISTINCT $postcode_level_col )
				FROM	$this->table_postcodes_raw
			");
			$results['percentage_of_total'] = round( ( count( $results['straddling_postcodes'] ) / $postcodes_total ) * 100, 1 );

		}

		return $results;
	}

	/**
	 * From raw data, get local authority for postcode
	 *
	 * @since	0.1
	 * @param	string		$postcode
	 * @param	string		$la_type
	 * @param	bool		$strip_title	Strip off "County", "District", etc.?
	 * @return	array
	 */
	private function raw_postcode_to_local_authority( $postcode, $la_type = 'CTY', $strip_title = true ) {
		global $wpdb;
		$la_details = array();

		// Only bother if raw data present
		if ( $this->raw_data_present ) {
			$postcode = preg_replace( '/[^A-Z0-9]+/', '', strtoupper( $postcode ) );
			$la_type = preg_replace( '/[^A-Z]+/', '', strtoupper( $la_type ) );

			// If trying to get county, try to get "district" if there's no county (i.e. top-level authority is unitary or metropolitan)
			$attempt_authority_levels = array( $la_type );
			if ( $la_type == 'CTY' ) {
				$attempt_authority_levels[] = 'DIS';
			}
			foreach ( $attempt_authority_levels as $attempt_authority_level ) {

				// Build area code equivalents
				$area_code_equivalents = array();
				foreach ( $this->code_type_equivalents[ $attempt_authority_level ] as $code_type ) {
					$area_code_equivalents[] = ' ac.code_type = \'' . $code_type . '\'';
				}

				// Build query
				$sql = "
					SELECT	ac.code_type, ac.area_title
					FROM	$this->table_area_codes_raw ac, $this->table_postcodes_raw pc
					WHERE	pc.postcode LIKE '" . $postcode . "%'
					AND		pc." . strtolower( $attempt_authority_level ) . "_code	= ac.code
					AND		( " . implode( ' OR ', $area_code_equivalents ) . " )
				";

				// Do query
				$la_details = $wpdb->get_row( $sql );

				// Break if we've got results
				if ( $la_details ) {
					break;
				}

			}

			// Strip title?
			if ( $la_details->area_title && $strip_title ) {
				foreach ( array( 'The City of', 'County', 'District', 'Ward', 'London Boro' ) as $area_label ) {
					$la_details->area_title = preg_replace( '/\b' . $area_label . '\b/', '', $la_details->area_title );
				}
				$la_details->area_title = preg_replace( '/\(.+\)/', '', $la_details->area_title );
				$la_details->area_title = trim( $la_details->area_title );
			}

		}

		return $la_details;
	}

}