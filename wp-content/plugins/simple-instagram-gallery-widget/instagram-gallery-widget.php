<?php
ob_start();
/*
Plugin Name: Instagram Gallery Widget
Description: Fetch images from Instagram.
Version: 0.4
Author: Fredrik Starck
Author URI: http://www.starck.nu
*/

global $ifw_current_version;
$ifw_current_version = '0.3';

include_once dirname( __FILE__ ) . '/instagram-widget.php';

set_time_limit(3600); // If the job will be large

	
// Load the widget on widgets_init
function load_ifw_widget() {
	register_widget('InstagramFWWidget');
}
add_action('widgets_init', 'load_ifw_widget');


add_action('admin_menu', 'ifw_admin_menu');
function ifw_admin_menu() {
    add_submenu_page('options-general.php', 'Instagram Settings', 'Instagram Widget', 'manage_options', 'ifw-admin',
        "IFW_Admin");
}
function IFW_Admin()
{
	if ('POST' == $_SERVER['REQUEST_METHOD']) {
		update_option('instagram_client_id', $_POST['instagram_client_id']);
		update_option('instagram_client_secret', $_POST['instagram_client_secret']);
		
		header("Location: https://api.instagram.com/oauth/authorize/?redirect_uri=".plugin_dir_url( __FILE__ )."callback.php&response_type=code&scope=public_content&client_id=".get_option('instagram_client_id'));
	}
	
	require_once dirname( __FILE__ ) . '/admin-layout.php';
	
	echo $Layout;
}

function ifw_required_stylesheets(){
	wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css'); 
}
add_action('wp_enqueue_scripts','ifw_required_stylesheets');

?>