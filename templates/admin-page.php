<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$last_import = get_option( 'devto_wp_importer_last_import_result', null );
?>

<div class="wrap devto-wp-importer-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Dev.to Importer', 'devto-wp-importer' ); ?></h1>
	<hr class="wp-header-end">

	<div class="devto-wp-importer-card">
		<h2><?php esc_html_e( 'Settings', 'devto-wp-importer' ); ?></h2>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'devto_wp_importer_settings' );
			do_settings_sections( 'devto_wp_importer_settings' );
			submit_button( __( 'Save Settings', 'devto-wp-importer' ) );
			?>
		</form>
	</div>

	<div class="devto-wp-importer-card">
		<h2><?php esc_html_e( 'Import Articles', 'devto-wp-importer' ); ?></h2>
		<div class="devto-wp-importer-import-section">
			<p>
				<?php esc_html_e( 'Click the button below to import your Dev.to articles into WordPress. Existing articles will be updated if they have changed; no duplicates will be created.', 'devto-wp-importer' ); ?>
			</p>
			<button
				type="button"
				id="devto-wp-importer-import-btn"
				class="button button-primary button-hero"
				data-label="<?php esc_attr_e( 'Import Articles from Dev.to', 'devto-wp-importer' ); ?>"
			>
				<?php esc_html_e( 'Import Articles from Dev.to', 'devto-wp-importer' ); ?>
			</button>
			<span class="spinner" id="devto-wp-importer-spinner"></span>
		</div>

		<div id="devto-wp-importer-results" style="display:none;">
			<div id="devto-wp-importer-results-summary"></div>
			<div id="devto-wp-importer-messages-log"></div>
		</div>
	</div>

	<?php if ( $last_import && ! empty( $last_import['results'] ) ) : ?>
		<div class="devto-wp-importer-card">
			<h2><?php esc_html_e( 'Last Import', 'devto-wp-importer' ); ?></h2>
			<div class="devto-wp-importer-last-import">
				<p>
					<strong><?php esc_html_e( 'Date:', 'devto-wp-importer' ); ?></strong>
					<?php echo esc_html( $last_import['timestamp'] ?? '-' ); ?>
				</p>
				<div class="devto-wp-importer-info-grid">
					<div>
						<span class="devto-wp-importer-label"><?php esc_html_e( 'Created', 'devto-wp-importer' ); ?></span>
						<span class="devto-wp-importer-value"><?php echo absint( $last_import['results']['created'] ?? 0 ); ?></span>
					</div>
					<div>
						<span class="devto-wp-importer-label"><?php esc_html_e( 'Updated', 'devto-wp-importer' ); ?></span>
						<span class="devto-wp-importer-value"><?php echo absint( $last_import['results']['updated'] ?? 0 ); ?></span>
					</div>
					<div>
						<span class="devto-wp-importer-label"><?php esc_html_e( 'Skipped', 'devto-wp-importer' ); ?></span>
						<span class="devto-wp-importer-value"><?php echo absint( $last_import['results']['skipped'] ?? 0 ); ?></span>
					</div>
					<div>
						<span class="devto-wp-importer-label"><?php esc_html_e( 'Failed', 'devto-wp-importer' ); ?></span>
						<span class="devto-wp-importer-value"><?php echo absint( $last_import['results']['failed'] ?? 0 ); ?></span>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="devto-wp-importer-card">
		<h2><?php esc_html_e( 'How It Works', 'devto-wp-importer' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Enter your Dev.to API key above and save settings.', 'devto-wp-importer' ); ?></li>
			<li><?php esc_html_e( 'Click "Import Articles from Dev.to" to fetch all your articles.', 'devto-wp-importer' ); ?></li>
			<li><?php esc_html_e( 'Each article is mapped by its Dev.to ID - no duplicate posts are created.', 'devto-wp-importer' ); ?></li>
			<li><?php esc_html_e( 'On subsequent imports, only articles that have been edited on Dev.to will be updated.', 'devto-wp-importer' ); ?></li>
			<li><?php esc_html_e( 'Tags, cover images, and metadata are synced automatically.', 'devto-wp-importer' ); ?></li>
		</ol>
	</div>
</div>
