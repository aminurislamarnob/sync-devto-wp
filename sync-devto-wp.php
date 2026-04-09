<?php
/**
 * Plugin Name: Sync Dev.to to WordPress
 * Plugin URI:  https://github.com/your-repo/sync-devto-wp
 * Description: Import your Dev.to articles into WordPress posts with duplicate prevention and smart updates.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://yourwebsite.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sync-devto-wp
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SDWP_VERSION', '1.0.0' );
define( 'SDWP_PLUGIN_FILE', __FILE__ );
define( 'SDWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SDWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SDWP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

spl_autoload_register( function ( string $class ) {
	$prefix    = 'SyncDevtoWP\\';
	$base_dir  = SDWP_PLUGIN_DIR . 'includes/';
	$len       = strlen( $prefix );

	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

function sdwp_instance(): SyncDevtoWP\Plugin {
	return SyncDevtoWP\Plugin::instance();
}

add_action( 'plugins_loaded', 'sdwp_instance' );

register_activation_hook( __FILE__, function () {
	$defaults = array(
		'sdwp_api_key'       => '',
		'sdwp_post_status'   => 'draft',
		'sdwp_post_author'   => 0,
		'sdwp_import_scope'  => 'published',
	);

	foreach ( $defaults as $key => $value ) {
		if ( get_option( $key ) === false ) {
			add_option( $key, $value );
		}
	}
} );

register_deactivation_hook( __FILE__, function () {
	delete_transient( 'sdwp_import_lock' );
} );
