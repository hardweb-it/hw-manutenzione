<?php
#Check license status
$status  = get_option( 'hw_manutenzione_license_status' );
$licstat = ($status == 'valid') ? true : false;
define('HW_MANUTENZIONE_LICENSE_STATUS', $licstat);
# ADMIN FOOTER CONTACTS
add_action('in_admin_footer', 'hw_admin_custom_footer');
function hw_admin_custom_footer() {
$expire_check_message = ' | ' . hw_manutenzione_get_license_message();
echo '
<div id="footer">
	<div id="footer-bottom">
		<p>Questo sito &egrave; gestito da Hardweb.it © 2017 - '. date('Y', time()).' | <a href="mailto:assistenza@hardweb.it">assistenza@hardweb.it</a> | <a href="https://hardweb.it" target="_blank">hardweb.it</a> | <a href="tel:3891671634" target="_blank">+39 389 1671634</a> | <a style="color:#1EBEA5" href="https://wa.me/393287759424" target="_blank">Whatsapp business</a>'.$expire_check_message.'</p>
	</div>
</div>';
}

#CALLED FROM MAIN PLUGIN
function hw__manutenzione_page() {
#HIDE ITEMS TO non-hardweb users
if (hw_is_hardweb_user()) {	

	//$core = get_core_updates();
	$themes = get_theme_updates();
    // Get plugins that have an update
    $updates = get_plugin_updates();
	
    // WordPress active plugins
    $plugins = get_plugins();
    $active_plugins = get_option('active_plugins', array());
	$active_theme = get_option('active_theme', array());
	echo "<div style='margin-top: 50px;'><h3>Plugin da aggiornare</h3></div>";
	echo "<div style='-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 60%;'><span style='font-weight: bold;'>Eseguiti aggiornamenti plugin:</span><br>
	<ul>";
	//print_r($updates);
    foreach ($plugins as $plugin_path => $plugin) {
        if (!in_array($plugin_path, $active_plugins)) {
            continue;
        }
        if (array_key_exists($plugin_path, $updates)) {
			echo '<li>'.$plugin['Name'] . ' v' . $plugin['Version'] . ' > v' . $updates[$plugin_path]->update->new_version .'</li>';
		}
    }
	echo "</ul></div>";
	echo "<div style='-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 60%;'><span style='font-weight: bold;'>Eseguiti aggiornamenti core grafico:</span><br>
	<ul>";
	
	// Get theme info
	//print_r($themes);
	foreach($themes as $theme_slug => $theme_update_object) {
		$current_theme = wp_get_theme($theme_slug);
		echo "<li>". $current_theme->get('Name') . " v" . $current_theme->get('Version');		
		$theme_object = get_object_vars($theme_update_object);
		echo " > v" . $theme_object['update']['new_version'] . "</li>";
	}
	
	echo "</ul></div>";	
}
}

#FUNZIONE PER LA RIMOZIONE DI VOCI DAL MENU ADMIN LATERALE
add_action( 'admin_menu', 'hw__manutenzione_remove_menu_items' );
function hw__manutenzione_remove_menu_items() {
	
	#HIDE ITEMS TO non-hardweb users
	if (hw_is_hardweb_user()) {

		//menu pages
		$hide_these_pages = array('the7-dashboard','settings_page','vc-general','pb_backupbuddy_backup','Wordfence','about-ultimate');
		foreach($hide_these_pages as $id=>$slug) {
			remove_menu_page($slug);
		}

		//submenu pages
		$hide_these_submenu_pages = array('tools.php'=>'aiosp_import');
		foreach($hide_these_submenu_pages as $main_page=>$sub_slug) {
			remove_submenu_page($main_page, $sub_slug);
		}
		
	}
}

add_action('pre_current_active_plugins', 'hw_manutenzione_hide_plugins');
function hw_manutenzione_hide_plugins() {
	# hide plugins from main list
	global $wp_list_table;
	$hidearr = array('hw-manutenzione/index.php');
	$myplugins = $wp_list_table->items;
	foreach ($myplugins as $key => $val) {
		if (in_array($key,$hidearr)) {
		  unset($wp_list_table->items[$key]);
		}
	}	
}
//Load some admin styles
add_action('admin_enqueue_scripts', 'hw_manutenzione_admin_style');
function hw_manutenzione_admin_style() {
  wp_enqueue_style('hw-maintenance-style', HW_MANUTENZIONE_PLUGIN_URL .'/css/hwmaint.css', null, null, 'all');
}

# CHECK IF CURRENT USER IS 'hardweb'
function hw_is_hardweb_user() {
	$current_user = wp_get_current_user();
	if ($current_user->user_login == "hardweb") {
		return true;
	} else {
		return false;
	}
}

/**
 * Debug Pending Updates
 *
 * Crude debugging method that will spit out all pending plugin
 * and theme updates for admin level users when ?debug_updates is
 * added to a /wp-admin/ URL.
 */
function hw_debug_pending_updates() {

    // Rough safety nets
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) return;
    if ( ! isset( $_GET['debug_updates'] ) ) return;

    $output = "";

    // Check plugins
    $plugin_updates = get_site_transient( 'update_plugins' );
    if ( $plugin_updates && ! empty( $plugin_updates->response ) ) {
        foreach ( $plugin_updates->response as $plugin => $details ) {
            $output .= "<p><strong>Plugin</strong> <u>$plugin</u> is reporting an available update.</p>";
        }
    }

    // Check themes
    wp_update_themes();
    $theme_updates = get_site_transient( 'update_themes' );
    if ( $theme_updates && ! empty( $theme_updates->response ) ) {
        foreach ( $theme_updates->response as $theme => $details ) {
            $output .= "<p><strong>Theme</strong> <u>$theme</u> is reporting an available update.</p>";
        }
    }

    if ( empty( $output ) ) $output = "No pending updates found in the database.";
	echo $output;
    wp_die( $output );
}
?>