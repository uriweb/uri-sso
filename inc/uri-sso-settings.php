<?php
/**
 * Description: File to create admin settings menu for the Courses Plugin.
 * Version: 0.1
 * Author: John Pennypacker <john@pennypacker.net>
 */

// Block direct requests
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}


/**
 * Register settings
 */
function uri_sso_register_settings() {

	$group = 'uri_sso';
	$page = 'uri_sso';

	register_setting(
		$group,
		$group,
		'uri_sso_sanitize_settings'
	);

	$section = 'uri_sso_settings';
	
	add_settings_section(
		$section,
		__( 'URI SSO Settings', 'uri' ),
		'_uri_sso_settings_section',
		$page
	);

	add_settings_field(
		'use_sso',
		'Use SSO',
		'_uri_sso_use_sso',
		$page,
		$section,
		array('label_for' => 'uri_sso_use_sso')
	);

	add_settings_field(
		'login_url',
		'Login URL',
		'_uri_sso_login_url_field',
		$page,
		$section,
		array('label_for' => 'uri_sso_login_url')
	);

	add_settings_field(
		'logout_url',
		'Logout URL',
		'_uri_sso_logout_url_field',
		$page,
		$section,
		array('label_for' => 'uri_sso_logout_url')
	);

	add_settings_field(
		'user_variables',
		'User Variables',
		'_uri_sso_user_variables_field',
		$page,
		$section,
		array('label_for' => 'uri_sso_user_variables')
	);

	add_settings_field(
		'default_role',
		'Default Role',
		'_uri_sso_default_role_field',
		$page,
		$section,
		array('label_for' => 'uri_sso_default_role')
	);

	add_settings_field(
		'first_name_variable',
		'First Name Variable',
		'_uri_sso_first_name_variable_field',
		$page,
		$section,
		array('label_for' => 'uri_sso_first_name')
	);

	add_settings_field(
		'last_name_variable',
		'Last Name Variable',
		'_uri_sso_last_name_variable_field',
		$page,
		$section,
		array('label_for' => 'uri_sso_last_name')
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
	<p>You probably don’t need to change anything on this screen, to use the network default settings, leave these blank. <strong>Proceed with caution</strong>.</p>
	<p>For the URL options, you can use the following tokens:</p>
	<ul>
		<li><code>%host%</code> - The host name, currently <code><?php echo $_SERVER['HTTP_HOST'] ?></code></li>
		<li><code>%site%</code> - The WordPress site front page, currently <code><?php echo home_url() ?></code></li>
	</ul>
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
 * Adds a network options menu item
 */
function uri_sso_register_network_settings_page() {
	add_menu_page(
		__( 'URI SSO Network Settings', 'uri' ),
		'URI SSO',
		'manage_options',
		'uri-sso',
		'uri_sso_display_network_options_page',
		'dashicons-admin-network',
		90
	);
}
add_action( 'network_admin_menu', 'uri_sso_register_network_settings_page' );

/**
 * Display the network options for this plugin.
 */
function uri_sso_display_network_options_page() {
	if ( ! current_user_can('manage_options') ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$output = '
	';
?>
<div class="wrap">
	<h2>URI SSO Network Settings</h2>
	<p>These settings are used as defaults across the entire network, but individual sites may override them.</p>
	<p>For the URL options, you can use the following tokens:</p>
	<ul>
		<li><code>%host%</code> - The host name, currently <code><?php echo $_SERVER['HTTP_HOST'] ?></code></li>
		<li><code>%site%</code> - The WordPress site front page, currently <code><?php echo home_url() ?></code></li>
	</ul>
	<?php
		$action = esc_url( 
			add_query_arg( 
				'action', 
				'uri-sso', 
				network_admin_url( 'edit.php' ) 
			) 
		);
		echo '<form action="' . $action . '" method="post">';
		wp_nonce_field( 'uri-sso', 'uri_sso_validate' );
// 		settings_errors();
		settings_fields('uri_sso');
 		do_settings_sections('uri_sso');
		submit_button( 'Save Settings' );
	?>
  </form>
</div>
<?php
	}

/**
 * Handles submissions from the network settings.
 */
function uri_sso_save_network_settings() {
 	check_admin_referer( 'uri-sso', 'uri_sso_validate' ); // Nonce security check
	$input = uri_sso_sanitize_settings( $_POST['uri_sso'] );	
	update_site_option( 'uri_sso', $input );
	wp_redirect( add_query_arg( array( 'page' => 'uri-sso', 'updated' => TRUE ), network_admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'network_admin_edit_uri-sso', 'uri_sso_save_network_settings' );

/**
 * Displays a 'settings updated' message on the network settings screen.
 */
function uri_sso_custom_notices() {
	if( isset( $_GET['page'] ) && 'uri-sso' === $_GET['page'] && isset( $_GET['updated'] )  ) {
		echo '<div id="message" class="updated notice is-dismissible"><p>URI SSO Settings have been updated.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
	} 
}
add_action( 'network_admin_notices', 'uri_sso_custom_notices' );



/**
 * Sanitize the form input.
 * converts checkbox input to boolean
 */
function uri_sso_sanitize_settings( $input ) {
	$output = $input;
	$output['use_sso'] = isset($input['use_sso']) ? (bool) $input['use_sso'] : false;
	return $output;
}


function _get_text_field( $setting, $help_text, $size=60 ) {
	$value = uri_sso_get_settings( $setting );
	return '<input type="text" name="uri_sso[' . htmlspecialchars( $setting ) . ']" id="uri_sso_' . htmlspecialchars( $setting ) . '" value="' . htmlspecialchars( $value ) . '" size="' . htmlspecialchars( $size ) . '" /> <p>' . $help_text . '</p>';
}

/**
 * Display the use SSO checkbox.
 */
function _uri_sso_use_sso() {
	$value = uri_sso_get_settings('use_sso' );
	$checked = ( $value ) ? ' checked="checked"' : '';
	echo '<input type="checkbox" name="uri_sso[use_sso]" id="use_sso" ' . $checked . '/>';
	echo '<p>Use SSO instead of default WordPress authentication.</p>';	
}


/**
 * Display the login URI field.
 */
function _uri_sso_login_url_field() {
	$help_text = 'Enter the SSO login URL.<br />
	Default: <code>' .  uri_sso_default_settings('login_url') . '</code>';
	echo _get_text_field( 'login_url', $help_text );
}

/**
 * Display the logout URI field.
 */
function _uri_sso_logout_url_field() {
	$help_text = 'Enter the SSO logout URL to clear session cookies on the web server.<br />
	Default: <code>' .  uri_sso_default_settings('logout_url') . '</code>';
	echo _get_text_field( 'logout_url', $help_text );
}

/**
 * Display the alternate $variables keys field.
 */
function _uri_sso_user_variables_field() {
	$help_text = 'A comma-separated list of <code>$_SERVER</code> variables to determine the username.<br />  
	Default: <code>' .  uri_sso_default_settings('user_variables') . '</code><br />';
	echo _get_text_field( 'user_variables', $help_text );
}

/**
 * Display the default role field.
 */
function _uri_sso_default_role_field() {
	$roles = get_editable_roles();

	$value = uri_sso_get_settings( 'default_role' );
	echo '<select name="uri_sso[default_role]" id="default_role">';
		foreach( $roles as $key => $role ) {
			$selected = ( $key == $value ) ? ' selected' : '';
			echo '<option value="' . $key . '" ' . $selected . '>' . $role['name'] . '</option>';
		}
	echo '</select>';
	
	$help_text = 'The role assigned to users who authenticate but don‘t have a WordPress account.<br />  
	Default: <code>' .  uri_sso_default_settings('default_role') . '</code><br />';
	echo '<p>' . $help_text . '</p>';	
}

/**
 * Display the first name field.
 */
function _uri_sso_first_name_variable_field() {
	$help_text = 'A comma-separated list of <code>$_SERVER</code> variables to determine the first name.<br />  
	Default: <code>' .  uri_sso_default_settings('first_name_variable') . '</code><br />';
	echo _get_text_field( 'first_name_variable', $help_text );
}

/**
 * Display the last name field.
 */
function _uri_sso_last_name_variable_field() {
	$help_text = 'A comma-separated list of <code>$_SERVER</code> variables to determine the last name.<br />  
	Default: <code>' .  uri_sso_default_settings('last_name_variable') . '</code><br />';
	echo _get_text_field( 'last_name_variable', $help_text );
}
