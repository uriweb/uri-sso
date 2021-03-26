<?php
/**
 * Description: Log in screen styles
 * Author: Brandon Fuller <bjcfuller@uri.edu>
 */

/**
 * Include css and js
 */
function uri_sso_login_enqueues() {
	wp_register_style( 'uri-sso-login', URI_SSO_URL . 'css/uri-sso-login.css' );
	wp_enqueue_style( 'uri-sso-login' );
}
add_action( 'login_enqueue_scripts', 'uri_sso_login_enqueues' );

/**
 * Modify login logo url
 */
function uri_sso_login_logo_url() {
	return get_bloginfo( 'url' );
}
add_filter( 'login_headerurl', 'uri_sso_login_logo_url' );

/**
 * Modify login logo tooltip
 */
function uri_sso_login_title() {
	return get_bloginfo( 'name' );
}
if ( version_compare( $wp_version, '5.2', '>=' ) ) {
	add_filter( 'login_headertext', 'uri_sso_login_title' );
} else {
	// login_headertitle is deprecated as of WP 5.2, but web still runs 4.x
	add_filter( 'login_headertitle', 'uri_sso_login_title' );
}

/**
 * Modify the forgot password link
 */
function uri_sso_lost_password( $lostpassword_url, $redirect ) {
	return 'https://password.uri.edu';
}
add_filter( 'lostpassword_url', 'uri_sso_lost_password', 10, 2 );

