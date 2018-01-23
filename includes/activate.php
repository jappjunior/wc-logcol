<?php

function r_activate_plugin() {

	if( version_compare( get_bloginfo( 'version' ), '4.5', '<' ) ){
		wp_die( __ ( 'Você deve atualizar o WordPress para utilizar esse plugin', 'woocommerce-logcol' ) );
	}
}