<?php
/*
Plugin Name: URI SSO
Plugin URI: http://www.uri.edu
Description: authenticate users against URI's SSO implementation
Version: 0.1
Author: John Pennypacker
Author URI: 
*/


// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');

define( 'URI_SSO_PATH', plugin_dir_path( __FILE__ ) );
define( 'URI_SSO_URL', str_replace('/assets', '/', plugins_url( 'assets', __FILE__ ) ) );

// include the convenience functions
include_once( URI_SSO_PATH . 'inc/uri-sso-utility-functions.php' );


add_filter( 'show_password_fields', '__return_false' );
add_filter( 'allow_password_reset', '__return_false' );




/**
 * Modify the message on the log in screen
 */
function uri_sso_login_message( $message ) {

	$username = _uri_sso_check_remote_user();
	
	if ( ! is_wp_error( $username ) ) {
		return 'Hi, ' . $username . '. You‘re logged in with single sign on, click log in to continue.';
	} else {
		//echo '<pre>', print_r($_SERVER, TRUE), '</pre>';
		return 'Uh oh, you are not logged in with SSO.';
	}

	return $message;
}
add_filter( 'login_message', 'uri_sso_login_message' );


/**
 * Modify the forgot password link
 */
function uri_sso_lost_password( $lostpassword_url, $redirect ) {
	return 'https://password.uri.edu';
}
add_filter( 'lostpassword_url', 'uri_sso_lost_password', 10, 2 );

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
		wp_redirect( _uri_sso_get_login_url() );
		exit;
		remove_action( 'authenticate', 'wp_authenticate_username_password', 20 );
		remove_action( 'authenticate', 'wp_authenticate_email_password', 20 );
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
remove_all_filters( 'authenticate' );
add_filter( 'authenticate', 'uri_sso_authenticate', 10, 3 );
// add_filter( 'wp_authenticate_user', 'uri_sso_authenticate', 10, 3 );


/**
 * Send the user to site's front page when logging out.
 */
function uri_sso_logout() {
// 	wp_redirect( home_url() );
// 	exit;
}
add_action( 'wp_logout', 'uri_sso_logout' );

/**
 * Send the user to an appropriate page after logging in
 */
function uri_sso_login_redirect( $url, $request, $user ) {
	
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
 * Filters the login URL.
 *
 * @param string $login_url    The login URL. Not HTML-encoded.
 * @param string $redirect     The path to redirect to on login, if supplied.
 * @param bool   $force_reauth Whether to force reauthorization, even if a cookie is present.
 *
 * @return string
 */
function uri_sso_custom_login_url( $login_url, $redirect, $force_reauth ){
	return _uri_sso_get_login_url();
}
//add_filter( 'login_url', 'uri_sso_custom_login_url', 10, 3 );


/**
 * Fires when a visitor goes to wp-login.php
 */
function uri_sso_handle_default_login_page() {
	echo '<style>';
	echo 'p, .user-pass-wrap { display: none; }';
	echo 'p.submit { display: block; }';
	echo 'p.submit .button-primary { float: none; display: block; width: 100%; font-size: 1.2rem; }';
	echo '</style>';
//	echo '<pre>remote user: ', print_r( $_SERVER['REMOTE_USER'], TRUE ), '</pre>';
// 	wp_redirect( _uri_sso_get_login_url() );
// 	exit;
}
add_action( 'login_init', 'uri_sso_handle_default_login_page' );

