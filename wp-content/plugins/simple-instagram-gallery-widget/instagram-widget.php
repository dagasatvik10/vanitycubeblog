<?php

class InstagramFWWidget extends WP_Widget
{
	var $pluginDomain = 'ifw_widget';

	function __construct() {
		$widget_ops = array('classname' => 'InstagramFWWidget', 'description' => __('Displays images from Instagram.') );
		parent::__construct('InstagramFWWidget', 'Instagram images', $widget_ops);
	}

	function form($instance)
	{
		$instance = wp_parse_args( (array) $instance, array( 
			'nr_of_images' => '', 
			'hashtag' => '',
			'image_size' => '',
			'username' => '',
		) );
		$nr_of_images = $instance['nr_of_images'];
		$hashtag = $instance['hashtag'];
		$image_size = $instance['image_size'];
		$username = $instance['username'];
?>
  <p><label for="<?php echo $this->get_field_id('nr_of_images'); ?>">
  <?php _e('Number of images', $this->pluginDomain); ?>: </label>
  <input class="widefat" id="<?php echo $this->get_field_id('nr_of_images'); ?>" name="<?php echo $this->get_field_name('nr_of_images'); ?>" type="text" value="<?php echo esc_attr($nr_of_images); ?>" /></p>
  <p>
  <label for="<?php echo $this->get_field_id('hashtag'); ?>"><?php _e('Hashtag', $this->pluginDomain); ?>:</label>
  <input class="widefat" id="<?php echo $this->get_field_id('hashtag'); ?>" name="<?php echo $this->get_field_name('hashtag'); ?>" type="text" value="<?php echo esc_attr($hashtag); ?>" /></p>
  <p>
  <label for="<?php echo $this->get_field_id('username'); ?>"><?php _e('Username', $this->pluginDomain); ?>:</label>
  <input class="widefat" id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>" type="text" value="<?php echo esc_attr($username); ?>" /></p>
  <p>
  <label for="<?php echo $this->get_field_id('image_size'); ?>"><?php _e('Image size (px)', $this->pluginDomain); ?>:</label>
  <input class="widefat" id="<?php echo $this->get_field_id('image_size'); ?>" name="<?php echo $this->get_field_name('image_size'); ?>" type="text" value="<?php echo esc_attr($image_size); ?>" /></p>
  
<?php
	}
 
	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance['nr_of_images'] = $new_instance['nr_of_images'];
		$instance['image_size'] = $new_instance['image_size'];
		
		if(substr($new_instance['hashtag'], 0, 1) == '#')
			$hashtag = substr($new_instance['hashtag'], 1);
		else
			$hashtag = $new_instance['hashtag'];
		
		if(substr($new_instance['username'], 0, 1) == '@')
			$username = substr($new_instance['username'], 1);
		else
			$username = $new_instance['username'];
		
		$instance['hashtag'] = $hashtag;
		$instance['username'] = $username;
			
		// Get user id
		if(!empty($instance['username']))
		{
			$apiurl = "https://api.instagram.com/v1/users/search/?q=".$instance['username']."&access_token=".get_option('instagram_access_token');

			$response = wp_remote_get($apiurl,
				array(
					'sslverify' => apply_filters('https_local_ssl_verify', false)
				)
			);
			if(!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200):
				$data = json_decode($response['body']);
				if($data->meta->code == 200):
					foreach($data->data as $item):
						if($item->username == $instance['username'])
							$instance['user_id'] = $item->id;
					endforeach;
				endif;
			endif;
		}
		
		return $instance;
	}
 
	function widget($args, $instance)
	{
		extract($args, EXTR_SKIP);

		echo $before_widget;
		
		global $wpdb;
		if(!empty($instance['username']))
			$title = '@'.$instance['username'];
		else
			$title = '#'.$instance['hashtag'];
		?>
		<style type="text/css">
		ul.instagram-widget {
			margin: 0 -5px 0 -5px;
			text-align: justify;
			overflow: auto;
			padding: 0;
		}
		ul.instagram-widget > li {
			display: inline-block;
			vertical-align: top;
			zoom: 1;
			margin: 0 5px 10px 5px;
			width: <?php echo $instance['image_size']; ?>px;
		}
		.instagram-widget li {
			list-style-type: none;
			float: left;
		}
		</style>
		<h3 class="widget-title"><i class="fa fa-instagram fa-lg"></i> <?php echo $title; ?></h3>
		<?php		
		$instance['access_token'] = get_option('instagram_access_token');
		$cacheduration = 600;
		$images = wp_cache_get($this->id, 'ifw_instagram_cache');
		if(false == $images):
			$imageraw = get_option('ifw-instagram-widget-cache');
			
			if ($imageraw) {
				$imageraw = unserialize(base64_decode($imageraw));
				
				if (($imageraw['created'] + $cacheduration) > time()) {
					$images = $imageraw['data'];
				}
			}

			if (false == $images) {
				$images = $this->instagram_get_latest($instance);
				wp_cache_set($this->id, $images, 'ifw_instagram_cache', $cacheduration);

				$tocache = array(
					'created' => time(),
					'data' => $images
				);
				
				update_option('ifw-instagram-widget-cache', base64_encode(serialize($tocache)));
			}
		endif;

		$imagetype = "image_small";
		$imagesize = $instance['image_size']+20;
		echo '<ul class="instagram-widget">';
		$limit = $instance['nr_of_images'];
		$i = 1;
		foreach($images as $image):
			if($i <= $limit)
			{
				$imagesrc = $image[$imagetype];
				echo '<li><a href="'.$image['link'].'" data-original="'.$image['image_large'].'" title="'.$image['title'].'" rel="'.$this->id.'">';
				echo '<img src="'.$imagesrc.'" alt="'.$image['title'].'" width="'.$imagesize.'" height="'.$imagesize.'" />';
				echo '</a></li>';
			}
			$i++;
		endforeach;
		echo '</ul>';

		echo $after_widget;
	}
	
	function instagram_get_latest($instance){
		$images = array();
		if($instance['access_token'] != null):
			$hashtag = $instance['hashtag'];
			if (substr($hashtag, 0, 1) == '#')
				$hashtag = substr($hashtag, 1);

			if(!empty($instance['nr_of_images']))
				$limit = '&count='.$instance['nr_of_images'];
			else
				$limit = '';
			
			if(isset($instance['user_id']) && !empty($instance['user_id']))
				$apiurl = "https://api.instagram.com/v1/users/".$instance['user_id']."/media/recent?access_token=".$instance['access_token'].$limit;
			else
				$apiurl = "https://api.instagram.com/v1/tags/".$hashtag."/media/recent?access_token=".$instance['access_token'].$limit;

			$response = wp_remote_get($apiurl,
				array(
					'sslverify' => apply_filters('https_local_ssl_verify', false)
				)
			);
			if(!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200):
				$data = json_decode($response['body']);
				if($data->meta->code == 200):
					foreach($data->data as $item):
						if(isset($instance['hashtag'], $item->caption->text)):
							$image_title = $item->user->username.': &quot;'.filter_var($item->caption->text, FILTER_SANITIZE_STRING).'&quot;';
						elseif(isset($instance['hashtag']) && !isset($item->caption->text)):
							$image_title = "instagram by ".$item->user->username;
						else:
							$image_title = filter_var($item->caption->text, FILTER_SANITIZE_STRING);
						endif;
						$images[] = array(
							"id" => $item->id,
							"title" => $image_title,
							"image_small" => $item->images->thumbnail->url,
							"image_middle" => $item->images->low_resolution->url,
							"image_large" => $item->images->standard_resolution->url,
							"link" => $item->link,
						);
					endforeach;
				endif;
			endif;
		endif;
		return $images;
	}
}	
?>