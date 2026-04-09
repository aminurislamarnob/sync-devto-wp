<?php

declare( strict_types=1 );

namespace SyncDevtoWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Api_Client {

	private const BASE_URL  = 'https://dev.to/api';
	private const PER_PAGE  = 100;
	private const MAX_RETRIES = 3;

	private string $api_key;

	public function __construct() {
		$this->api_key = $this->get_api_key();
	}

	private function get_api_key(): string {
		$encrypted = get_option( 'sdwp_api_key', '' );

		if ( empty( $encrypted ) ) {
			return '';
		}

		return $this->decrypt( $encrypted );
	}

	public function has_api_key(): bool {
		return ! empty( $this->api_key );
	}

	public function refresh_api_key(): void {
		$this->api_key = $this->get_api_key();
	}

	private function get_headers(): array {
		return array(
			'api-key'      => $this->api_key,
			'Accept'       => 'application/vnd.forem.api-v1+json',
			'Content-Type' => 'application/json',
		);
	}

	private function request( string $endpoint, array $query_params = array() ): array|\WP_Error {
		$url = self::BASE_URL . $endpoint;

		if ( ! empty( $query_params ) ) {
			$url = add_query_arg( $query_params, $url );
		}

		$args = array(
			'headers' => $this->get_headers(),
			'timeout' => 30,
		);

		$args = apply_filters( 'sdwp_api_request_args', $args, $endpoint );

		$retries = 0;
		$response = null;

		while ( $retries <= self::MAX_RETRIES ) {
			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				$retries++;
				if ( $retries <= self::MAX_RETRIES ) {
					sleep( pow( 2, $retries ) );
				}
				continue;
			}

			$code = wp_remote_retrieve_response_code( $response );

			if ( $code === 429 ) {
				$retries++;
				if ( $retries <= self::MAX_RETRIES ) {
					sleep( pow( 2, $retries ) );
				}
				continue;
			}

			if ( $code < 200 || $code >= 300 ) {
				$body = wp_remote_retrieve_body( $response );
				return new \WP_Error(
					'sdwp_api_error',
					sprintf(
						/* translators: 1: HTTP status code, 2: Response body */
						__( 'Dev.to API returned HTTP %1$d: %2$s', 'sync-devto-wp' ),
						$code,
						$body
					)
				);
			}

			break;
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'sdwp_json_error',
				__( 'Failed to parse Dev.to API response.', 'sync-devto-wp' )
			);
		}

		return $data;
	}

	public function get_articles_page( int $page = 1, string $scope = 'published' ): array|\WP_Error {
		$endpoint = $scope === 'all' ? '/articles/me/all' : '/articles/me/published';

		return $this->request( $endpoint, array(
			'page'     => $page,
			'per_page' => self::PER_PAGE,
		) );
	}

	public function get_all_articles( string $scope = 'published' ): array|\WP_Error {
		$all_articles = array();
		$page         = 1;

		while ( true ) {
			$articles = $this->get_articles_page( $page, $scope );

			if ( is_wp_error( $articles ) ) {
				return $articles;
			}

			if ( empty( $articles ) ) {
				break;
			}

			$all_articles = array_merge( $all_articles, $articles );
			$page++;

			usleep( 200000 );
		}

		return $all_articles;
	}

	public function get_article( int $article_id ): array|\WP_Error {
		return $this->request( '/articles/' . $article_id );
	}

	public static function encrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$salt = wp_salt( 'auth' );
		$key  = hash( 'sha256', $salt, true );
		$iv   = substr( hash( 'sha256', wp_salt( 'secure_auth' ), true ), 0, 16 );

		$encrypted = openssl_encrypt( $value, 'AES-256-CBC', $key, 0, $iv );

		return $encrypted !== false ? base64_encode( $encrypted ) : '';
	}

	private function decrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$decoded = base64_decode( $value );
		if ( $decoded === false ) {
			return '';
		}

		$salt = wp_salt( 'auth' );
		$key  = hash( 'sha256', $salt, true );
		$iv   = substr( hash( 'sha256', wp_salt( 'secure_auth' ), true ), 0, 16 );

		$decrypted = openssl_decrypt( $decoded, 'AES-256-CBC', $key, 0, $iv );

		return $decrypted !== false ? $decrypted : '';
	}
}
