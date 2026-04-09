<?php

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$last_import = get_option( 'sdwp_last_import_result', null );
?>

<div class="wrap sdwp-wrap">
	<div class="sdwp-header">
		<h1><?php esc_html_e( 'Dev.to Article Importer', 'sync-devto-wp' ); ?></h1>
	</div>

	<!-- Settings Card -->
	<div class="sdwp-card">
		<h2><?php esc_html_e( 'Settings', 'sync-devto-wp' ); ?></h2>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'sdwp_settings' );
			do_settings_sections( 'sdwp_settings' );
			submit_button( __( 'Save Settings', 'sync-devto-wp' ) );
			?>
		</form>
	</div>

	<!-- Import Card -->
	<div class="sdwp-card">
		<h2><?php esc_html_e( 'Import Articles', 'sync-devto-wp' ); ?></h2>
		<div class="sdwp-import-section">
			<p>
				<?php esc_html_e( 'Click the button below to import your Dev.to articles into WordPress. Existing articles will be updated if they have changed; no duplicates will be created.', 'sync-devto-wp' ); ?>
			</p>
			<button
				type="button"
				id="sdwp-import-btn"
				class="button button-primary button-hero"
				data-label="<?php esc_attr_e( 'Import Articles from Dev.to', 'sync-devto-wp' ); ?>"
			>
				<?php esc_html_e( 'Import Articles from Dev.to', 'sync-devto-wp' ); ?>
			</button>
			<span class="spinner" id="sdwp-spinner"></span>
		</div>

		<div id="sdwp-results" style="display:none;">
			<div id="sdwp-results-summary"></div>
			<div id="sdwp-messages-log"></div>
		</div>
	</div>

	<!-- Last Import Card -->
	<?php if ( $last_import && ! empty( $last_import['results'] ) ) : ?>
		<div class="sdwp-card">
			<h2><?php esc_html_e( 'Last Import', 'sync-devto-wp' ); ?></h2>
			<div class="sdwp-last-import">
				<p>
					<strong><?php esc_html_e( 'Date:', 'sync-devto-wp' ); ?></strong>
					<?php echo esc_html( $last_import['timestamp'] ?? '—' ); ?>
				</p>
				<div class="sdwp-info-grid">
					<div>
						<span class="sdwp-label"><?php esc_html_e( 'Created', 'sync-devto-wp' ); ?></span>
						<span class="sdwp-value"><?php echo absint( $last_import['results']['created'] ?? 0 ); ?></span>
					</div>
					<div>
						<span class="sdwp-label"><?php esc_html_e( 'Updated', 'sync-devto-wp' ); ?></span>
						<span class="sdwp-value"><?php echo absint( $last_import['results']['updated'] ?? 0 ); ?></span>
					</div>
					<div>
						<span class="sdwp-label"><?php esc_html_e( 'Skipped', 'sync-devto-wp' ); ?></span>
						<span class="sdwp-value"><?php echo absint( $last_import['results']['skipped'] ?? 0 ); ?></span>
					</div>
					<div>
						<span class="sdwp-label"><?php esc_html_e( 'Failed', 'sync-devto-wp' ); ?></span>
						<span class="sdwp-value"><?php echo absint( $last_import['results']['failed'] ?? 0 ); ?></span>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- How It Works Card -->
	<div class="sdwp-card">
		<h2><?php esc_html_e( 'How It Works', 'sync-devto-wp' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Enter your Dev.to API key above and save settings.', 'sync-devto-wp' ); ?></li>
			<li><?php esc_html_e( 'Click "Import Articles from Dev.to" to fetch all your articles.', 'sync-devto-wp' ); ?></li>
			<li><?php esc_html_e( 'Each article is mapped by its Dev.to ID — no duplicate posts are created.', 'sync-devto-wp' ); ?></li>
			<li><?php esc_html_e( 'On subsequent imports, only articles that have been edited on Dev.to will be updated.', 'sync-devto-wp' ); ?></li>
			<li><?php esc_html_e( 'Tags, cover images, and metadata are all synced automatically.', 'sync-devto-wp' ); ?></li>
		</ol>
	</div>
</div>
