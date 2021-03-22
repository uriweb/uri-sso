<?php
/**
 * Description: File to create admin settings menu for the Courses Plugin.
 * Version: 0.1
 * Author: John Pennypacker <john@pennypacker.net>
 */


/**
 * Register settings
 */
function uri_sso_register_settings() {

	$group = 'uri_sso';
	$page = 'uri_sso';

	register_setting(
		$group,
		$group,
		'sanitize_settings'
	);

	$section = 'uri_sso_settings';
	
	add_settings_section(
		$section,
		__( 'URI SSO Settings', 'uri' ),
		'_uri_sso_settings_section',
		$page
	);

	add_settings_field(
		'uri_sso_login_url',
		'Login URL',
		'_uri_sso_login_url_field',
		$page,
		$section,
		array('label_for' => 'uri_sso_login_url')
	);

	add_settings_field(
		'uri_sso_logout_url',
		'Logout URL',
		'_uri_sso_logout_url_field',
		$page,
		$section,
		array('label_for' => 'uri_sso_logout_url')
	);

	add_settings_field(
		'user_variables',
		'User variables',
		'_uri_sso_user_keys_field',
		$page,
		$section,
		array('label_for' => 'fallback_variables')
	);

}
add_action( 'admin_init', 'uri_sso_register_settings' );


/**
 * Callback for a settings section
 * @param arr $args has the following keys defined: title, id, callback.
 * @see add_settings_section()
 */
function _uri_sso_settings_section( $args ) {
	// output here appears under the form headline
}

/**
 * Add an options page for this plugin.
 */
function uri_sso_settings_page() {
	add_options_page(
		'URI SSO Settings',
		'URI SSO Settings',
		'manage_options',
		'uri-sso',
		'uri_sso_display_options_page'
	);
}
add_action( 'admin_menu', 'uri_sso_settings_page' );

/**
 * Display the options for this plugin.
 */
function uri_sso_display_options_page() {
	if ( ! current_user_can('manage_options') ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$output = '
	';
?>
<div class="wrap">
	<h2>URI SSO Settings</h2>
	<?php echo _uri_sso_get_instructions(); ?>
  <form action="options.php" method="post">
    <?php
    	settings_errors();
    	settings_fields('uri_sso');
    	do_settings_sections('uri_sso');
    	submit_button( 'Save Settings' );
    ?>
  </form>
</div>
<?php
	}



/**
 * Set the database version on saving the options.
 */
function sanitize_settings( $input ) {
	$output = $input;
	return $output;
}


function _get_text_field( $setting, $help_text, $size=60 ) {
	$value = _uri_sso_get_settings( $setting );
	return '<input type="text" name="uri_sso[' . htmlspecialchars( $setting ) . ']" id="uri_sso_' . htmlspecialchars( $setting ) . '" value="' . htmlspecialchars( $value ) . '" size="' . htmlspecialchars( $size ) . '" /> <p>' . $help_text . '</p>';
}

/**
 * Display the login URI field.
 */
function _uri_sso_login_url_field() {
	$help_text = 'Enter the SSO login URL.<br />
	Default: <code>%base%/mellon/login</code>';
	echo _get_text_field( 'uri_sso_login_url', $help_text );
}

/**
 * Display the logout URI field.
 */
function _uri_sso_logout_url_field() {
	$help_text = 'Enter the SSO logout URL to clear session cookies on the web server.<br />
	Default: <code>%base%/mellon/logout</code>';
	echo _get_text_field( 'uri_sso_logout_url', $help_text );
}

/**
 * Display the alternate $variables keys field.
 */
function _uri_sso_user_keys_field() {
	$help_text = 'A comma-separated list of <code>$_SERVER</code> variables to determine the username.<br />  
	Default: <code>REMOTE_USER, REDIRECT_REMOTE_USER, URI_LDAP_uid</code><br />';
	echo _get_text_field( 'user_variables', $help_text );
}

/**
 * Display the instructions
 */
function _uri_sso_get_instructions() {
	$text = '
		<p>You probably don’t need to change these, each site will use the network default. Proceed with caution.</p>
		<p>For the Login URL and Logout URL options, you can use the following variables to support your installation:</p>
		<ul>
			<li><code>%host%</code> - The host name, currently <code>' . $_SERVER['HTTP_HOST'] . '</code></li>
			<li><code>%site%</code> - The WordPress site front page, currently <code>' . home_url() . '</code></li>
		</ul>';
	return $text;
}
