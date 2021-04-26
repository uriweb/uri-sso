<?php
/**
 * Description: Convenience functions
 * Version: 0.1
 * Author: John Pennypacker <jpennypacker@uri.edu>
 */

// Block direct requests
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}


/**
 * Default values for the admin settings.
 * @param str $key the specific setting to return
 * @return mixed if $key is set, it returns the value of that setting, 
 *  otherwise, array of all settings
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
function uri_sso_get_settings( $key, $default=NULL ) {
	if ( NULL === $default ) {
		$default = uri_sso_default_settings( $key );
	}
	$settings = _uri_sso_get_option( 'uri_sso', uri_sso_default_settings() );
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
 * Include css when the user has an active session, but isn't logged into WordPress.
 */
function _uri_sso_has_session_css() {
	wp_register_style( 'uri-sso-has-session', URI_SSO_URL . '/css/uri-sso-has-session.css' );
	wp_enqueue_style( 'uri-sso-has-session' );
}


/**
 * Change "Log In" to "Log Back In" on wp-login via the WP translation mechanism.
 * @return str
 */
function _uri_sso_change_login_button( $translated_text, $text, $domain ) {
	if ( 'Log In' === $text ) {
		$translated_text = __( 'Log Back In' , 'uri' );
	}
	return $translated_text;
}


/**
 * Convenience function to go to the SSO login endpoint, then back to wp-admin.
 * @return str
 */
function _uri_sso_get_login_url() {
	$return_to = 'ReturnTo=' . urlencode( get_admin_url() );

	$url = uri_sso_get_settings( 'login_url' );
	
	list( $protocol ) = explode( '/', $_SERVER['SERVER_PROTOCOL'] );
	$url = strtolower( $protocol ) . '://' . _uri_sso_swap_tokens( $url );
	
	if( FALSE === strpos( '?', $url ) ) {
		$return_to = '?' . $return_to;
	} else {
		$return_to = '&' . $return_to;
	}

	return $url . $return_to;
}


/**
 * Wrapper for get_option.
 * queries the local value, if empty, returns the network value
 * @see uri_sso_get_settings()
 * @param str $key the option name
 * @param str $default a default value
 * @return mixed
 */
function _uri_sso_get_option( $key, $default=FALSE ) {
	$value = get_option( $key, $default );
	
	if( is_multisite() ) {
// 		@todo: implement a network-wide set of values
// 		if ( $default === $value ) {
// 			$value = get_network_option( NULL, $key, $default );
// 		}
		if ( $default === $value ) {
			$value = get_site_option( $key, $default );
		}


		// still no value
		if ( $default === $value ) {
			get_blog_option(get_network()->site_id, $key, $default);
		}
	
	}
	return $value;
}




/**
 * Check the value provided by the server.
 * @return mixed
 */
function _uri_sso_check_remote_user() {

	$username = _uri_sso_get_environment_variable( 'user_variables' );

	if ( NULL === $username ) {
		$message = '<strong>ERROR</strong>: No SSO session found.';
		return new WP_Error( 'empty_username', __( $message, 'uri' ) );
	}

	return trim( $username );
}

/**
 * Checks for a value in the environment variables and returns it.
 * @param str $key is the name of the variable that we're looking for 
 * @see uri_sso_default_settings
 * @param str $default can be used to override the default output when a value doens't exist.
 * @return str
 */
function _uri_sso_get_environment_variable( $key, $default=NULL ) {
	$output = $default;

	$keys = array_reverse( _uri_sso_get_user_variables( $key ) );

	foreach ( $keys as $k ) {
		if ( ! empty( $_SERVER[$k] ) ) {
			$output = $_SERVER[$k];
		}
	}
	
	return $output;
}


/**
 * Converts a string of variable names to an array.
 * e.g. for example, the user name defaults are REMOTE_USER, REDIRECT_REMOTE_USER, and URI_LDAP_uid. 
 * @param str $str is the name of the variable that we're looking for 
 * @see uri_sso_default_settings
 * @return arr
 */
function _uri_sso_get_user_variables( $str ) {
	$keys = _uri_sso_get_option( $str, uri_sso_default_settings( $str ) );

	if ( ! empty( $keys ) ) {
		$keys = explode(',', $keys);
		$keys = array_map( 'trim', $keys );
	}

	return array_unique( $keys );
}

/**
 * Create a new WordPress account for the specified username.
 * @todo populate name
 */
function _uri_sso_create_user($username) {
	$email = _uri_sso_get_email( $username );
	$role = uri_sso_get_settings( 'default_role' );
	
	$user_metadata = _uri_sso_get_name();
	
	$userdata = array(
		'user_login' =>  $username,
		'user_email'   =>  $email,
		'user_pass'  =>  wp_generate_password(),
		'first_name' => $user_metadata['first_name'],
		'last_name' => $user_metadata['last_name'],
		'role' => $role,
	);

	$user_id = wp_insert_user( $userdata ) ;

	// it worked
	if ( ! is_wp_error( $user_id ) ) {
		$user = get_user_by( 'id', $user_id );
		return $user;
	} else {
		// @todo: create error message for first time users 
	}

}

/**
 * Get the email address from the server, or generate it programmatically.
 * @return str
 */
function _uri_sso_get_email( $username ) {
	$email = '';
	if ( ! empty ( $_SERVER['URI_LDAP_mail'] ) ) {
		$email = $_SERVER['URI_LDAP_mail'];
	} else {
		$domain = 'uri.edu';
// 		if ( isset( $_SERVER['URI_LDAP_employeetype'] ) && $_SERVER['URI_LDAP_employeetype'] == 'student' ) {
// 			$domain = 'my.uri.edu';
// 		}
//		$email = $username . '+autogenerated@' . $domain;
		$email = $username . '@' . $domain;
	}
	return $email;
}

/**
 * Get the user's name from the server
 * someday, this could be a separate LDAP query
 * @return arr
 */
function _uri_sso_get_name() {
	return array(
		'first_name' => _uri_sso_get_environment_variable( 'first_name_variable' ),
		'last_name' => _uri_sso_get_environment_variable( 'last_name_variable' ),
	);
}

/**
 * Replace tokens permitted in the admin screen
 * @param str $str the input
 * @return str
 */
function _uri_sso_swap_tokens( $str ) {

	list( $protocol ) = explode( '/', $_SERVER['SERVER_PROTOCOL'] );
	
	$tokens = array(
		'host' => strtolower( $protocol ) . '://' . $_SERVER['HTTP_HOST'],
		'site' => home_url()
	);

	foreach ($tokens as $token => $value) {
		$str = str_replace('%' . $token . '%', $value, $str);
	}

	return $str;
}

