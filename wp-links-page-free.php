<?php
/**
 * @package wp-links-page-free
 * @version 1.0
 */
/*
Plugin Name: WP Links Page Free
Plugin URI:  http://wordpress.org/extend/plugins/wp-links-page-free/
Description: This plugin provides a screenshot of each link along with your own description 
Version: 1.0
Author: Allyson Rico, Robert Macchi
Author URI:  http://www.wplinkspage.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
 * Creating the WP Links Page Free database table
 */

$upload_dir = wp_upload_dir();
$wplpf_upload = $upload_dir['basedir'].'/'.'wp-links-page-free/';
if( ! file_exists( $wplpf_upload ) )
    wp_mkdir_p( $wplpf_upload );

if (!defined('WPLPF_UPLOAD_DIR')) {
    define('WPLPF_UPLOAD_DIR', $wplpf_upload);
}

if (!defined('WPLPF_UPLOAD_URL')) {
    define('WPLPF_UPLOAD_URL', $upload_dir['baseurl'].'/'.'wp-links-page-free/');
}

global $wp_links_page_free_db_version;
$wp_links_page_free_db_version = '1.4';

function wp_links_page_free_install() {
	global $wpdb;
	global $wp_links_page_free_db_version;

	$table_name = $wpdb->prefix . 'wp_links_page_free_table';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  url varchar(255) DEFAULT '' NOT NULL,
	  display varchar(255) DEFAULT '' NOT NULL,
	  weight mediumint(9) NOT NULL,
	  img varchar(255) NOT NULL,
	  description varchar(255) NOT NULL,
	  UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'wp_links_page_free_db_version', $wp_links_page_free_db_version );
}

function wp_links_page_free_install_data() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wp_links_page_free_table';
	$links = $wpdb->get_results("SELECT * FROM $table_name");
	$img_url = plugins_url( 'wordpressorg.jpg', __FILE__ );
	if (empty($links)) {
	$wpdb->insert( 
		$table_name, 
		array( 
			'url' => 'http://www.wordpress.org', 
			'display' => 'wordpress.org',
			'description' => 'WordPress is web software you can use to create a beautiful website or blog. We like to say that WordPress is both free and priceless at the same time.', 
			'img' => $img_url,
		) 
	);
	}
}

register_activation_hook( __FILE__, 'wp_links_page_free_install' );
register_activation_hook( __FILE__, 'wp_links_page_free_install_data' );
register_activation_hook( __FILE__, 'wp_links_page_free_page_creator' );

/**
 * Run script for new screenshots automatically
 */
 
register_activation_hook( __FILE__, 'wp_links_page_free_setup_schedule' );
/**
 * On activation, set a time, frequency and name of an action hook to be scheduled.
 */

add_option( 'wplpf_screenshot_refresh', 'weekly', '', 'yes' );
add_option( 'wplpf_grid', 'grid', '', 'yes' );
add_option( 'wplpf_width', '4', '', 'yes' );
add_option( 'wplpf_page', 'false', '', 'yes' );
add_action( 'wp', 'wp_links_page_free_setup_schedule');
 
function wp_links_page_free_setup_schedule() {
	$screenshot_refresh = esc_attr( get_option('wplpf_screenshot_refresh') );
	wp_schedule_event( time(), $screenshot_refresh, 'wp_links_page_free_event' );
}

add_action( 'wp_links_page_free_event', 'wp_links_page_free_event_hook' );

register_deactivation_hook( __FILE__, 'wp_links_page_free_deactivation' );
/**
 * On deactivation, remove all functions from the scheduled action hook.
 */
function wp_links_page_free_deactivation() {
	wp_clear_scheduled_hook( 'wp_links_page_free_event' );
	$page_id = get_option('wplpf_page');
	wp_delete_post( $page_id, true );
}

/**
 * On the scheduled action hook, run the function.
 */
function wp_links_page_free_event_hook() {
	global $wpdb;
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$links = $wpdb->get_results("SELECT * FROM $table ORDER BY weight");
	$table_rows = '';
	$i = 1;
	foreach ($links as $link) {
	$screenshot = wplpf_getSSLPage('https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url='.$link->url.'&screenshot=true');
	$data_whole = json_decode($screenshot);
	if (isset($data_whole->error)) {
		die();	
	}
	if (isset($data_whole->screenshot->data)) {
		$data = $data_whole->screenshot->data;
	} else { die();}
	
	if (isset($link->description)) {
		$description = $link->description;	
	} else $description = '';
	
	if (isset($link->url)) {
		$url = $link->url;
		// in case scheme relative URI is passed, e.g., //www.google.com/
		$input = trim($url, '/');
		
		// If scheme not included, prepend it
		if (!preg_match('#^http(s)?://#', $input)) {
			$input = 'http://' . $input;
		}
		
		$urlParts = parse_url($input);
		
		// remove www
		$domain = preg_replace('/^www\./', '', $urlParts['host']);
		// remove punctuation
		$file = preg_replace("/[^a-zA-Z0-9]+/", "", $url);
	}else {die();}
	
    $file = WPLPF_UPLOAD_DIR . $file . '.jpg';
	if(file_exists($file)) unlink($file);
	$data = str_replace('_', '/', $data);
	$data = str_replace('-', '+', $data);
    $base64img = str_replace('data:image/jpeg;base64,', '', $data);
    $data = base64_decode($data);
    file_put_contents($file, $data);
	}
}

function wp_links_page_free_add_intervals($schedules) {
	$schedules['twodays'] = array(
		'interval' => 172800,
		'display' => __('Every Other Day')
	);
	$schedules['threedays'] = array(
		'interval' => 259200,
		'display' => __('Every Three Days')
	);
	$schedules['weekly'] = array(
		'interval' => 604800,
		'display' => __('Weekly')
	);
	$schedules['biweekly'] = array(
		'interval' => 1209600,
		'display' => __('Every Two Weeks')
	);
	$schedules['monthly'] = array(
		'interval' => 2635200,
		'display' => __('Monthly')
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'wp_links_page_free_add_intervals'); 

/**
 * Menu Function adds new links setup page
 */
 
 
 if ( is_admin() ){ // admin actions
  add_action( 'admin_menu', 'wp_links_page_free_menu' );
  add_action( 'admin_init', 'wp_links_page_free_settings' );
} else {
  // non-admin enqueues, actions, and filters
}


function wp_links_page_free_menu() {
	
	$wp_links_free_page = add_menu_page(
        'WP Links Free Setup',
        'WP Links Page Free',
        'manage_options',
        'wp_links_page_free-menu',
        'wp_links_page_free_options',
		'');
		
	$wp_links_free_subpage = add_submenu_page(
        'wp_links_page_free-menu',
        'WP Links Page Free Settings',
        'Settings',
        'manage_options',
		'wp_links_free_subpage-menu',
        'wp_links_free_subpage_options');
		
	$wp_links_free_subpage2 = add_submenu_page(
        'wp_links_page_free-menu',
        'WP Links Page Free Help',
        'Help',
        'manage_options',
		'wp_links_free_subpage2-menu',
        'wp_links_free_help_page');
			
	add_action( 'load-' . $wp_links_free_page, 'load_wp_links_page_free_js' );
	add_action( 'load-' . $wp_links_free_subpage, 'load_wp_links_page_free_js' );	
	add_action( 'load-' . $wp_links_free_subpage2, 'load_wp_links_page_free_js' );		
	
}

function load_wp_links_page_free_js() {
add_action( 'admin_enqueue_scripts', 'enqueue_wp_links_page_free_script' );
}


function enqueue_wp_links_page_free_script($pagenow) {
	wp_enqueue_script( 'jquery-ui-sortable', false, 'jquery', null, false );
	wp_enqueue_script( 'wp-links-free-page', plugins_url( 'wp-links-page-free/wp-links-page-free.js', 'wp-links-page-free' ), null, null, true );
	wp_localize_script( 'wp-links-free-page', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_style( 'wp-links-free-style', plugins_url( 'wp-links-page-free/wp-links-page-free.css', 'wp-links-page-free' ), null, null, false );
}	


/* WP Links Setup Page in admin */

function wp_links_page_free_options() {
	global $wpdb;
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$links = $wpdb->get_results("SELECT * FROM $table ORDER BY weight ASC");
	$table_rows = '';
	$i = 1;
	foreach ($links as $link) {
		$table_rows .= '<tr id="'.$link->id.'" ><td id="'.$link->id.'" class="index">'.$i.'</td><td class="screenshot" ><img src="'.$link->img.'" /></td><td class="url" >'.$link->display.'</td><td class="description" >'.$link->description.'</td><td class="edit"><button id="'.$link->id.'" class="update button button-primary button-large" style="display: none;">Update</button><button id="'.$link->id.'" class="edit button button-primary button-large">Edit</button></td><td id="'.$link->url.'" class="delete" ><button id="'.$link->id.'" class="delete button button-primary button-large">Delete</button></td></tr>';
		$i++;
	}
	echo '<h1>Edit WP Links Page Free Setup</h1>
			<p class="message-limit" style="display:none;">You are limited to six links with this plugin. For more links, purchase the full version at <a href="http://www.wplinkspage.com/purchase/" target="_blank">WpLinksPage.com</a>.</p>
			<h2>Add a New Link</h2>
			<form id="add-link">
			<label for="url" >Link URL</label>
			<input id="url-input" type="text" name="url" maxlength="255" />
			<label for="description" >Description</label>
			<input id="description-input" type="text" name="description" maxlength="255" />
			<button id="saveimg" class="button button-primary button-large" type="submit" >Add Link</button>
			</form>
			<p class="message" style="display: none;">Changes to this page will not be saved until you press "Save".</p>
			<p class="update-message" style="display: none;">Updating screenshots may take a few minutes. Please be patient.</p>
			<button id="update-screenshots" class="button button-primary button-large">Update Screenshots</button><h2>Current Links</h2>
			<table id="sort" class="grid wp-list-table widefat striped links" cellspacing="0" >
			<thead>
			<tr><th class="index">No.</th><th>Screenshot</th><th>Link Display</th><th colspan="3">Description</th></tr>
			</thead>
			<tbody>'.$table_rows.'
			</tbody>
			</table>
			<button id="save-weight" class="button button-primary button-large">Save</button>
			<p class="message" style="display: none;">Changes to this page will not be saved until you press "Save".</p>
			<p class="update-message" style="display: none;">Updating screenshots may take a few minutes. Please be patient.</p>';
}

function wp_links_free_subpage_options() {
	//reschedule the event based on the new options
	if(isset($_GET['settings-updated']) && $_GET['settings-updated'])
   {
	$sr = get_option('wplpf_screenshot_refresh');
	$timestamp = time();
	if ($sr == 'twicedaily') {$rate = '+12 hours';}
	if ($sr == 'daily') {$rate = '+1 day';}
	if ($sr == 'twodays') {$rate = '+2 days';}
	if ($sr == 'threedays') {$rate = '+3 days';}
	if ($sr == 'weekly') {$rate = '+1 week';}
	if ($sr == 'biweekly') {$rate = '+2 weeks';}
	if ($sr == 'monthly') {$rate = '+1 month';}
	$next_event = strtotime($rate, $timestamp);
	$time = wp_next_scheduled( 'wp_links_page_free_event' );
	wp_clear_scheduled_hook( 'wp_links_page_free_event' );
	wp_schedule_event( $next_event, $sr, 'wp_links_page_free_event' );
   }
	
	//display the options page
	echo '<div class="wrap wplpf-settings">
	<h2>Edit WP Links Page Free Settings</h2>
	<form method="post" action="options.php">';
	settings_fields( 'wp-links-page-free-option-group' );
	do_settings_sections( 'wp-links-page-free-option-group' );
	$screenshot_refresh = esc_attr( get_option('wplpf_screenshot_refresh') );
	$grid = esc_attr( get_option('wplpf_grid') );
	$width = esc_attr( get_option('wplpf_width') );
    echo '<table class="form-table"><tbody>
		<tr>
		<th scope="row"><label class="label" for="wplpf_screenshot_refresh" >Screenshot Refresh Rate</label></th>
        <td>
		<label><input type="radio" name="wplpf_screenshot_refresh" value="twicedaily" ';
		echo ($screenshot_refresh=='twicedaily')?'checked':'';
		echo ' >Twice Daily</label><br/>
		<label><input type="radio" name="wplpf_screenshot_refresh" value="daily" ';
		echo ($screenshot_refresh=='daily')?'checked':'';
		echo ' >Daily</label><br/>
		<label><input type="radio" name="wplpf_screenshot_refresh" value="twodays" ';
		echo ($screenshot_refresh=='twodays')?'checked':'';
		echo ' >Every Two Days</label><br/>
		<label><input type="radio" name="wplpf_screenshot_refresh" value="threedays" ';
		echo ($screenshot_refresh=='threedays')?'checked':'';
		echo ' >Every Three Days</label><br/>
		<label><input type="radio" name="wplpf_screenshot_refresh" value="weekly" ';
		echo ($screenshot_refresh=='weekly')?'checked':'';
		echo ' >Weekly</label><br/>
		<label><input type="radio" name="wplpf_screenshot_refresh" value="biweekly" ';
		echo ($screenshot_refresh=='biweekly')?'checked':'';
		echo ' >Every Two Weeks</label><br/>
		<label><input type="radio" name="wplpf_screenshot_refresh" value="monthly" ';
		echo ($screenshot_refresh=='monthly')?'checked':'';
		echo ' >Monthly</label><br/>';
		if ($screenshot_refresh == 'twicedaily') {$screenshot_refresh = 'Twice Daily';}
		if ($screenshot_refresh == 'daily') {$screenshot_refresh = 'Daily';}
		if ($screenshot_refresh == 'twodays') {$screenshot_refresh = 'Two Days';}
		if ($screenshot_refresh == 'threedays') {$screenshot_refresh = 'Every Three Days';}
		if ($screenshot_refresh == 'weekly') {$screenshot_refresh = 'Weekly';}
		if ($screenshot_refresh == 'biweekly') {$screenshot_refresh = 'Every Two Weeks';}
		if ($screenshot_refresh == 'monthly') {$screenshot_refresh = 'Monthly';}
		echo '<p class="description">How often should WP Links Page get new screenshots for your links?<br/>The refresh rate is currently set to '.$screenshot_refresh.'.</p></td></tr>';
		
	
	echo '<tr><th scope="row"><label class="label" for="grid" >Use a grid or a list layout?</label></th>
		<td>
		<label><input type="radio" name="wplpf_grid" value="grid" ';
		echo ($grid=='grid')?'checked':'';
		echo ' >Grid</label><br/>
		<label><input type="radio" name="wplpf_grid" value="list" ';
		echo ($grid=='list')?'checked':'';
		echo ' >List</label><br/>
		<p class="description" >The Grid layout does not show the description. Use the List layout to display the link descriptions.</p>
		<p class="description" >The Layout is currently set to '.$grid.'.</p></td></tr>';
	if ($grid == true) {
    
	echo '<tr><th scope="row"><label class="label" for="wplpf_width" >Grid Width</label></th>
        <td>
		<label for="wplpf_width">Number of Columns:</label><br/>
		<label><input type="radio" name="wplpf_width" value="2" ';
		echo ($width=='2')?'checked':'';
		echo ' >2</label><br/>
		<label><input type="radio" name="wplpf_width" value="3" ';
		echo ($width=='3')?'checked':'';
		echo ' >3</label><br/>
		<label><input type="radio" name="wplpf_width" value="4" ';
		echo ($width=='4')?'checked':'';
		echo ' >4</label><br/>
		<label><input type="radio" name="wplpf_width" value="5" ';
		echo ($width=='5')?'checked':'';
		echo ' >5</label><br/>
		<p class="description" >How many columns should the grid have?<br />The number of columns is currently set to '.$width.'.</p></td></tr></tbody></table>';
	}
	submit_button();
	echo '</form>
    </div>';	
}

function wp_links_page_free_settings() { // whitelist options
  register_setting( 'wp-links-page-free-option-group', 'wplpf_screenshot_refresh' );
  register_setting( 'wp-links-page-free-option-group', 'wplpf_grid' );
  register_setting( 'wp-links-page-free-option-group', 'wplpf_width' );
}

function wplpf_getSSLPage($url) {
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSLVERSION,3); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function wp_links_free_help_page() {
	echo '<div class="wplpf-help"><h2>WP-Links-Page-Free Help Page</h2>
	<a href="http://www.wplinkspage.com/purchase/" target="_blank" class="wplpf-help-p">Click here to purchase the pro version.</a><br/><br/>
	<h2>Documentation</h2>
<div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Installation</strong></h3>
<h4>Uploading via WordPress Dashboard</h4>
<ol>
<li>Navigate to the &#8216;Add New&#8217; in the plugins dashboard</li>
<li>Navigate to the &#8216;Upload&#8217; area</li>
<li>Select wp-links-page.zip from your computer</li>
<li>Click &#8216;Install Now&#8217;</li>
<li>Activate the plugin in the Plugin dashboard</li>
</ol>
<h4>Using FTP</h4>
<ol>
<li>Download wp-links-page.zip</li>
<li>Extract the wp-links-page.zip directory to your computer</li>
<li>Upload the wp-links-page.zip directory to the <code>/wp-content/plugins/</code> directory</li>
<li>Activate the plugin in the Plugin dashboard</li>
</ol>
</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone size-full wp-image-126" src="'.plugins_url( "images/Install-Plugin.jpg", __FILE__ ).'" alt="Install Plugin" /></p>
</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div><div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Adding and Editing Links</strong></h3>
<p >Visit the WP Links Page section of the dashboard to add, reorder, and edit the links.</p>
<p >Add links by entering the link URL/description and hit the add link button.</p>
<p >You may edit the link or description with the edit button.</p>
<p >To reorder your links simply drag and drop them into place, then click ‘Save’ at the bottom of the screen.</p>
<p >When updating the links, press the edit button and make your changes. Click update to save your changes. Please do not try to update multiple links at the same time. The update button will only update its link, not any others.</p>
<p >Clicking the ‘Update Screenshots’ button on the this page can take several minutes depending on your connection. Please be patient while it retrieves new images. If for some reason it does not automatically refresh when completed, simply refresh the page to see the new images.</p>
<p style="font-weight: bold;">With this version you are limited to six links only. <a href="http://www.wplinkspage.com/" target="_blank">Purchase the pro version for unlimited links.</a></p>
</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone size-full wp-image-205" src="'.plugins_url( "images/add-edit-links.jpg", __FILE__ ).'" alt="add edit links" /></p>
</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div><div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Settings</strong></h3>
<p >Visit the Settings subpage in the WP Links Page section to set the timeframe to retrieve new screenshots and edit the settings for the links page view.</p>
<p >The Grid layout does not show the description. Use the List layout to display the link descriptions.</p>
<p >Options:</p>
<ul>
<li >Screenshot refresh rate: Twice Daily, Daily, Every two days, Weekly, Every two Weeks, Monthly.</li>
<li >Display: Grid or List.</li>
<li >Columns for Grid: 1, 2, 3, 4 or 5</li>
</ul>
</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone size-full wp-image-110" src="'.plugins_url( "images/settingspage.jpg", __FILE__ ).'" alt="settings page" /></p>
</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div><div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Widget</strong></h3>
<p>A new widget is also provided. This widget will display a one column grid of the links with descriptions only showing if the ‘List’ option is chosen.</p>
<p>If using a enhanced Text Widget you may enter the the Wp links Page shortcode to display links.</p>
<p style="text-align: left;">Options:</p>
<ul>
<li style="text-align: left;">Title</li>
<li style="text-align: left;">Number of Links to display</li>
<li style="text-align: left;">Display Descriptions toggle</li>
</ul>
<p style="font-weight: bold;">With this version the widget only gives the option to change the title. <a href="http://www.wplinkspage.com/" target="_blank">Purchase the pro version for the extra widget features.</a></p>
</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone size-full wp-image-133" src="'.plugins_url( "images/widgets.jpg", __FILE__ ).'" alt="widgets"  /></p>
</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div><div class="fusion-one-half fusion-layout-column fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><h3><strong>Shortcode</strong></h3>
<p >Use this shortcode to add your links to any page or post:</p>
<p><strong>&#91;wp_links_page_free]</strong></p>
<ul>
<li>Displays all links with the global settings.</li>
<p style="font-weight: bold;">With this version you are limited to the above shortcode only. <a href="http://www.wplinkspage.com/" target="_blank">Purchase the pro version take advantage of the fine tuned control available below.</a></p>
</ul>
<p><strong>&#91;wp_links_page num=&#8221;1&#8243;]</strong></p>
<ul>
<li>Displays only the first link.</li>
<li>Change the number to any other link number to display a different link.</li>
<li>This shortcode always fills 100% of the width regardless of list or grid options.</li>
<li><strong>Allowed Options: </strong>Any Number</li>
</ul>
<p><strong>&#91;wp_links_page type=&#8221;grid&#8221;]</strong></p>
<ul>
<li>Overrides the global settings to either grid or list.</li>
<li>This option is case sensitive. It will understand &#8220;grid&#8221; but not &#8220;Grid&#8221;.</li>
<li><strong>Allowed Options: </strong>grid or list</li>
</ul>
<div class="">
<div class="">
<p><strong>&#91;wp_links_page cols=&#8221;2&#8243;]</strong></p>
<ul>
<li>Overrides the current number of columns in the grid.</li>
<li>If the list setting is enabled this option will be ignored.</li>
<li><strong>Allowed Options:</strong> 2, 3, 4, or 5</li>
</ul>
<p><strong>&#91;wp_links_page description=&#8221;yes&#8221;]</strong></p>
</div>
<ul>
<li>Sets the option to display descriptions on the grid.</li>
<li>If the list setting is enabled descriptions are always displayed regardless of this option.</li>
<li><strong>Allowed Options:</strong> yes or no</li>
</ul>
</div>
<p><strong>&#91;wp_links_page limit=&#8221;3&#8243;]</strong></p>
<ul>
<li>Limits the amount of links shown to this number</li>
<li>e.g. if 3 is chosen only 3 links will display.</li>
<li><strong>Allowed Options: </strong>any number</li>
</ul>
</div></div><div class="fusion-one-half fusion-layout-column fusion-column-last fusion-spacing-yes" style="margin-top:0px;margin-bottom:20px;"><div class="fusion-column-wrapper"><p><img class="alignnone wp-image-184 size-full" src="'.plugins_url( "images/Shortcode-EX-e1433675525924.jpg", __FILE__ ).'" alt="Shortcode Example" /></p>
</div></div><div class="fusion-clearfix"></div><div class="fusion-sep-clear"></div><div class="fusion-separator fusion-full-width-sep sep-single" style="border-color:#e0dede;border-top-width:1px;margin-left: auto;margin-right: auto;margin-top:px;margin-bottom:30px;"></div>
	<a href="http://www.wplinkspage.com/purchase/" target="_blank" class="wplpf-help-p">Click here to purchase the pro version.</a></div>';
}

/* WP Links Page of links with shortcode */

function wp_links_page_free_links() {
	$grid = esc_attr( get_option('wplpf_grid') );
	$col = esc_attr( get_option('wplpf_width') );
	if ($grid == 'grid') {
		if ($col == 2) $width = 45;
		if ($col == 3) $width = 33;
		if ($col == 4) $width = 25;
		if ($col == 5) $width = 20;
	}else $width = 95;
	$content = '';
	$content .= '<style>.wplpf-link {margin-bottom: 10px;}
		.wplpf-link-grid { text-align: center;}
		.wplpf-link-grid .url {font-weight: bold;}
		.wplpf-link-grid img {max-width: 100%; display: inline !important;}
		.wplpf-list .wplpf-link-grid p {min-width: 50%;}
		.wplpf-list .wplpf-link-grid img {float: none !important; padding-right: 5%; width: 100%; margin-bottom: 10px;}
		.wplpf-link {float: left; display: block; padding: 0 1%;text-decoration: none !important; border: none !important;}
		.wplpf-list {width: 100%;}
		.wplpf-description {font-style: italic;}
		.wplpf-clear {clear: both;}
		@-ms-viewport {width: device-width;}
		@viewport {width: device-width;}
		@media screen and (min-width: 38.75em) {.wplpf-grid{width:100%; float: none;}.wplpf-list .wplpf-link-grid img {float: none !important; padding-right: 5%; width: 100%;} }
		@media screen and (min-width: 46.25em) {.wplpf-grid{width:50%; float: left;}.wplpf-list .wplpf-link-grid img {float: left !important; padding-right: 5%; width: 50%;}}
		@media screen and (min-width: 55em) {.wplpf-grid{width: '.$width.'%;}}</style>';
	global $wpdb;
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$links = $wpdb->get_results("SELECT * FROM $table ORDER BY weight ASC");
	$i = 0;
	foreach ($links as $link) {
		if ($col == $i) {
			$i = 1;
		} else { $i++;}
		if ($i == 1) {
			$clear = ' wplpf-clear';
		} else { $clear = '';}
		$input = $link->url;
		$input = trim($input, '/');
		if (!preg_match('#^http(s)?://#', $input)) {
			$input = 'http://' . $input;
		}
		$urlParts = parse_url($input);
		$domain = preg_replace('/^www\./', '', $urlParts['host']);
		$content .= '<a class="wplpf-link wplpf-'.$grid.$clear.'" href="'.$link->display.'" target="_blank"><div class="wplpf-link-grid"><img src="'.$link->img.'" ><p class="url">'.$domain.'</p>';
		if ($grid == 'list') {
		$content .= '<p class="wplpf-description">'.$link->description.'</p><hr style="clear:both;"></div></a>';
		} else { $content .= '</div></a>';}
	}
	return $content;
}
add_shortcode('wp_links_page_free', 'wp_links_page_free_links');

/* Page Code */

    function wp_links_page_free_page_creator() {
		$grid = esc_attr( get_option('wplpf_grid') );
		$col = esc_attr( get_option('wplpf_width') );
		$content = '[wp_links_page_free]';
		$post = array(
			  'post_content'   => $content,
			  'post_name'      => 'links',
			  'post_title'     => 'Links',
			  'post_status'    => 'publish',
			  'post_type'      => 'page',
			  'post_date'      => date('Y-m-d H:i:s'),
			  'post_date_gmt'  => gmdate('Y-m-d H:i:s'),
			  'comment_status' => 'closed',
			);  
			$id = wp_insert_post ($post);
			update_option( 'wplpf_page', $id );
	}
	
	//activation hook on line 66
	    

/* Widget Code */

/**
 * Adds Wp_Links_Widget widget.
 */
class WP_Links_Free_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wp_links_free_widget', // Base ID
			__( 'WP Links Page Free', 'content' ), // Name
			array( 'description' => __( 'WP Links Page Free Widget', 'content' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		} else echo $args['before_title'] . apply_filters( 'widget_title', 'Links' ). $args['after_title'];
		if ( ! empty( $instance['limit'] ) ) {
			$limit = $instance['limit'];
		} else $limit = 10;
	$content = '<style>.wplpf-link-grid-widget { text-align: center; }
		.wplpf-link-grid-widget .url {font-weight: bold;}
		.wplpf-link-grid-widget img {max-width: 100%; margin-right: 10px;}
		.wplpf-link-widget {float: left; display: block; margin: 10px;text-decoration: none !important;width: 95%; border: none !important;}
		.wplpf-widget .wplpf-link-grid img {float: left;}
		.wplpf-description-widget {font-style: italic;}</style>';
	global $wpdb;
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$links = $wpdb->get_results("SELECT * FROM $table ORDER BY weight ASC");
	$i = 0;
	foreach ($links as $link) {
		if ($i == $limit) {
			break;
		} else {$i++;}
		$input = $link->url;
		$input = trim($input, '/');
		if (!preg_match('#^http(s)?://#', $input)) {
			$input = 'http://' . $input;
		}
		$urlParts = parse_url($input);
		$domain = preg_replace('/^www\./', '', $urlParts['host']);
		$content .= '<a class="wplpf-link-widget wplpf-widget" href="'.$link->display.'" target="_blank"><div class="wplpf-link-grid-widget"><img src="'.$link->img.'" ><p class="url">'.$domain.'</p><p class="wplpf-description-widget">'.$link->description.'</p></div></a>';
		}
		echo __( $content, 'text_domain' );
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Links', 'text_domain' );
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
      <?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

} // class Widget

// register widget
function register_wp_links_free_widget() {
    register_widget( 'WP_Links_Free_Widget' );
}
add_action( 'widgets_init', 'register_wp_links_free_widget' );

// AJAX calls below

// AJAX to add a link

add_action( 'wp_ajax_wplpf_ajax', 'wplpf_ajax_callback' );

function wplpf_ajax_callback() {
	global $wpdb;
	
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$links = $wpdb->get_results("SELECT * FROM $table ORDER BY weight ASC");
	$count = count($links);
	if ($count >= 6) {
		die();
	}
	
	if (isset($_REQUEST['weight'])) {
		$weight = $_REQUEST['weight'];
		$weight = sanitize_text_field($weight);
	} else $weight = -50;
	if (isset($_REQUEST['description'])) {
		$description = $_REQUEST['description'];	
		$description  = sanitize_text_field($description);
	} else $description = '';
	if (isset($_REQUEST['data'])) {
		$data = $_REQUEST['data'];
	}else die(json_encode(array('message' => 'ERROR', 'code' => 1336)));
	
	if (isset($_REQUEST['url'])) {
		$url = $_REQUEST['url'];
		$url = esc_url_raw($url);
		// in case scheme relative URI is passed, e.g., //www.google.com/
		$input = trim($url, '/');
		
		// If scheme not included, prepend it
		if (!preg_match('#^http(s)?://#', $input)) {
			$input = 'http://' . $input;
		}
		
		$urlParts = parse_url($input);
		
		// remove www
		$domain = preg_replace('/^www\./', '', $urlParts['host']);
		$display = $domain;
		// remove punctuation
		$file = preg_replace("/[^a-zA-Z0-9]+/", "", $url);
	}else die(json_encode(array('message' => 'ERROR', 'code' => 1337)));
	
    $base64img = str_replace('data:image/jpeg;base64,', '', $data);
    $data = base64_decode($data);
    $img_url = WPLPF_UPLOAD_URL.$file.".jpg";
	
    $file = WPLPF_UPLOAD_DIR . $file . '.jpg';
    file_put_contents($file, $data);
	global $wpdb;
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$wpdb->insert(
			$table,
			array(
				'url' => $url,
				'display' => $display,
				'description' => $description,
				'img' => $img_url,
				'weight' => $weight
			));
	echo "success";
	wp_die();
}

// AJAX to delete a link

add_action( 'wp_ajax_wplpf_ajax_delete', 'wplpf_ajax_delete_callback' );

function wplpf_ajax_delete_callback() {
	
	if (isset($_REQUEST['id'])) {
		$id = $_REQUEST['id'];
		$id = sanitize_text_field($id);
	} else die(json_encode(array('message' => 'ERROR', 'code' => 1442)));
	
	if(isset($_REQUEST['url'])) {
		$url = $_REQUEST['url'];
		$url = esc_url_raw($url);
	} 
	
	// in case scheme relative URI is passed, e.g., //www.google.com/
	$input = trim($url, '/');
	
	// If scheme not included, prepend it
	if (!preg_match('#^http(s)?://#', $input)) {
		$input = 'http://' . $input;
	}
	
	$urlParts = parse_url($input);
	
	// remove www
	$domain = preg_replace('/^www\./', '', $urlParts['host']);
	// remove punctuation
	$file = preg_replace("/[^a-zA-Z0-9]+/", "", $url);
	
    $file = WPLPF_UPLOAD_DIR . $file . '.jpg';
	if(file_exists($file)) unlink($file);
	
	global $wpdb;
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$wpdb->delete( $table, array( 'ID' => $id ) );
			
	wp_die();
	
}

// AJAX to update a link

add_action( 'wp_ajax_wplpf_ajax_update', 'wplpf_ajax_update_callback' );

function wplpf_ajax_update_callback() {
	
	if (isset($_REQUEST['id'])) {
		$id = $_REQUEST['id'];	
		$id = sanitize_text_field($id);
	} else die(json_encode(array('message' => 'ERROR', 'code' => 1442)));
	if (isset($_REQUEST['url'])) {
		$url = $_REQUEST['url'];
		$url = sanitize_text_field($url);	
	} else die(json_encode(array('message' => 'ERROR', 'code' => 1443)));
	if (isset($_REQUEST['desc'])) {
		$desc = $_REQUEST['desc'];	
		$desc = sanitize_text_field($desc);
	} else $desc = "";
	
	
	global $wpdb;
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$wpdb->update( 
	$table, 
	array( 
		'display' => $url,	
		'description' => $desc
	), 
	array( 'ID' => $id ), 
	array( 
		'%s',	// value1
		'%s'	// value2
	), 
	array( '%d' ) 
);
		
	wp_die();
	
}

// AJAX to update the screenshots

add_action( 'wp_ajax_wplpf_ajax_update_screenshots', 'wplpf_ajax_update_screenshots_callback' );

function wplpf_ajax_update_screenshots_callback() {
	
	global $wpdb;
	$table = $wpdb->prefix.'wp_links_page_free_table';
	$links = $wpdb->get_results("SELECT * FROM $table ORDER BY weight");
	$table_rows = '';
	$i = 1;
	foreach ($links as $link) {
	$screenshot = wplpf_getSSLPage('https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url='.$link->url.'&screenshot=true');
	$data_whole = json_decode($screenshot);
	if (isset($data_whole->error)) {
		die();	
	}
	if (isset($data_whole->screenshot->data)) {
		$data = $data_whole->screenshot->data;
	} else { die();}
	
	if (isset($link->description)) {
		$description = $link->description;	
	} else $description = '';
	
	if (isset($link->url)) {
		$url = $link->url;
		// in case scheme relative URI is passed, e.g., //www.google.com/
		$input = trim($url, '/');
		
		// If scheme not included, prepend it
		if (!preg_match('#^http(s)?://#', $input)) {
			$input = 'http://' . $input;
		}
		
		$urlParts = parse_url($input);
		
		// remove www
		$domain = preg_replace('/^www\./', '', $urlParts['host']);
		// remove dot
		$name = preg_replace('/[.,]/', '', $domain);
		$file = preg_replace("/[^a-zA-Z0-9]+/", "", $url);
	}else {wp_die();}
	
    $file = WPLPF_UPLOAD_DIR . $file . '.jpg';
	if(file_exists($file)) unlink($file);
	$data = str_replace('_', '/', $data);
	$data = str_replace('-', '+', $data);
    $base64img = str_replace('data:image/jpeg;base64,', '', $data);
    $data = base64_decode($data);
    file_put_contents($file, $data);
	}
			
	wp_die();
}

// AJAX to save link weights

add_action( 'wp_ajax_wplpf_ajax_weight', 'wplpf_ajax_weight_callback' );

function wplpf_ajax_weight_callback() {
	$data = $_POST['links_update'];
	$data = array_map($data);
	global $wpdb;
	$new_data = '';
	foreach ($data as $link) {
	$id = $link['id'];
	$weight = $link['weight'];
	
	$table_name = $wpdb->prefix . 'wp_links_page_free_table';
	
	$wpdb->update( 
	$table_name, 
	array( 
		'weight' => $weight,	
	), 
	array( 'ID' => $id ), 
	array( 
		'%d',	// value1
	), 
	array( '%d' ) 
	);
	
	$new_data .= 'id: '.$id.', weight: '.$weight.', ';
	
	}
	
	echo $new_data;
		
	wp_die();
}

?>