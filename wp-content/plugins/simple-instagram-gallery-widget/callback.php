<?php
function wp_path() 
{
	if (strstr($_SERVER["SCRIPT_FILENAME"], "/wp-content/")) 
		return preg_replace("/\/wp-content\/.*/", "", $_SERVER["SCRIPT_FILENAME"]);
	elseif (strstr($_SERVER["SCRIPT_FILENAME"], "/app/")) 
		return preg_replace("/\/app\/.*/", "/wp", $_SERVER["SCRIPT_FILENAME"]);
}
require_once( wp_path() . '/wp-load.php' );
 
 if (isset($_GET['code'])) {

	$client_id = get_option('instagram_client_id');
	$client_secret = get_option('instagram_client_secret');
 
	$response = wp_remote_post("https://api.instagram.com/oauth/access_token",
		array(
			'body' => array(
				'code' => $_GET['code'],
				'response_type' => 'authorization_code',
				'redirect_uri' => plugin_dir_url( __FILE__ ).'callback.php',
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'grant_type' => 'authorization_code',
			),
			'sslverify' => apply_filters('https_local_ssl_verify', false)
		)
	);

	$access_token = null;
	$username = null;
	$image = null;

	$success = false;
	$errormessage = null;
	$errortype = null;

	if(!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200):
		$auth = json_decode($response['body']);
		if(isset($auth->access_token)):
			$access_token = $auth->access_token;
			$user = $auth->user;
			
			update_option('instagram_access_token', $access_token);

			$success = true;
		endif;
        elseif(is_wp_error($response)):
                $error = $response->get_error_message();
                $errormessage = $error;
                $errortype = 'Wordpress Error';
	elseif($response['response']['code'] >= 400):
		$error = json_decode($response['body']);
		$errormessage = $error->error_message;
		$errortype = $error->error_type;
	endif;  

	if (!$access_token):
		delete_option('instagram_access_token');
	endif;
}

header("Location: ".admin_url().'options-general.php?page=ifw-admin');
?>