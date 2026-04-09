<?php

declare( strict_types=1 );

namespace SyncDevtoWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	private Importer $importer;

	public function __construct( Importer $importer ) {
		$this->importer = $importer;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_sdwp_import_articles', array( $this, 'ajax_import' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function register_menu(): void {
		add_management_page(
			__( 'Dev.to Importer', 'sync-devto-wp' ),
			__( 'Dev.to Importer', 'sync-devto-wp' ),
			'manage_options',
			'sdwp-importer',
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		register_setting( 'sdwp_settings', 'sdwp_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_api_key' ),
		) );

		register_setting( 'sdwp_settings', 'sdwp_post_status', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'draft',
		) );

		register_setting( 'sdwp_settings', 'sdwp_post_author', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		register_setting( 'sdwp_settings', 'sdwp_import_scope', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'published',
		) );

		add_settings_section(
			'sdwp_main_section',
			__( 'Dev.to API Settings', 'sync-devto-wp' ),
			array( $this, 'render_section_description' ),
			'sdwp_settings'
		);

		add_settings_field(
			'sdwp_api_key',
			__( 'API Key', 'sync-devto-wp' ),
			array( $this, 'render_api_key_field' ),
			'sdwp_settings',
			'sdwp_main_section'
		);

		add_settings_field(
			'sdwp_post_status',
			__( 'Import Post Status', 'sync-devto-wp' ),
			array( $this, 'render_post_status_field' ),
			'sdwp_settings',
			'sdwp_main_section'
		);

		add_settings_field(
			'sdwp_post_author',
			__( 'Post Author', 'sync-devto-wp' ),
			array( $this, 'render_post_author_field' ),
			'sdwp_settings',
			'sdwp_main_section'
		);

		add_settings_field(
			'sdwp_import_scope',
			__( 'Import Scope', 'sync-devto-wp' ),
			array( $this, 'render_import_scope_field' ),
			'sdwp_settings',
			'sdwp_main_section'
		);
	}

	public function sanitize_api_key( $value ): string {
		$value = sanitize_text_field( $value );

		if ( empty( $value ) ) {
			return '';
		}

		return Api_Client::encrypt( $value );
	}

	public function render_section_description(): void {
		echo '<p>' . esc_html__( 'Configure your Dev.to API key and import preferences. Get your API key from Dev.to → Settings → Extensions.', 'sync-devto-wp' ) . '</p>';
	}

	public function render_api_key_field(): void {
		$value = get_option( 'sdwp_api_key', '' );
		$has_key = ! empty( $value );
		?>
		<input
			type="password"
			name="sdwp_api_key"
			id="sdwp_api_key"
			class="regular-text"
			value=""
			placeholder="<?php echo $has_key ? esc_attr__( '••••••••••••••••• (key is saved)', 'sync-devto-wp' ) : esc_attr__( 'Enter your Dev.to API key', 'sync-devto-wp' ); ?>"
		/>
		<?php if ( $has_key ) : ?>
			<p class="description">
				<?php esc_html_e( 'A key is already saved. Enter a new key to replace it, or leave blank to keep the existing key.', 'sync-devto-wp' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: URL to Dev.to settings */
					esc_html__( 'Generate your API key at %s', 'sync-devto-wp' ),
					'<a href="https://dev.to/settings/extensions" target="_blank" rel="noopener">dev.to/settings/extensions</a>'
				);
				?>
			</p>
		<?php endif; ?>
		<?php
	}

	public function render_post_status_field(): void {
		$value = get_option( 'sdwp_post_status', 'draft' );
		$statuses = array(
			'draft'   => __( 'Draft', 'sync-devto-wp' ),
			'publish' => __( 'Published', 'sync-devto-wp' ),
			'pending' => __( 'Pending Review', 'sync-devto-wp' ),
		);
		?>
		<select name="sdwp_post_status" id="sdwp_post_status">
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Status applied to imported posts. Unpublished Dev.to articles are always imported as drafts.', 'sync-devto-wp' ); ?>
		</p>
		<?php
	}

	public function render_post_author_field(): void {
		$value = absint( get_option( 'sdwp_post_author', 0 ) );

		wp_dropdown_users( array(
			'name'             => 'sdwp_post_author',
			'id'               => 'sdwp_post_author',
			'selected'         => $value,
			'show_option_none' => __( '— Current User —', 'sync-devto-wp' ),
			'option_none_value' => 0,
		) );
		?>
		<p class="description">
			<?php esc_html_e( 'WordPress user assigned as the author of imported posts.', 'sync-devto-wp' ); ?>
		</p>
		<?php
	}

	public function render_import_scope_field(): void {
		$value = get_option( 'sdwp_import_scope', 'published' );
		?>
		<select name="sdwp_import_scope" id="sdwp_import_scope">
			<option value="published" <?php selected( $value, 'published' ); ?>>
				<?php esc_html_e( 'Published articles only', 'sync-devto-wp' ); ?>
			</option>
			<option value="all" <?php selected( $value, 'all' ); ?>>
				<?php esc_html_e( 'All articles (including drafts)', 'sync-devto-wp' ); ?>
			</option>
		</select>
		<?php
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'sync-devto-wp' ) );
		}

		include SDWP_PLUGIN_DIR . 'templates/admin-page.php';
	}

	public function ajax_import(): void {
		check_ajax_referer( 'sdwp_import_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to perform this action.', 'sync-devto-wp' ),
			) );
		}

		$results = $this->importer->run();

		wp_send_json_success( $results );
	}

	public function admin_notices(): void {
		$screen = get_current_screen();

		if ( $screen === null || $screen->id !== 'tools_page_sdwp-importer' ) {
			return;
		}

		$api_key = get_option( 'sdwp_api_key', '' );
		if ( empty( $api_key ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'Please configure your Dev.to API key to start importing articles.', 'sync-devto-wp' )
			);
		}
	}
}
