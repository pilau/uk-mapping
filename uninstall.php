<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package   UK_Mapping
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */

// If uninstall, not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
