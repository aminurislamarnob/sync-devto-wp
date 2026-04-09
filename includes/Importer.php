<?php

declare( strict_types=1 );

namespace SyncDevtoWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Importer {

	private Api_Client $api_client;

	private array $results = array(
		'created'  => 0,
		'updated'  => 0,
		'skipped'  => 0,
		'failed'   => 0,
		'messages' => array(),
	);

	public function __construct( Api_Client $api_client ) {
		$this->api_client = $api_client;
	}

	public function run(): array {
		$this->results = array(
			'created'  => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'messages' => array(),
		);

		if ( ! $this->api_client->has_api_key() ) {
			$this->results['messages'][] = __( 'API key is not configured. Please add your Dev.to API key in settings.', 'sync-devto-wp' );
			return $this->results;
		}

		if ( get_transient( 'sdwp_import_lock' ) ) {
			$this->results['messages'][] = __( 'An import is already in progress. Please wait.', 'sync-devto-wp' );
			return $this->results;
		}

		set_transient( 'sdwp_import_lock', true, 10 * MINUTE_IN_SECONDS );

		do_action( 'sdwp_before_import' );

		$scope    = get_option( 'sdwp_import_scope', 'published' );
		$articles = $this->api_client->get_all_articles( $scope );

		if ( is_wp_error( $articles ) ) {
			delete_transient( 'sdwp_import_lock' );
			$this->results['messages'][] = sprintf(
				/* translators: %s: Error message from Dev.to API */
				__( 'Failed to fetch articles from Dev.to: %s', 'sync-devto-wp' ),
				$articles->get_error_message()
			);
			return $this->results;
		}

		if ( empty( $articles ) ) {
			delete_transient( 'sdwp_import_lock' );
			$this->results['messages'][] = __( 'No articles found on your Dev.to account.', 'sync-devto-wp' );
			return $this->results;
		}

		$this->results['messages'][] = sprintf(
			/* translators: %d: Number of articles found */
			__( 'Found %d articles on Dev.to. Starting import...', 'sync-devto-wp' ),
			count( $articles )
		);

		foreach ( $articles as $article_summary ) {
			$this->process_article( $article_summary );
		}

		delete_transient( 'sdwp_import_lock' );

		do_action( 'sdwp_after_import', $this->results );

		update_option( 'sdwp_last_import_result', array(
			'results'   => $this->results,
			'timestamp' => current_time( 'mysql' ),
		) );

		return $this->results;
	}

	private function process_article( array $article_summary ): void {
		$devto_id = absint( $article_summary['id'] ?? 0 );

		if ( $devto_id === 0 ) {
			$this->results['failed']++;
			$this->results['messages'][] = __( 'Skipped article with missing ID.', 'sync-devto-wp' );
			return;
		}

		$should_import = apply_filters( 'sdwp_should_import_article', true, $article_summary );
		if ( ! $should_import ) {
			$this->results['skipped']++;
			return;
		}

		$existing_post = $this->find_existing_post( $devto_id );

		if ( $existing_post ) {
			$this->maybe_update_post( $existing_post, $article_summary );
		} else {
			$this->create_post( $devto_id, $article_summary );
		}
	}

	private function find_existing_post( int $devto_id ): ?\WP_Post {
		$posts = get_posts( array(
			'post_type'   => 'post',
			'meta_key'    => '_sdwp_devto_id',
			'meta_value'  => $devto_id,
			'post_status' => 'any',
			'numberposts' => 1,
		) );

		return ! empty( $posts ) ? $posts[0] : null;
	}

	private function maybe_update_post( \WP_Post $existing_post, array $article_summary ): void {
		$devto_id       = absint( $article_summary['id'] );
		$title          = $article_summary['title'] ?? '';
		$stored_edited  = get_post_meta( $existing_post->ID, '_sdwp_devto_edited_at', true );
		$remote_edited  = $article_summary['edited_at'] ?? '';

		if ( ! empty( $stored_edited ) && $stored_edited === $remote_edited ) {
			$this->results['skipped']++;
			do_action( 'sdwp_article_skipped', $existing_post->ID, $devto_id );
			return;
		}

		$full_article = $this->api_client->get_article( $devto_id );

		if ( is_wp_error( $full_article ) ) {
			$this->results['failed']++;
			$this->results['messages'][] = sprintf(
				/* translators: 1: Article title, 2: Error message */
				__( 'Failed to fetch full article "%1$s": %2$s', 'sync-devto-wp' ),
				esc_html( $title ),
				$full_article->get_error_message()
			);
			return;
		}

		usleep( 200000 );

		$post_data = $this->map_article_to_post_data( $full_article );
		$post_data['ID'] = $existing_post->ID;

		$post_data = apply_filters( 'sdwp_article_post_data', $post_data, $full_article, 'update' );

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			$this->results['failed']++;
			$this->results['messages'][] = sprintf(
				/* translators: 1: Article title, 2: Error message */
				__( 'Failed to update "%1$s": %2$s', 'sync-devto-wp' ),
				esc_html( $title ),
				$result->get_error_message()
			);
			return;
		}

		$this->save_article_meta( $existing_post->ID, $full_article );
		$this->sync_tags( $existing_post->ID, $full_article );
		$this->sync_cover_image( $existing_post->ID, $full_article );

		$this->results['updated']++;

		do_action( 'sdwp_article_updated', $existing_post->ID, $devto_id, $full_article );
	}

	private function create_post( int $devto_id, array $article_summary ): void {
		$title = $article_summary['title'] ?? '';

		$full_article = $this->api_client->get_article( $devto_id );

		if ( is_wp_error( $full_article ) ) {
			$this->results['failed']++;
			$this->results['messages'][] = sprintf(
				/* translators: 1: Article title, 2: Error message */
				__( 'Failed to fetch full article "%1$s": %2$s', 'sync-devto-wp' ),
				esc_html( $title ),
				$full_article->get_error_message()
			);
			return;
		}

		usleep( 200000 );

		$post_data = $this->map_article_to_post_data( $full_article );

		$post_data = apply_filters( 'sdwp_article_post_data', $post_data, $full_article, 'create' );

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			$this->results['failed']++;
			$this->results['messages'][] = sprintf(
				/* translators: 1: Article title, 2: Error message */
				__( 'Failed to create post for "%1$s": %2$s', 'sync-devto-wp' ),
				esc_html( $title ),
				$post_id->get_error_message()
			);
			return;
		}

		$this->save_article_meta( $post_id, $full_article );
		$this->sync_tags( $post_id, $full_article );
		$this->sync_cover_image( $post_id, $full_article );

		$this->results['created']++;

		do_action( 'sdwp_article_imported', $post_id, $devto_id, $full_article );
	}

	private function map_article_to_post_data( array $article ): array {
		$post_status = get_option( 'sdwp_post_status', 'draft' );
		$post_author = absint( get_option( 'sdwp_post_author', 0 ) );

		if ( $post_author === 0 ) {
			$post_author = get_current_user_id();
		}

		$is_published = $article['published'] ?? false;
		$status       = $is_published
			? apply_filters( 'sdwp_import_post_status', $post_status, $article )
			: 'draft';

		$post_date     = '';
		$post_date_gmt = '';
		$published_at  = $article['published_at'] ?? '';

		if ( ! empty( $published_at ) ) {
			$timestamp     = strtotime( $published_at );
			$post_date     = gmdate( 'Y-m-d H:i:s', $timestamp );
			$post_date_gmt = $post_date;
		}

		$content = wp_kses_post( $article['body_html'] ?? '' );

		$data = array(
			'post_title'    => sanitize_text_field( $article['title'] ?? '' ),
			'post_content'  => $content,
			'post_excerpt'  => sanitize_text_field( $article['description'] ?? '' ),
			'post_status'   => $status,
			'post_author'   => $post_author,
			'post_type'     => 'post',
		);

		if ( ! empty( $post_date ) ) {
			$data['post_date']     = $post_date;
			$data['post_date_gmt'] = $post_date_gmt;
		}

		return $data;
	}

	private function save_article_meta( int $post_id, array $article ): void {
		$meta = array(
			'_sdwp_devto_id'            => absint( $article['id'] ?? 0 ),
			'_sdwp_devto_url'           => esc_url_raw( $article['url'] ?? '' ),
			'_sdwp_devto_slug'          => sanitize_text_field( $article['slug'] ?? '' ),
			'_sdwp_devto_edited_at'     => sanitize_text_field( $article['edited_at'] ?? '' ),
			'_sdwp_devto_published_at'  => sanitize_text_field( $article['published_at'] ?? '' ),
			'_sdwp_devto_canonical_url' => esc_url_raw( $article['canonical_url'] ?? '' ),
			'_sdwp_devto_reactions'     => absint( $article['public_reactions_count'] ?? 0 ),
			'_sdwp_devto_comments'      => absint( $article['comments_count'] ?? 0 ),
			'_sdwp_devto_reading_time'  => absint( $article['reading_time_minutes'] ?? 0 ),
			'_sdwp_last_imported'       => current_time( 'mysql' ),
		);

		$meta = apply_filters( 'sdwp_article_meta_data', $meta, $article, $post_id );

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	private function sync_tags( int $post_id, array $article ): void {
		$tag_list = $article['tag_list'] ?? array();

		if ( is_string( $tag_list ) ) {
			$tag_list = array_map( 'trim', explode( ',', $tag_list ) );
		}

		$tag_list = array_filter( array_map( 'sanitize_text_field', $tag_list ) );

		if ( ! empty( $tag_list ) ) {
			wp_set_post_tags( $post_id, $tag_list, false );
		}
	}

	private function sync_cover_image( int $post_id, array $article ): void {
		$cover_image_url = $article['cover_image'] ?? '';

		if ( empty( $cover_image_url ) ) {
			return;
		}

		$stored_url = get_post_meta( $post_id, '_sdwp_devto_cover_image_url', true );
		if ( $stored_url === $cover_image_url && has_post_thumbnail( $post_id ) ) {
			return;
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$tmp = download_url( $cover_image_url );

		if ( is_wp_error( $tmp ) ) {
			return;
		}

		$file_info = wp_check_filetype( basename( wp_parse_url( $cover_image_url, PHP_URL_PATH ) ?? 'image.jpg' ) );
		$ext       = $file_info['ext'] ?: 'jpg';

		$file_array = array(
			'name'     => sanitize_file_name( 'devto-' . $article['id'] . '-cover.' . $ext ),
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return;
		}

		set_post_thumbnail( $post_id, $attachment_id );
		update_post_meta( $post_id, '_sdwp_devto_cover_image_url', esc_url_raw( $cover_image_url ) );
	}
}
