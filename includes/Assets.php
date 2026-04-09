<?php

namespace WeLabs\DevtoWpImporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	public function enqueue_admin_assets( $hook ) {
		if ( $hook !== 'tools_page_devto-wp-importer' ) {
			return;
		}

		wp_enqueue_style(
			'devto_wp_importer_admin',
			DEVTO_WP_IMPORTER_PLUGIN_ADMIN_ASSET . '/css/importer.css',
			[],
			DEVTO_WP_IMPORTER_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'devto_wp_importer_admin',
			DEVTO_WP_IMPORTER_PLUGIN_ADMIN_ASSET . '/js/importer.js',
			[ 'jquery' ],
			DEVTO_WP_IMPORTER_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'devto_wp_importer_admin',
			'devtoWpImporterAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'devto_wp_importer_import_nonce' ),
				'i18n'    => [
					'importing'    => __( 'Importing articles...', 'devto-wp-importer' ),
					'importDone'   => __( 'Import completed!', 'devto-wp-importer' ),
					'importFailed' => __( 'Import failed. Please try again.', 'devto-wp-importer' ),
					'importLog'    => __( 'Import Log', 'devto-wp-importer' ),
					'inProgress'   => __( 'Import in progress... elapsed: %ds', 'devto-wp-importer' ),
					'confirm'      => __( 'Are you sure you want to start importing articles from Dev.to?', 'devto-wp-importer' ),
					'created'      => __( 'Created', 'devto-wp-importer' ),
					'updated'      => __( 'Updated', 'devto-wp-importer' ),
					'skipped'      => __( 'Skipped (unchanged)', 'devto-wp-importer' ),
					'failed'       => __( 'Failed', 'devto-wp-importer' ),
				],
			]
		);
	}
}
