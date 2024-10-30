<?php
/*
Plugin Name: bhJaikuWidget
Plugin URI: http://blog.burninghat.net
Description: Add up to 9 sidebar widgets to display your Jaiku Updates
Version: 0.2.2
Author: Emmanuel Ostertag alias burningHat
Author URI: http://blog.burninghat.net
License: GPL

Copyright 2008  Emmanuel Ostertag alias burningHat (email : webmaster _at_ burninghat.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function bhJW_widget_init(){
	// Widget capable ?
	if ( !function_exists('wp_register_sidebar_widget') || !function_exists('wp_register_widget_control') )
		return;

	// Display widgets
	function bhJW_widget($args, $number = 1){
		extract($args);
		
		// init widget options
		$options = get_option('bhJaikuWidget');
		$title = $options[$number]['title'];
		$account = $options[$number]['account'];
		$nb_updates = $options[$number]['nb_updates'];
		
		//$userstream = 'http://' . $account . '.jaiku.com/feed/';
		$userstream = 'http://' . $account .'.jaiku.com/feed/rss'; // bug 'jaiku rss error' correction - 2008-06-30
		
		include_once(ABSPATH . WPINC . '/rss.php');
		$rss = fetch_rss($userstream);
		$maxitems = $nb_updates;
		$items = array_slice($rss->items, 0, $maxitems);
		
			
		// output	
		echo $before_widget . $before_title . $title . $after_title;
		
		echo "<ul>\n";
		if ( empty($items) ){
			echo'<li>not yet updated or invalid account (check your widget configuration please)</li>';
		} else {
		foreach ( $items as $item){
?>
			<li>
				<?php echo $item['title'] ?> -
				<a href="<?php echo $item['guid'] ?>" title="<?php echo $item['title']; ?>">
					<?php echo $item['jaiku']['timesince'] ?>
				</a>
			</li>
<?php
		}
		}
		
		echo "</ul>\n";
		echo $after_widget;	
	}
	
	// Control widgets
	function bhJW_widget_control($number){
		// get actual options
		$options = $newoptions = get_option('bhJaikuWidget');
		
		if ( !is_array($options) ){
			$options = $newoptions = array(
				'number' => 1,
				1 => array(
					'account' => '',
					'title' => __('My Jaiku', 'bhJW'),
					'nb_updates' => '5'
				)
			);
		}
		
		if ( isset($_POST["bhJW-submit-$number"]) ){
			$newoptions[$number]['title'] = trim(strip_tags(stripslashes($_POST["bhJW-title-$number"])));
			$newoptions[$number]['account'] = trim(strtolower(strip_tags(stripslashes($_POST["bhJW-account-$number"]))));
			$newoptions[$number]['nb_updates'] = stripslashes($_POST["bhJW-nb_updates-$number"]);
			
			// if title not set, use default
			if ( empty($newoptions[$number]['title']) ) $newoptions[$number]['title'] = __('My Jaiku', 'bhJW');
			
			// if nb_update not set or overload, use default
			if ( empty($newoptions[$number]['nb_updates']) || $newoptions[$number]['nb_updates'] < 1 || $newoptions[$number]['nb_updates'] > 30 ) $newoptions[$number]['nb_updates'] = 5;
			
			// check if account is valid
			require_once(ABSPATH . WPINC . '/rss.php');
			$rss = fetch_rss('http://' . $newoptions[$number]['account'] . '.jaiku.com/feed/rss');
			if ( !is_object($rss) ){
				$newoptions[$number]['account'] = "Erreur: ". $newoptions[$number]['account'] ." n'est pas un compte Jaiku valide !";
			}
		}
		
		if ( $options != $newoptions ){
			$options = $newoptions;
			update_option('bhJaikuWidget', $options);
		}
		
		$title = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
		if ( '' == trim($title) )
			$title = __('My Jaiku', 'bhJW');
		
		$account = htmlspecialchars($options[$number]['account'], ENT_QUOTES);
		$nb_updates = (int)htmlspecialchars($options[$number]['nb_updates'], ENT_QUOTES);
		if ( 0 == $nb_updates )
			$nb_updates = 5;
		
	?>
		<p style="text-align: left">
			<label for="bhJW-title-<?php echo $number; ?>"><?php _e('Title:', 'bhJW'); ?><br />
			<input style="width: 100% !important" type="text" id="bhJW-title-<?php echo $number; ?>" name="bhJW-title-<?php echo $number; ?>" value="<?php echo $title; ?>" /></label>
		</p>
		
		<p style="text-align: left">
			<label for="bhJW-account-<?php echo $number; ?>"><?php _e("Your Jaiku's screen name", 'bhJW'); ?><br />
			<input style="width: 100% !important" type="text" id="bhJW-account-<?php echo $number; ?>" name="bhJW-account-<?php echo $number; ?>" value="<?php echo $account; ?>" /></label>
		</p>
		
		<p style="text-align: left">
			<label for="bhJW-nb_updates-<?php echo $number; ?>"><?php _e('Number statuses (max 30)', 'bhJW'); ?><br />
			<input style="width: 10% !important" type="text" id="bhJW-nb_updates-<?php echo $number; ?>" name="bhJW-nb_updates-<?php echo $number; ?>" value="<?php echo $nb_updates; ?>" /></label>
		</p>
		
		<input type="hidden" id="bhJW-submit-<?php echo $number; ?>" name="bhJW-submit-<?php echo $number; ?>" value="1" />
	
	<?php
	}
	
	function bhJW_widget_setup(){
		$options = $newoptions = get_option('bhJaikuWidget');
		
		if ( isset($_POST['bhJW-number-submit']) ){
			$number = (int) $_POST['bhJW-number'];
			if ( $number > 9 ){
				$number = 9;
			} elseif ( $number < 1 ){
				$number = 1;
			}
			$newoptions['number'] = $number;
		}
		
		if ( $options != $newoptions ){
			$options = $newoptions;
			update_option('bhJaikuWidget', $options);
			bhJW_widget_register($options['number']);
		}
	}
	
	function bhJW_widget_page(){
		$options = $newoptions = get_option('bhJaikuWidget');
?>
<div class="wrap">
	<form method="post">
		<h2><?php _e('Jaiku Widgets', 'bhJW'); ?></h2>
		<p style="line-height: 30px"><?php _e('How many Jaiku widgets would you like?', 'bhJW'); ?>
		<select id="bhJW-number" name="bhJW-number" value="<?php echo $options['number']; ?>">
<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number'] == $i ? "selected='selected'" : '').">$i</option>"; ?>
		</select>
		<span class="submit"><input type="submit" name="bhJW-number-submit" id="bhJW-number-submit" value="<?php echo attribute_escape(__('Save', 'bhJW')); ?>" /></span></p>
	</form>	
</div> 
<?php
	}
	

	// Upgrade bhJW_widget from <0.1.2
	function bhJW_upgrade(){
		$options = get_option('bhJaikuWidget');
		$newoptions = array('number' => 1, 1 => $options);
		update_option('bhJaikuWidget', $newoptions);
		
		return $newoptions; 
	}
	
	function bhJW_widget_register(){
		$options = get_option('bhJaikuWidget');
		
		// need update ?
		if ( is_array($options) && !isset($options['number'])){
			$options = bhJW_upgrade();
		}

		$number = $options['number'];
		//$number = 2;
		
		if ( $number < 1 ){
			$number = 1;
		} elseif ( $number > 9 ){
			$number = 9;
		}
		
		for ( $i = 1 ; $i <= 9 ; $i++ ){
			wp_register_sidebar_widget('widget_jaiku-'.$i, sprintf(__('Jaiku %d', 'bhJW'), $i), $i <= $number ? 'bhJW_widget' : '', array('classname' => 'widget_jaiku'), $i);
			wp_register_widget_control('widget_jaiku-'.$i, sprintf(__('Jaiku %d', 'bhJW'), $i), $i <= $number ? 'bhJW_widget_control' : '', array('width' => 350, 'height' => 155), $i);
		}
		add_action('sidebar_admin_setup', 'bhJW_widget_setup');
		add_action('sidebar_admin_page', 'bhJW_widget_page');
	}
	
	// Launch Widgets
	bhJW_widget_register();
}

function bhJW_textdomain(){
	$locale = get_locale();
	if ( empty($locale) ){
		$locale = 'en_US';
	} else {
		$path = basename(str_replace('\\', '/', dirname(__FILE__)));
		$path = ABSPATH.PLUGINDIR.'/'.$path;
		$mofile = $path.'/'.$locale.'.mo';
		load_textdomain('bhJW', $mofile);
	}
}

// Run
add_action('init', 'bhJW_textdomain');
add_action('widgets_init', 'bhJW_widget_init');
?>