<?php
/*
Plugin Name: Manutenzione Hardweb.it
Plugin URI:  https://www.hardweb.it
Description: Plugin di gestione manutenzione portali
Version:     2.0.2
Author:      Hardweb.it
Author URI:  https://www.hardweb.it/wp-plugins/
Copyright: © 2018 Hardweb IT
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/
#SECURITY CHECK
if(!defined('ABSPATH')) exit;

# ADMIN FOOTER CONTACTS
add_action('in_admin_footer', 'hw_admin_custom_footer');
function hw_admin_custom_footer() {
echo '
<div id="footer">
	<div id="footer-bottom">
		<p>Questo sito &egrave; gestito da Hardweb.it © 2017 - '. date('Y', time()).' | <a href="mailto:assistenza@hardweb.it">assistenza@hardweb.it</a> | <a href="https://hardweb.it" target="_blank">hardweb.it</a> | <a href="tel:3891671634" target="_blank">+39 389 1671634</a> | <a style="color:#1EBEA5" href="https://wa.me/393287759424" target="_blank">Whatsapp business</a></p>
	</div>
</div>';
}

# SUBMENU TOOLS PAGE
add_action( 'admin_menu', 'hw__manutenzione_init', 999);
function hw__manutenzione_init() {
	if (hw_is_hardweb_user()) {
	add_submenu_page( 'tools.php', 'HW Manutenzione', 'HW Manutenzione', 'edit_posts', 'hw-maintenance', 'hw__manutenzione_page', null);
	}
}

function hw__manutenzione_page() {
    // Get theme info
    $theme_data = wp_get_theme();
    $theme = $theme_data->Name . ' ' . $theme_data->Version;

    // Get plugins that have an update
    $updates = get_plugin_updates();
	
    // WordPress active plugins
    $plugins = get_plugins();
    $active_plugins = get_option('active_plugins', array());
	echo "<div style='margin-top: 50px;'></div>";
	echo "<div style='-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 60%;min-height:200px'>Eseguiti aggiornamenti plugin:<br>
	<ul>";
    foreach ($plugins as $plugin_path => $plugin) {
        if (!in_array($plugin_path, $active_plugins)) {
            continue;
        }
        if (array_key_exists($plugin_path, $updates)) {
			echo '<li>'.$plugin['Name'] . ' v' . $plugin['Version'] . ' -> v' . $updates[$plugin_path]->update->new_version .'</li>';
		}
    }
	echo "</ul></div>";
}

#FUNZIONE PER LA RIMOZIONE DI VOCI DAL MENU ADMIN LATERALE
add_action( 'admin_init', 'hw__manutenzione_remove_menu_items' );
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
  wp_enqueue_style('hw-maintenance-style', plugin_dir_url( __FILE__ ) .'/css/hwmaint.css', null, null, false);
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
