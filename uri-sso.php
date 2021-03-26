<?php
/**
 * Plugin Name: URI SSO
 * Plugin URI: http://www.uri.edu
 * Description: authenticate users against URI's SSO implementation
 * Version: 0.1
 * Author: URI Web Communications
 * Author URI: 
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

// include the convenience functions
include_once( URI_SSO_PATH . 'inc/uri-sso-settings.php' );

// include the log in screen customizations
include_once( URI_SSO_PATH . 'inc/uri-sso-login-screen.php' );

// remove default WP auth filters
remove_all_filters( 'authenticate' );

// hide password fields on the profile pages
add_filter( 'show_password_fields', '__return_false' );
add_filter( 'allow_password_reset', '__return_false' );



/**
 * Modify the introduction on the log in screen
 */
function uri_sso_login_message( $message ) {

	$username = _uri_sso_check_remote_user();
	
	if ( ! is_wp_error( $username ) ) {
		_uri_sso_has_session_css();
		add_filter( 'gettext', '_uri_sso_change_login_button', 20, 3 );
		return '<p>Hi, <span class="username">' . $username . '</span>. You‘re logged in with single sign on, but not logged into WordPress.</p>';
	} else {
		// echo '<pre>', print_r($_SERVER, TRUE), '</pre>';
		// return 'Uh oh, you are not logged in with SSO.';
	}

	return $message;
}
add_filter( 'login_message', 'uri_sso_login_message' );


/**
 * Modify the custom styles messages on the log in screen
 */
function uri_sso_login_messages( $messages ) {
	if ( '%09You+are+now+logged+out.%3Cbr+%2F%3E%0A' == urlencode( $messages ) ) {
		$messages = "\t" . 'You are now logged out of WordPress.<br />' . "\n";

		if ( ! is_wp_error( _uri_sso_check_remote_user() ) ) {
			$return = urlencode( home_url() );
			$messages .= '<a href="https://staging.web.uri.edu/mellon/logout?ReturnTo=' . $return . '">Log out of the web server</a>.';
		}
	}
	return $messages;
}
add_filter( 'login_messages', 'uri_sso_login_messages', 20, 1 );


/**
 * Authenticate the user using the environment variables.
 */
function uri_sso_authenticate( $user, $username, $password ) {

	// if the user is logging out, let them.
	if( ( isset( $_GET['action'] ) && 'logout' == $_GET['action']) || ( isset( $_GET['loggedout'] ) && 'true' == $_GET['loggedout'] ) ) {
		return $user;
	}

	if ( empty( $username ) ) {
		$username = _uri_sso_check_remote_user();
	}
	
	// echo '<pre>', print_r( $username, TRUE ), '</pre>';

	if ( is_wp_error( $username ) ) {
// 		wp_redirect( _uri_sso_get_login_url() );
// 		exit;
		return $username;
	} else {	
		// check if the username has a WP account; blog_id 0 is for multisite
		$users = get_users( array( 'login' => $username, 'blog_id' => 0 ) );		
		if( 1 === count( $users ) && 'WP_User' === get_class( $users[0] ) ) {
			return $users[0];
		}
		
		// still here? create a new user
		$user = _uri_sso_create_user( $username );
		return $user;
	}

	return $user;
}
add_filter( 'authenticate', 'uri_sso_authenticate', 10, 3 );
// add_filter( 'wp_authenticate_user', 'uri_sso_authenticate', 10, 3 );


/**
 * Send the user to an appropriate page after logging in
 */
function uri_sso_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	
	if ( ! is_wp_error( $user ) ) {
		if ( in_array( 'subscriber', (array) $user->roles ) ) {
			// send subscribers to the front page
			return home_url();
		} else {
			// send everyone else to the WP dashboard
			return get_admin_url( get_current_blog_id() );
			//return get_dashboard_url(get_current_user_id());
		}
	}	
}
add_filter( 'login_redirect', 'uri_sso_login_redirect', 10, 3 );


/**
 * Remove the reauth=1 parameter from the login URL, if applicable.
 */
function uri_sso_automatic_reauth( $login_url ) {
	$login_url = remove_query_arg( 'reauth', $login_url );
	return $login_url;
}
add_filter( 'login_url', 'uri_sso_automatic_reauth' );
