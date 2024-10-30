<?php
/*
Plugin Name: Honey Coinhive Locker
Description: Add coinhive content locker into posts and pages.
Version: 1.0.0
Author: Honey Plugins
Author URI: http://honeyplugins.com
Text Domain: honey-coinhive-locker
Domain Path: /assets/languages/
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Do not let plugin be accessed directly
 **/
if ( ! defined( 'ABSPATH' ) ) {
    write_log( "Plugin should not be accessed directly!" );
    exit; // Exit if accessed directly
}

function chcl_shortcode($atts = [], $content = null)
{
	if( isset( $atts['hashes'] ) ){
		$hashesRequired = $atts['hashes'];
	}else{
		$hashesRequired = get_option('chcl_hashes');	
	}
 	$token = chcl_create_data_token();
	$locked_items = get_option('locked_items');
	$locked_items[$token]['data'] = $content;
	$locked_items[$token]['hashes'] = $hashesRequired;
	$locked_items[$token]['timestamp'] = time();
	update_option('locked_items', $locked_items);
 	$content = chcl_render_locker_captcha($hashesRequired, $token);
    return $content;
}
add_shortcode('honey-locker', 'chcl_shortcode');

// Options Page
function chcl_register_settings()
	{
	add_option('chcl_site_key', '');
	add_option('chcl_secret_key', '');
	add_option('chcl_hashes', 256);
	add_option('chcl_color_setting', '#f5d76e');
	add_option('chcl_bootstrap_button', false);
	register_setting('chcl_options_group', 'chcl_site_key', 'chcl_callback');
	register_setting('chcl_options_group', 'chcl_secret_key', 'chcl_callback');
	register_setting('chcl_options_group', 'chcl_hashes', 'chcl_callback');
	register_setting('chcl_options_group', 'chcl_color_setting', 'chcl_callback');
	register_setting('chcl_options_group', 'chcl_bootstrap_button', 'chcl_callback');
	}

add_action('admin_init', 'chcl_register_settings');

function chcl_register_options_page()
	{
	add_options_page('Coinhive Content Locker', 'Coinhive Content Locker', 'manage_options', 'chcl', 'chcl_options_page');
	}

add_action('admin_menu', 'chcl_register_options_page');

function chcl_admin_notice() {
	echo '<div class="updated"><p>Use <code>[honey-locker]Your Content Here[/honey-locker]</code> in posts or pages to lock your content.</p>
	<p>Or use <code>&lt;?php echo do_shortcode(\'[honey-locker]Your Content Here[/honey-locker]\'); ?&gt;</code>  in theme files to lock your content.</p>
	</div>';
}

// Enable the use of shortcodes in text widgets.
add_filter( 'widget_text', 'do_shortcode' );

function chcl_options_page()
	{
?>
<div style="background-color: white;">
   <?php screen_icon(); ?>
   	<?php
	if ( chcl_api_keys_set() ) {
		chcl_admin_notice();
	}
	?>
   <h2><?php echo __('Coinhive Content Locker', 'honey-coinhive-locker'); ?></h2>
   <form method="post" action="options.php">
      <?php settings_fields( 'chcl_options_group' ); ?>
      <h3><?php echo __('API Settings', 'honey-coinhive-locker'); ?></h3>
      <p><label for="chcl_site_key"><?php echo __('Site Key', 'honey-coinhive-locker'); ?>:</label><br>
         <input type="text" id="chcl_site_key" name="chcl_site_key" value="<?php echo get_option('chcl_site_key'); ?>" />
      </p>
      <p><label for="chcl_secret_key"><?php echo __('Secret Key', 'honey-coinhive-locker'); ?>:</label><br>
         <input type="password" id="chcl_secret_key" name="chcl_secret_key" value="<?php echo get_option('chcl_secret_key'); ?>" />
      </p>
      <p><?php echo __('Get your Site Key and Secret Key from', 'honey-coinhive-locker'); ?> <a href="https://coinhive.com/settings/sites" target="_blank">Coinhive.com</a></p>
      <h3><?php echo __('Locker Settings', 'honey-coinhive-locker'); ?></h3>
      <p><label for="chcl_hashes"><?php echo __('Default Hashes', 'honey-coinhive-locker'); ?>:</label><br>
         <input type="number" id="chcl_hashes" name="chcl_hashes" value="<?php echo get_option('chcl_hashes'); ?>" min="0" step="256"/>
      </p>
      <p><label for="chcl_color_setting"><?php echo __('Color', 'honey-coinhive-locker'); ?>:</label><br>
         <input type="text" name="chcl_color_setting" value="<?php echo get_option('chcl_color_setting'); ?>" class="chcl-color-picker" >
      </p>
      <p><label for="chcl_bootstrap_button"><?php echo __('Bootstrap Button', 'honey-coinhive-locker'); ?>:</label><br>
         <?php echo '<input type="checkbox" id="chcl_bootstrap_button" name="chcl_bootstrap_button" value="1"' . checked( 1, esc_attr( get_option( 'chcl_bootstrap_button' ) ), false ) . '/>Use bootstrap styling for the unlock button'; ?>
      </p>
      <?php  submit_button(); ?>
   </form>
</div>
<?php
	}

function chcl_add_settings_link($links)
	{
	$settings_link = '<a href="options-general.php?page=chcl">' . __('Settings', 'honey-coinhive-locker') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
	}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'chcl_add_settings_link');

function chcl_enqueue_boot()
	{
	wp_register_style('chcl_bootstrap_style', plugins_url('js/bootstrap.min.css', __FILE__));
	wp_enqueue_style('chcl_bootstrap_style');
	}

add_action('init', 'chcl_enqueue_boot');

add_action('plugins_loaded', 'chcl_load_textdomain');

function chcl_load_textdomain()
	{
	load_plugin_textdomain('honey-coinhive-locker', false, basename(dirname(__FILE__)) . '/assets/languages');
	}

function chcl_render_locker_captcha($hashesRequired, $token) {
	if ( ! chcl_api_keys_set() ) {
		return "<div class='verifyCHLocker'><h4 style='color: red;'>".__('Coinhive API Settings are not set.', 'honey-coinhive-locker')."</h4>,/div>";
	}
	if ( chcl_api_keys_set() ) {
		$content  = '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';
		$content .= '<input type="hidden" value="'.$token.'" class="input frontend_token" name="frontend_token_name">';
		$content .= '<input type="hidden" value="'.$hashesRequired.'" class="input hashes_required" name="hashes_required_name">';
		$content .= '<div class="contentCHLocker" style="display:none"></div>';
		$content .= "<div class='verifyCHLocker'><div class='content-locked-text'>Content Locked</div>";
		$content .= "<div>Click <strong>Unlock</strong> to view this content" . "</div>";
		$content .= '<div style="margin-top: 15px"><i class="material-icons content-locked-icon">lock</i></div>';
 		$content .= '<div class="barCHLocker">	
					<div class="myProgress">
					  <div class="currCHLocker"></div>
					</div>
		</div>';
 		if(get_option( 'chcl_bootstrap_button' )==true){
 			$content .= '<button class="btn btn-outline-warning colorize startCHLocker">' . __('Unlock', 'honey-coinhive-locker') . '</button>';
 		}else{
 			$content .= '<button class="startCHLocker">'.__('Unlock', 'honey-coinhive-locker').'</button>';
 		}
		$content .= '<div class="logoHoneyFix"><img class="logoHoney" src="'.plugins_url( '/images/honeylogo.png', __FILE__ ).'" alt="Honey Logo"><div class="honey_text">Honey</div></div>		
		</div>';
		return $content;
	}
}

function chcl_add_inline_css() {
	
	wp_enqueue_style('chcl_css', plugins_url('css/styles.php', __FILE__));
	$color_setting = get_option('chcl_color_setting', '#f5d76e');
    $chcl_custom_css = "
		    		.colorize {
						border-color:{$color_setting} !important;
						color:{$color_setting} !important;
						text-decoration: none !important;
					}

					.colorize:hover {
						color:black !important;
						border-color:{$color_setting} !important;
						background-color:{$color_setting} !important;
					}

					.colorize:focus {
						border-color:{$color_setting} !important;
						background:none !important;
						box-shadow: none !important;
					}
					.honey_text {
							font-size:10px; margin-top:-5px; margin-right:5px;				
					}
					.logoHoney {
						width:40px !important; 
						height:40px !important;
					}
					.logoHoneyFix {
						margin-left:auto; margin-right:0;
						margin-top:-37px;
						width:40px !important; 
						height:40px !important;
						text-align:right;
					}
					.myProgress {
					  width: 100%;
					  background-color: #ddd;
					  margin-bottom:10px;
					}	
					.currCHLocker {
					  width: 1%;
					  height: 30px;
					  background-color: {$color_setting};
					  max-width: 100%;
					}
					#frontend_token {
						display: none;
					}
					.content-locked-text{
						font-size:40px;
						font-weight:bold;
					}
					.content-locked-icon{
						font-size:50px !important;
					}
					.verifyCHLockerClick {
						font-size:20px; padding-left:20px; background:white;
					}
					.verifyCHLocker {
						padding: 25px 15px; margin: 5px 0; background-color: #e6e6e6; min-width:250px; text-align:center;
					}
					.contentCHLocker {
						display: none;
					}
					.verifyText {
						font-size:15px; padding-left:10px; padding-top:10px; font-weight: 100;
					}
					.barCHLocker {
						display: none;
					}				
					input[disabled] {pointer-events:none}
					
					.chclLoading {
							-webkit-animation: rotation 2s infinite linear;
					}

					@-webkit-keyframes rotation {
							from {
									-webkit-transform: rotate(0deg);
							}
							to {
									-webkit-transform: rotate(359deg);
							}
					}
	";

  wp_add_inline_style( 'chcl_css', $chcl_custom_css );

}
add_action( 'wp_enqueue_scripts', 'chcl_add_inline_css' );

function chcl_additional_scripts()
	{
		wp_enqueue_script('authedmine', 'http://authedmine.com/lib/authedmine.min.js');
		wp_enqueue_script('miner', plugins_url('js/honey_locker.js?v1', __FILE__) , array( 'jquery'	));
		wp_enqueue_script('chcl_custom_js', plugins_url('js/jquery.custom.js', __FILE__) , array(
			'jquery',
			'wp-color-picker'
		) , '', true);
		$chcl_custom = array(
			'verifying_trans' => __('Verifying...', 'honey-coinhive-locker'),
			'verify_first_trans' => __('Please verify first', 'honey-coinhive-locker'),
			'template_url' => get_bloginfo('siteurl') ,
			'site_key' => base64_encode(get_option('chcl_site_key')) ,
			'site_name' => get_bloginfo('name') ,
			'username' => base64_encode($current_user->user_login) ,
			'hashcount' => get_option('chcl_hashes') ,
			'ajaxurl' => admin_url('admin-ajax.php')
		);
		wp_localize_script('miner', 'chcl_custom', $chcl_custom);
	}

add_action('init', 'chcl_additional_scripts');


function chcl_api_keys_set() {
	if ( get_option( 'chcl_secret_key' ) && get_option( 'chcl_site_key' ) ) {
		return true;
	} else {
		return false;
	}
}

function chcl_create_data_token() {
	try {
		$string = openssl_random_pseudo_bytes(32);
	} catch (TypeError $e) {
		die("An unexpected error has occurred"); 
	} catch (Error $e) {
		die("An unexpected error has occurred");
	} catch (Exception $e) {
		die("Could not generate a random string. Is our OS secure?");
	}
	$chcToken = bin2hex($string);
	return $chcToken;
}

function chcl_save_token() {
	$token = $_POST['token'];
	$token_site = $_POST['token_site'];
	$locked_items = get_option('locked_items');
	$locked_items[$token_site]['token'] = $token;
	update_option('locked_items', $locked_items);
	print($token);
	die();
}

function chcl_fetch_content() {
	$token = $_POST['token'];
	$token_site = $_POST['token_site'];
	$locked_items = get_option('locked_items');
    if (array_key_exists($token_site, $locked_items)) {
    	if($locked_items[$token_site]['token'] == $token){
    		$hashes = $locked_items[$token_site]['hashes'];
    		$secret = get_option('chcl_secret_key');
    		$response = wp_remote_post( 'https://api.coinhive.com/token/verify', array(
			  'method' => 'POST',
			  'body' => array('secret' => $secret,'token'=> $token, 'hashes'=> $hashes)
			  )
			);
			$response = json_decode(($response['body']));
			if($response->success == true && $response->hashes >= $hashes){
				print($locked_items[$token_site]['data']);
			}else if($response->hashes <= $hashes){
				print('not enough honey');
			}else{
				print($response->error);
			}
    	}else{
    		print('token error');
    	}
    	unset($locked_items[$token_site]);
    	update_option('locked_items', $locked_items);
	}
	die();
}

add_action('wp_ajax_nopriv_chcl_save_token', 'chcl_save_token');
add_action('wp_ajax_chcl_save_token', 'chcl_save_token');
add_action('wp_ajax_nopriv_chcl_fetch_content', 'chcl_fetch_content');
add_action('wp_ajax_chcl_fetch_content', 'chcl_fetch_content');

add_filter( 'cron_schedules', 'chcl_add_every_three_minutes' );
function chcl_add_every_three_minutes( $schedules ) {
    $schedules['every_three_minutes'] = array(
            'interval'  => 180,
            'display'   => __( 'Every 3 Minutes', 'honey-coinhive-locker' )
    );
    return $schedules;
}

if ( ! wp_next_scheduled( 'chcl_add_every_three_minutes' ) ) {
    wp_schedule_event( time(), 'every_three_minutes', 'chcl_add_every_three_minutes' );
}

add_action( 'chcl_add_every_three_minutes', 'chcl_every_three_minutes_event_func' );
function chcl_every_three_minutes_event_func() {
	$locked_items = get_option('locked_items');
	foreach ($locked_items as $key => $value) {
		if($value['timestamp'] <= time()-3600){
			unset($locked_items[$key]);
    		update_option('locked_items', $locked_items);
		}
	}
}