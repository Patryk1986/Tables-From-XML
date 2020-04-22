<?php 
	if( !defined('WP_UNINSTALL_PLUGIN') )
		exit();

	delete_option('tfx_options');
	delete_option('tfx_tables');
?>