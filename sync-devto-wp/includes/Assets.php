<?php

declare( strict_types=1 );

namespace SyncDevtoWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( $hook !== 'tools_page_sdwp-importer' ) {
			return;
		}

		wp_enqueue_style(
			'sdwp-admin',
			SDWP_PLUGIN_URL . 'assets/admin/css/importer.css',
			array(),
			SDWP_VERSION
		);

		wp_enqueue_script(
			'sdwp-admin',
			SDWP_PLUGIN_URL . 'assets/admin/js/importer.js',
			array( 'jquery' ),
			SDWP_VERSION,
			true
		);

		wp_localize_script( 'sdwp-admin', 'sdwpAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'sdwp_import_nonce' ),
			'i18n'    => array(
				'importing'    => __( 'Importing articles...', 'sync-devto-wp' ),
				'importDone'   => __( 'Import completed!', 'sync-devto-wp' ),
				'importFailed' => __( 'Import failed. Please try again.', 'sync-devto-wp' ),
				'confirm'      => __( 'Are you sure you want to start importing articles from Dev.to?', 'sync-devto-wp' ),
				'created'      => __( 'Created', 'sync-devto-wp' ),
				'updated'      => __( 'Updated', 'sync-devto-wp' ),
				'skipped'      => __( 'Skipped (unchanged)', 'sync-devto-wp' ),
				'failed'       => __( 'Failed', 'sync-devto-wp' ),
			),
		) );
	}
}
