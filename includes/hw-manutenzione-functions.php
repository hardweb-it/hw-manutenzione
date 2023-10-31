<?php
/* hide plugin from admin list */
add_action('pre_current_active_plugins', 'hw_manutenzione_hide_plugins');
function hw_manutenzione_hide_plugins() {

	if (!hw_is_hardweb_user()) {
		# hide plugins from main list
		global $wp_list_table;
		$hidearr = array('hw-manutenzione/hw-manutenzione.php');
		$myplugins = $wp_list_table->items;
		foreach ($myplugins as $key => $val) {
			if (in_array($key,$hidearr)) {
			  unset($wp_list_table->items[$key]);
			}
		}
	}
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