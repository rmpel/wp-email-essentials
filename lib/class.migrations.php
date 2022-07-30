<?php
/**
 * Class for Migrating other plugins settings
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

/**
 * The PHP 5.4 and earlier version of this class, also used as the base for the PHP 5.5+ version.
 */
class Migrations {

	/**
	 * Implementation of register_activation_hook().
	 */
	public static function plugin_activation_hook() {
		self::migrate_from_smtp_connect();
		self::migrate_from_post_smtp();
	}

	/**
	 * Migrate settings from SMTP Connect and deactivate the plugin.
	 */
	public static function migrate_from_smtp_connect() {
		$plugin = 'smtp-connect/smtp-connect.php';
		$active = self::plugin_active( $plugin );

		if ( $active ) {
			// plugin active, migrate.
			$wpes_config = Plugin::get_config();

			$smtp_connect = get_option( 'smtp-connect', array() );
			if ( $smtp_connect['enabled'] ?? false ) {
				$wpes_config['smtp-enabled'] = true;
				$wpes_config['host']         = $smtp_connect['Host'];
				$wpes_config['username']     = $smtp_connect['Username'];
				$wpes_config['password']     = $smtp_connect['Password'];
			}
			Plugin::update_config( $wpes_config );

			self::deactivate( $plugin );
		}
	}

	/**
	 * Migrate settings from Postman SMTP and deactivate the plugin.
	 */
	public static function migrate_from_post_smtp() {
		$plugin = 'post-smtp/postman-smtp.php';
		$active = self::plugin_active( $plugin );

		if ( $active ) {
			// plugin active, migrate.
			$wpes_config = Plugin::get_config();

			$postman_settings = get_option( 'postman_options', array() );
			if ( $postman_settings['hostname'] ?? false ) {
				$wpes_config['smtp-enabled']   = true;
				$wpes_config['secure']         = '' !== $postman_settings['enc_type'] ? 'tls-' : ''; // Assume unvalidated TLS; we don't know.
				$wpes_config['host']           = $postman_settings['hostname'];
				$wpes_config['port']           = $postman_settings['port'];
				$wpes_config['username']       = $postman_settings['basic_auth_username'];
				$wpes_config['password']       = $postman_settings['basic_auth_password'];
				$wpes_config['from_name']      = $postman_settings['sender_name'];
				$wpes_config['from_email']     = $postman_settings['sender_email'];
				$wpes_config['enable_history'] = true === $postman_settings['mail_log_enabled'] || 'true' === $postman_settings['mail_log_enabled'];
			}
			Plugin::update_config( $wpes_config );

			self::deactivate( $plugin );
		}
	}

	/**
	 * Check if the plugin is active
	 *
	 * @param string $plugin The plugin to check.
	 *
	 * @return false|string False for not active, blog or network for active on blog or network
	 */
	private static function plugin_active( $plugin ) {
		if ( is_multisite() && is_plugin_active_for_network( $plugin ) ) {
			return 'network';
		}
		if ( is_plugin_active( $plugin ) ) {
			return 'blog';
		}

		return false;
	}

	/**
	 * Deactivate a plugin, the hard way.
	 *
	 * @param string $plugin The plugin file path relative to wp-content/plugins.
	 */
	private static function deactivate( $plugin ) {
		// deactivate conflicting plugin.
		deactivate_plugins( $plugin, false, true );

		// WordPress still thinks the plugin is active, do it the hard way.
		$active = get_option( 'active_plugins', [] );
		unset( $active[ array_search( $plugin, $active, true ) ] );
		update_option( 'active_plugins', $active );

		$active = get_site_option( 'active_sitewide_plugins', [] );
		unset( $active[ array_search( $plugin, $active, true ) ] );
		update_site_option( 'active_sitewide_plugins', $active );

		// log the deactivation.
		update_option( 'recently_activated', array_merge( get_option( 'recently_activated', [] ) ?: [], [ $plugin => time() ] ) );
		update_site_option( 'recently_activated', array_merge( get_site_option( 'recently_activated', [] ) ?: [], [ $plugin => time() ] ) );
	}
}
