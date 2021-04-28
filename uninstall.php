<?php

	delete_option( 'uri_sso' );
	
	if ( is_multisite() ) {
		delete_site_option( 'uri_sso' );
	}

?>