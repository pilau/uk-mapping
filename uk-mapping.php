<?php

/**
 * UK Mapping
 *
 * @package   UK_Mapping
 * @author    Steve Taylor
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:			UK Mapping
 * Description:			A WordPress plugin for managing UK postcodes and local authority areas data.
 * Version:				0.1
 * Author:				Steve Taylor
 * Text Domain:			pilau-uk-mapping-locale
 * License:				GPL-2.0+
 * License URI:			http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:			/lang
 * GitHub Plugin URI:	https://github.com/pilau/uk-mapping
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-uk-mapping.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'Pilau_UK_Mapping', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Pilau_UK_Mapping', 'deactivate' ) );

Pilau_UK_Mapping::get_instance();