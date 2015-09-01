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
	 * The raw area codes table name (without prefix)
	 *
	 * @since    0.1
	 * @var      string
	 */
	protected $table_area_codes_raw = 'pukm_area_codes_raw';

	/**
	 * The raw postcodes table name (without prefix)
	 *
	 * @since    0.1
	 * @var      string
	 */
	protected $table_postcodes_raw = 'pukm_postcodes_raw';

	/**
	 * Is the raw data present?
	 *
	 * @since    0.1
	 * @var      boolean
	 */
	protected $raw_data_present = false;

	/**
	 * Code type equivalents
	 *
	 * @since    0.1
	 * @var      array
	 */
	protected $code_type_equivalents = array(
		'cty'	=> array( 'cty' ),
		'dis'	=> array( 'dis', 'lbo', 'mtd', 'uta' ),
		'diw'	=> array( 'diw', 'lbw', 'mtw', 'utw' ),
	);

	/**
	 * Code type names
	 *
	 * @since    0.1
	 * @var      array
	 */
	protected $code_type_names = array(
		'cty'	=> 'County',
		'dis'	=> 'District',
		'diw'	=> 'District Ward',
		'lbo'	=> 'London Borough',
		'lbw'	=> 'London Borough Ward',
		'mtd'	=> 'Metropolitan District',
		'mtw'	=> 'Metropolitan District Ward',
		'uta'	=> 'Unitary Authority',
		'ute'	=> 'Unitary Authority Electoral Division',
		'utw'	=> 'Unitary Authority Ward'
	);

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

		// Global init
		add_action( 'init', array( $this, 'init' ) );

		// Admin init
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Add the admin page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menus' ) );
		//add_action( 'admin_init', array( $this, 'process_plugin_admin_page' ) );

		// Load admin style sheet and JavaScript.
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Other hooks
		//add_action( 'init', array( $this, 'register_custom_post_types' ), 0 );
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
	 * Register custom post types
	 *
	 * @since	0.1
	 */
	public function register_custom_post_types() {


	}

	/**
	 * Register custom taxonomies
	 *
	 * @since	0.1
	 */
	public function register_custom_taxonomies() {


	}

}