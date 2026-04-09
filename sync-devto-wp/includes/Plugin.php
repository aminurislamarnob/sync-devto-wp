<?php

declare( strict_types=1 );

namespace SyncDevtoWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	public Api_Client $api_client;
	public Importer   $importer;
	public Admin      $admin;
	public Assets     $assets;

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->api_client = new Api_Client();
		$this->importer   = new Importer( $this->api_client );
		$this->admin      = new Admin( $this->importer );
		$this->assets     = new Assets();
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}
}
