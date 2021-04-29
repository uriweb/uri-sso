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

// include the convenience functions
include_once( URI_SSO_PATH . 'inc/uri-sso-utility-functions.php' );

// include the settings
include_once( URI_SSO_PATH . 'inc/uri-sso-settings.php' );

// include the authentication customizations, is the setting is set
if ( uri_sso_get_settings() ) {
	// include the log in screen customizations
	include_once( URI_SSO_PATH . 'inc/uri-sso-login-screen.php' );
	include_once( URI_SSO_PATH . 'inc/uri-sso-authentication.php' );
}


/**
 * Handles deactivation.
 */
function uri_sso_deactivate() {
  delete_option( 'uri_sso' );
}
register_deactivation_hook( __FILE__, 'uri_sso_deactivate' );

