<?php

namespace WeLabs\DevtoWpImporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	/**
	 * @var Importer
	 */
	private $importer;

	public function __construct( Importer $importer ) {
		$this->importer = $importer;

		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'wp_ajax_devto_wp_importer_import_articles', [ $this, 'ajax_import' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	public function register_menu() {
		add_management_page(
			__( 'Dev.to Importer', 'devto-wp-importer' ),
			__( 'Dev.to Importer', 'devto-wp-importer' ),
			'manage_options',
			'devto-wp-importer',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings() {
		register_setting(
			'devto_wp_importer_settings',
			'devto_wp_importer_api_key',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_api_key' ],
			]
		);

		register_setting(
			'devto_wp_importer_settings',
			'devto_wp_importer_post_status',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'publish',
			]
		);

		register_setting(
			'devto_wp_importer_settings',
			'devto_wp_importer_post_author',
			[
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			]
		);

		register_setting(
			'devto_wp_importer_settings',
			'devto_wp_importer_import_scope',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'published',
			]
		);

		add_settings_section(
			'devto_wp_importer_main_section',
			__( 'Dev.to API Settings', 'devto-wp-importer' ),
			[ $this, 'render_section_description' ],
			'devto_wp_importer_settings'
		);

		add_settings_field(
			'devto_wp_importer_api_key',
			__( 'API Key', 'devto-wp-importer' ),
			[ $this, 'render_api_key_field' ],
			'devto_wp_importer_settings',
			'devto_wp_importer_main_section'
		);

		add_settings_field(
			'devto_wp_importer_post_status',
			__( 'Import Post Status', 'devto-wp-importer' ),
			[ $this, 'render_post_status_field' ],
			'devto_wp_importer_settings',
			'devto_wp_importer_main_section'
		);

		add_settings_field(
			'devto_wp_importer_post_author',
			__( 'Post Author', 'devto-wp-importer' ),
			[ $this, 'render_post_author_field' ],
			'devto_wp_importer_settings',
			'devto_wp_importer_main_section'
		);

		add_settings_field(
			'devto_wp_importer_import_scope',
			__( 'Import Scope', 'devto-wp-importer' ),
			[ $this, 'render_import_scope_field' ],
			'devto_wp_importer_settings',
			'devto_wp_importer_main_section'
		);
	}

	public function sanitize_api_key( $value ) {
		$value = sanitize_text_field( $value );

		if ( empty( $value ) ) {
			return '';
		}

		return ApiClient::encrypt( $value );
	}

	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure your Dev.to API key and import preferences. Get your API key from Dev.to -> Settings -> Extensions.', 'devto-wp-importer' ) . '</p>';
	}

	public function render_api_key_field() {
		$value   = get_option( 'devto_wp_importer_api_key', '' );
		$has_key = ! empty( $value );
		?>
		<input
			type="password"
			name="devto_wp_importer_api_key"
			id="devto_wp_importer_api_key"
			class="regular-text"
			value=""
			placeholder="<?php echo $has_key ? esc_attr__( '***************** (key is saved)', 'devto-wp-importer' ) : esc_attr__( 'Enter your Dev.to API key', 'devto-wp-importer' ); ?>"
		/>
		<?php if ( $has_key ) : ?>
			<p class="description">
				<?php esc_html_e( 'A key is already saved. Enter a new key to replace it, or leave blank to keep the existing key.', 'devto-wp-importer' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: URL to Dev.to settings */
					esc_html__( 'Generate your API key at %s', 'devto-wp-importer' ),
					'<a href="https://dev.to/settings/extensions" target="_blank" rel="noopener">dev.to/settings/extensions</a>'
				);
				?>
			</p>
		<?php endif; ?>
		<?php
	}

	public function render_post_status_field() {
		$value    = get_option( 'devto_wp_importer_post_status', 'publish' );
		$statuses = [
			'draft'   => __( 'Draft', 'devto-wp-importer' ),
			'publish' => __( 'Published', 'devto-wp-importer' ),
			'pending' => __( 'Pending Review', 'devto-wp-importer' ),
		];
		?>
		<select name="devto_wp_importer_post_status" id="devto_wp_importer_post_status">
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Status applied to imported posts. Unpublished Dev.to articles are always imported as drafts.', 'devto-wp-importer' ); ?>
		</p>
		<?php
	}

	public function render_post_author_field() {
		$value = absint( get_option( 'devto_wp_importer_post_author', 0 ) );

		wp_dropdown_users(
			[
				'name'              => 'devto_wp_importer_post_author',
				'id'                => 'devto_wp_importer_post_author',
				'selected'          => $value,
				'show_option_none'  => __( '- Current User -', 'devto-wp-importer' ),
				'option_none_value' => 0,
			]
		);
		?>
		<p class="description">
			<?php esc_html_e( 'WordPress user assigned as the author of imported posts.', 'devto-wp-importer' ); ?>
		</p>
		<?php
	}

	public function render_import_scope_field() {
		$value = get_option( 'devto_wp_importer_import_scope', 'published' );
		?>
		<select name="devto_wp_importer_import_scope" id="devto_wp_importer_import_scope">
			<option value="published" <?php selected( $value, 'published' ); ?>>
				<?php esc_html_e( 'Published articles only', 'devto-wp-importer' ); ?>
			</option>
			<option value="all" <?php selected( $value, 'all' ); ?>>
				<?php esc_html_e( 'All articles (including drafts)', 'devto-wp-importer' ); ?>
			</option>
		</select>
		<?php
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'devto-wp-importer' ) );
		}

		include DEVTO_WP_IMPORTER_TEMPLATE_DIR . '/admin-page.php';
	}

	public function ajax_import() {
		check_ajax_referer( 'devto_wp_importer_import_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to perform this action.', 'devto-wp-importer' ),
				]
			);
		}

		$results = $this->importer->run();

		wp_send_json_success( $results );
	}

	public function admin_notices() {
		$screen = get_current_screen();

		if ( $screen === null || $screen->id !== 'tools_page_devto-wp-importer' ) {
			return;
		}

		$api_key = get_option( 'devto_wp_importer_api_key', '' );
		if ( empty( $api_key ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'Please configure your Dev.to API key to start importing articles.', 'devto-wp-importer' )
			);
		}
	}
}
