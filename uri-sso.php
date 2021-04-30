<?php
/**
 * Plugin Name: URI SSO
 * Plugin URI: https://www.uri.edu/wordpress/software/
 * Description: authenticate users against URI's SSO implementation
 * Version: 1.0
 * Author: URI Web Communications
 * Author URI: https://web.uri.edu/external-relations/contact-us/#web
 *
 * @author: John Pennypacker <jpennypacker@uri.edu>
 * @author: Brandon Fuller <bjcfuller@uri.edu>
 */

/** absorbs Brandon's awesome login screen styling from uri-admin-login **/
/** borrows a good bit from http://danieltwc.com/2011/http-authentication-4-0/ **/


// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');

define( 'URI_SSO_PATH', plugin_dir_path( __FILE__ ) );
define( 'URI_SSO_URL', str_replace('/inc', '/', plugins_url( 'inc', __FILE__ ) ) );


if ( is_admin() || uri_sso_get_settings( 'use_sso' ) ) {
	// include the convenience functions
	include_once( URI_SSO_PATH . 'inc/uri-sso-utility-functions.php' );

	// include the settings
	include_once( URI_SSO_PATH . 'inc/uri-sso-settings.php' );
}

// include the authentication customizations, is the setting is set
if ( uri_sso_get_settings( 'use_sso' ) ) {
	// include the log in screen customizations
	include_once( URI_SSO_PATH . 'inc/uri-sso-login-screen.php' );
	include_once( URI_SSO_PATH . 'inc/uri-sso-authentication.php' );
}


/**
 * Default values for the admin settings.  
 * If a key is specified and it exists, that value is returned.
 * If a key is specified but does not exist, it returns false.
 * If no key is set, it returns the entire array.
 * @param str $key the specific setting to return
 * @return mixed
 */
function uri_sso_default_settings( $key='' ) {
	$default_settings = array(
		'use_sso' => FALSE,
		'login_url' => '%host%/mellon/login',
		'logout_url' => '%host%/mellon/logout',
		'user_variables' => 'REMOTE_USER, REDIRECT_REMOTE_USER, URI_AZURESSO_uri_username',
		'default_role' => 'subscriber',
		'first_name_variable' => 'URI_AZURESSO_uri_givenname, URI_LDAP_displayname',
		'last_name_variable' => 'URI_AZURESSO_uri_surname, URI_LDAP_sn',
	);

	if ( ! empty ( $key ) ) {
		if( array_key_exists( $key, $default_settings ) ) {
			return $default_settings[$key];
		} else {
			return FALSE;
		}
	} else {
		return $default_settings;
	}
}

/**
 * Query stored settings from the database.
 * @param str $key the specific setting to return
 * @return mixed if $key is set, it returns the value of that setting, 
 *  otherwise, array of all settings
 */
function uri_sso_get_settings( $key='', $default=NULL ) {
	if ( NULL === $default ) {
		$default = uri_sso_default_settings( $key );
	}
	$settings = get_option( 'uri_sso', $default );

	if ( is_multisite() ) {
		// local settings are meant to override site settings, 
		// but if they're blank, use the network settings.  merge with network settings.
		$site_settings = get_site_option( 'uri_sso', $default );
		if ( is_array( $settings ) ) {
			foreach ( $settings as $k => $v ) {
				if ( '' === $settings[$k] ) {
					$settings[$k] = $site_settings[$k];
				}
			}
		} else {
			$settings = $site_settings;
		}
	}

	if ( ! empty ( $key ) ) {
		if( array_key_exists( $key, $settings ) ) {
			return $settings[$key];
		} else {
			return $default;
		}
	} else {
		return $settings;
	}
}


/**
 * Handles deactivation.
 */
function uri_sso_deactivate() {
  delete_option( 'uri_sso' );
}
register_deactivation_hook( __FILE__, 'uri_sso_deactivate' );


