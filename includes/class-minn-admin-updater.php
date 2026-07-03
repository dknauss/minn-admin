<?php
/**
 * Self-updater. Checks the manifest.json on GitHub and feeds WordPress the
 * update package from GitHub Releases — same pattern as Disembark.
 *
 * @package minn-admin
 */

defined( 'ABSPATH' ) || exit;

class Minn_Admin_Updater {

	public $plugin_slug;
	public $version;
	public $cache_key;
	public $cache_allowed;

	const MANIFEST_URL = 'https://raw.githubusercontent.com/austinginder/minn-admin/main/manifest.json';

	public function __construct() {
		if ( defined( 'MINN_ADMIN_DEV_MODE' ) ) {
			add_filter( 'https_ssl_verify', '__return_false' );
			add_filter( 'https_local_ssl_verify', '__return_false' );
			add_filter( 'http_request_host_is_external', '__return_true' );
		}
		$this->plugin_slug   = 'minn-admin';
		$this->version       = MINN_ADMIN_VERSION;
		$this->cache_key     = 'minn_admin_updater';
		$this->cache_allowed = false;

		add_filter( 'plugins_api', array( $this, 'info' ), 30, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
	}

	public function request() {
		// Get the local manifest as a fallback.
		$manifest_file  = dirname( __DIR__ ) . '/manifest.json';
		$local_manifest = null;
		if ( file_exists( $manifest_file ) ) {
			$local_manifest = json_decode( file_get_contents( $manifest_file ) );
		}

		if ( ! is_object( $local_manifest ) ) {
			$local_manifest = new \stdClass();
		}

		$remote = get_transient( $this->cache_key );

		if ( false === $remote || ! $this->cache_allowed ) {
			$remote_response = wp_remote_get(
				self::MANIFEST_URL,
				array(
					'timeout' => 30,
					'headers' => array( 'Accept' => 'application/json' ),
				)
			);

			if ( is_wp_error( $remote_response ) || 200 !== wp_remote_retrieve_response_code( $remote_response ) || empty( wp_remote_retrieve_body( $remote_response ) ) ) {
				return $local_manifest;
			}

			$remote = json_decode( wp_remote_retrieve_body( $remote_response ) );
			set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );
		}

		if ( is_object( $remote ) ) {
			return $remote;
		}

		return $local_manifest;
	}

	public function info( $response, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
			return $response;
		}

		$remote = $this->request();
		if ( ! $remote || empty( $remote->version ) ) {
			return $response;
		}

		$response                 = new \stdClass();
		$response->name           = $remote->name;
		$response->slug           = $remote->slug;
		$response->version        = $remote->version;
		$response->tested         = $remote->tested;
		$response->requires       = $remote->requires;
		$response->author         = $remote->author;
		$response->author_profile = $remote->author_profile;
		$response->homepage       = $remote->homepage;
		$response->download_link  = $remote->download_url;
		$response->trunk          = $remote->download_url;
		$response->requires_php   = $remote->requires_php;
		$response->last_updated   = $remote->last_updated;
		$response->sections       = array( 'description' => $remote->sections->description );

		if ( ! empty( $remote->banners ) ) {
			$response->banners = array(
				'low'  => $remote->banners->low,
				'high' => $remote->banners->high,
			);
		}
		return $response;
	}

	public function update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->request();
		if ( $remote && isset( $remote->version ) && version_compare( $this->version, $remote->version, '<' ) ) {
			$response               = new \stdClass();
			$response->slug         = $this->plugin_slug;
			$response->plugin       = "{$this->plugin_slug}/{$this->plugin_slug}.php";
			$response->new_version  = $remote->version;
			$response->package      = $remote->download_url;
			$response->tested       = $remote->tested;
			$response->requires_php = $remote->requires_php;

			$transient->response[ $response->plugin ] = $response;
		}
		return $transient;
	}

	public function purge( $upgrader, $options ) {
		if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			delete_transient( $this->cache_key );
		}
	}
}
