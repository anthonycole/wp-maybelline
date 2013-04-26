<?php
/*
Plugin Name: Maybelline
Author: Anthony Cole
Author URI: http://anthonycole.me/
*/

/**
 * WP_Maybelline
 *
 * @package default
 * @author 
 **/

register_activation_hook(__FILE__, array('WP_Maybelline', 'install') );

Class WP_Maybelline 
{
	public static function init()
	{
		if( is_user_logged_in() )
			return true;
		
		add_action('template_redirect', 'WP_Maybelline::listen');
	}

	public static function install()
	{
		$sql = "CREATE TABLE `wp_maybelline` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) DEFAULT NULL,
			  `post_id` int(11) DEFAULT NULL,
			  `timestamp` datetime DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB;";

	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );	
	}

	public static function listen($post_types)
	{
		global $wp_query;

		$post_types = array(
			'post',
			'document'
		);


		if(  $wp_query->is_single && in_array( $wp_query->post->post_type, $post_types ) ) 
		{
			self::log_view( get_current_user_id(), $wp_query->post->ID );
		}
	}

	/**
	 * Add a "view"
	 *
	 * @return void
	 * @author 
	 **/
	public static function log_view($user_id, $post_id, $time = '') {
		global $wpdb;

		if( '' == $time )
			$time = current_time('mysql', 1);

		$vals = array(
			'user_id'    => $user_id,
			'post_id' 	 => $post_id,
			'timestamp'  => $time
		);

		$casting = array(
			'%d',
			'%d',
			'%s'
		);

		$query = $wpdb->insert('wp_maybelline', $vals, $casting);

		do_action('wp_maybelline_add_view');
	}

	/**
	 * Get all of the posts a user has looked at.
	 *
	 * @return void
	 * @author 
	 **/
	public static function get_views_by_user($user_id, $limit = 5) {
		global $wpdb;
		$query = $wpdb->prepare("SELECT DISTINCT * FROM wp_maybelline WHERE user_id = %d LIMIT", $user_id, $limit );
		$wpdb->get_results($query);
		do_action('wp_maybelline_get_user_views', $user_id);
	}
	
	/**
	 * Get all of the users that have looked at a specified post.
	 *
	 * @return void
	 * @author 
	 **/
	public static function get_views_by_post($post_id) {
		global $wpdb;
		$query = $wpdb->prepare("SELECT DISTINCT * FROM wp_maybelline WHERE post_id = %d LIMIT", $post_id, $limit );
		$wpdb->get_results($query);
		do_action('wp_maybelline_get_post_views', $post_id);
	}

}

WP_Maybelline::init();