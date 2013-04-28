<?php
/*
Plugin Name: Maybelline
Author: Anthony Cole
Author URI: http://anthonycole.me/
License: GPL V3

WP-Maybelline 
Copyright (C) 2008-2013, Anthony Cole - anthony.c.cole@gmail.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * WP_Maybelline
 *
 * @package default
 * @author anthonycole
 **/

register_activation_hook(__FILE__, array('WP_Maybelline', 'install') );

Class WP_Maybelline 
{
	public static function init()
	{

		add_action('template_redirect', 'WP_Maybelline::listen');
	}

	public static function install()
	{
		$sql = "CREATE TABLE `wp_maybelline` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) DEFAULT NULL,
			  `post_id` int(11) DEFAULT NULL,
			  `timestamp` datetime DEFAULT NULL,
			  `post_type` varchar(255) DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB;";

	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );	
	}

	public static function listen($post_types)
	{
		global $wp_query;

		if( !is_user_logged_in() )
			return true;

		$post_types = array(
			'post',
			'document'
		);

		if(  $wp_query->is_single && in_array( $wp_query->post->post_type, $post_types ) ) 
		{
			self::log_view( get_current_user_id(), $wp_query->post->ID, '', $wp_query->post->post_type );
		}
	}

	/**
	 * Add a "view"
	 *
	 * @return void
	 * @author 
	 **/
	public static function log_view($user_id, $post_id, $time = '', $post_type ) {
		global $wpdb;

		if( '' == $time )
			$time = current_time('mysql', 1);

		$vals = array(
			'user_id'    => $user_id,
			'post_id' 	 => $post_id,
			'timestamp'  => $time,
			'post_type'	 => $post_type
		);

		$casting = array(
			'%d',
			'%d',
			'%s',
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

	public static function get_views_by_post_type()
	{
		global $wpdb;
		$query = $wpdb->prepare("SELECT DISTINCT * FROM wp_maybelline WHERE post_id = %d LIMIT", $post_id, $limit );
		$wpdb->get_results($query);
		do_action('wp_maybelline_get_post_views', $post_id);
	}

	public static function get_results()
	{
		global $wpdb;	
		$query = "SELECT user_id, post_id, wp_posts.post_title, timestamp FROM wp_maybelline LEFT JOIN wp_posts ON wp_posts.ID = wp_maybelline.post_id";
		$dbresults = $wpdb->get_results($query, ARRAY_N);
		return $dbresults;
	}

	public static function get_results_by_user($user_id)
	{
		global $wpdb;
		$query = "SELECT user_id, post_id, wp_posts.post_title, timestamp FROM wp_maybelline INNER JOIN wp_posts ON wp_posts.ID = wp_maybelline.post_id AND wp_maybelline.user_id = %d";
		$dbresults = $wpdb->get_results($wpdb->prepare( $query, $user_id ), ARRAY_N);

		return $dbresults;
	}

	public static function format_csv($content)
	{
		$header = array('User ID', 'Post ID', 'Post Title', 'View Time');
		array_unshift($content, $header);
		return $content;
	}

}

WP_Maybelline::init();


if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class WP_Maybelline_Frontend extends WP_Maybelline
{
	public static function init()
	{
		add_action('admin_menu', array(__CLASS__, 'add_page_menu'));
		add_action('admin_init', array(__CLASS__, 'listen'));
	}

	public static function add_page_menu()
	{
		add_submenu_page('tools.php', __('Maybelline', 'wp-maybelline'), __('Mayblline', 'wp-maybelline'), 'edit_posts', 'maybelline_admin', array(__CLASS__, 'page') );
	}

	public static function generate_csv($results)
	{
	    header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Content-Type: application/force-download");
	    header("Content-Type: application/octet-stream");
	    header("Content-Type: application/download");
	    header("Content-Disposition: attachment;filename=content.csv");
	    header("Content-Transfer-Encoding: binary");

	    $df = fopen("php://output", 'w');

	   foreach ($results as $row) {
	      fputcsv($df, $row);
	   }

	   fclose($df);
	}

	public static function listen()
	{
		if( isset( $_GET['page'] ) && 'maybelline_admin' ==  $_GET['page'] && isset($_REQUEST['mayb_action'])  )   
		{
			switch( $_REQUEST['mayb_action'] ) :
				case "results" : 
					$content = self::get_results();
					self::generate_csv(self::format_csv($content));
				break;

				case "user" : 
					$user_id = $_REQUEST['user_id'];
	
					$content = self::get_results_by_user($user_id);
					self::generate_csv(self::format_csv($content));
				break;

				default : 
					wp_die("These are not the droids you are looking for!");
				break;
			endswitch;	
			exit;
		}
	}

	public static function page()
	{
		?>
		<div class="wrap">
			<h2>Maybelline</h2>
			<p>Maybelline is a tool used for seeing what your users are up to on your WordPress blog.</p>

			<a href="<?php echo admin_url('tools.php?page=maybelline_admin&mayb_action=results'); ?>" class="button">Download all data</a>
			
			<p>User data is available here</p>
			<?php  
			$table  = new WP_Maybelline_List_User_Table();
			$table->prepare_items();
			$table->display('true');
			?>
		</div>
		<?php
	}
}

WP_Maybelline_Frontend::init();


class WP_Maybelline_List_User_Table extends WP_List_Table 
{
	function __construct()
	{
		parent::__construct( array(
		'singular'=> 'wp_list_text_link', //Singular label
		'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
		'ajax'	=> false //We won't support Ajax for this table
		) );
	}

	function get_columns() {
		$columns = array(
			'name'=>__('Name'),
		);

		return $columns;
	}

	function prepare_items() {
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();

    	$users = get_users();

        $totalitems = count($users);

        //How many to display per page?
        $perpage = 5;

        
		//The pagination links are automatically built according to those parameters

		/* -- Register the Columns -- */
		$columns = $this->get_columns();
		$_wp_column_headers[$screen->id]=$columns;

		$userargs = array(
			'offset' => $offset
		);

		$users = get_users( );
	/* -- Fetch the items -- */

		$this->_column_headers = array( 
	 		$this->get_columns(),		// columns
	 		array(),			
	 		array()
		);
		
		$this->items = $users;

	}

	function display_rows() {
		//Get the records registered in the prepare_items method
		$records = $this->items;
		
		//Loop for each record
		if( !empty($records) ) {

			foreach($records as $rec){
				//Open the line
		        echo '<tr id="record_'.$rec->data->user_login.'">';
		        echo '<td><a href="' . admin_url('tools.php?page=maybelline_admin&mayb_action=user&user_id=' . $rec->data->ID ) . '">'.stripslashes($rec->user_nicename).'</a></td>';

				//Close the line
				echo'</tr>';

			}
		}

	}
}