<?php
	/*
		Plugin Name: ViralURL WordPress Plugin
		Plugin URI: http://ViralPlugin.com/wordpress
		Description: Easily cloak your links & monetize WordPress!  The ViralURL WordPress Plugin allows you to automatically insert your ViralURL.com Link Cloaker & Shortener links in blog posts based on keywords, allowing you to protect and track affiliate links.
		Version: 1.2.7
		Author: Colin Klinkert & Frank Bauer
		Author URI: http://ViralURLs.com
	*/
	
	include('vpadministration.php');
	
	register_activation_hook(__FILE__,'vpurl_install');
	
	function vpurl_install()
	{
		global $wpdb;
		$latest = 1;
		
		$table_name = $wpdb->prefix . "vpurl_keywords";
		$ver = get_option('vpurl_db_version', 0);
		
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
		{
			$sql = "CREATE TABLE `$table_name` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`keyword_name` VARCHAR( 200 ) NOT NULL ,
					`affiliate_link` VARCHAR( 200 ) NOT NULL ,
					`cloaked_link` VARCHAR( 200 ) NOT NULL ,
					`statusbar` VARCHAR( 200 ) NOT NULL ,
					`replacement_count` TINYINT( 5 ) UNSIGNED NOT NULL ,
					`weight` TINYINT( 5 ) UNSIGNED NOT NULL ,
					`new_window` BOOL NOT NULL DEFAULT '0',
					`no_follow` BOOL NOT NULL DEFAULT '0'
				);";
				
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		
		update_option('vpurl_db_version', $latest);
	}
?>
