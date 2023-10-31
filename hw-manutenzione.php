<?php
/*
* Plugin Name: Manutenzione by Hardweb.it
* Plugin URI:  https://www.hardweb.it
* Description: Plugin di gestione manutenzione portali
* Version:     2.1.8
* Author:      Hardweb.it
* Author URI:  https://www.hardweb.it
* Copyright: © 2020 Hardweb IT
* License:      GPL2
* License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/
#SECURITY CHECK
if(!defined('ABSPATH')) exit;
#DEFINE
define( 'HW_MANUTENZIONE_PLUGIN_VERSION', '2.1.8' ); //VERSION
define( 'HW_MANUTENZIONE_PLUGIN_SLUG', 'hw-manutenzione' ); //SLUG
define( 'HW_MANUTENZIONE_REPO_URL', 'http://clienti.hardweb.it' );
define( 'HW_MANUTENZIONE_ITEM_ID', 33865 );
define( 'HW_MANUTENZIONE_PLUGIN_PAGE', 'hw-manutenzione-check' );
define( 'HW_MANUTENZIONE_ITEM_NAME', 'Manutenzione by Hardweb.it' );
define( 'HW_MANUTENZIONE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HW_MANUTENZIONE_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'HW_MANUTENZIONE_PLUGIN_FULLPATH', dirname( __FILE__ ).'/hw-manutenzione.php');
#INCLUDE
include_once(HW_MANUTENZIONE_PLUGIN_DIR.'/includes/hw-manutenzione-functions.php');
#UPDATER
include_once(HW_MANUTENZIONE_PLUGIN_DIR.'/includes/hw-manutenzione-plugin-updater.php');


//Load admin styles
add_action('admin_enqueue_scripts', 'hw_manutenzione_admin_style');
function hw_manutenzione_admin_style() {
  wp_enqueue_style('hw-maintenance-style', HW_MANUTENZIONE_PLUGIN_URL .'/css/hwmaint.css', null, null, 'all');
}

/* initialize options at plugin activation */
register_activation_hook(__FILE__, 'hw_manutenzione_register_options');
function hw_manutenzione_register_options() {

	//delete old options
	delete_option('hw_manutenzione_license');
	delete_option('hw_manutenzione_license_expired');
	delete_option('hw_manutenzione_license_expired_on');
	delete_option('hw_manutenzione_license_key');
	delete_option('hw_manutenzione_license_status');

	// creates our settings in the options table
	register_setting('hw_manutenzione', 'hw_manutenzione_status' );
	register_setting('hw_manutenzione', 'hw_manutenzione_type' );
	register_setting('hw_manutenzione', 'hw_manutenzione_expiry' );
	register_setting('hw_manutenzione', 'hw_manutenzione_last_update' );

}

/* Admin menu item */
add_action('admin_menu', 'hw_manutenzione_license_menu');
function hw_manutenzione_license_menu() {
	$icon = HW_MANUTENZIONE_PLUGIN_URL . "/img/icon.png";
	add_menu_page( 'Manutenzione by Hardweb.it', 'Manutenzione', 'read', HW_MANUTENZIONE_PLUGIN_PAGE, 'hw_manutenzione_check_page', $icon, 2);
}

/* Plugin Page */
function hw_manutenzione_check_page() {
	$maintenance_type = get_option( 'hw_manutenzione_type' );
	$status  = get_option( 'hw_manutenzione_status' );
	$valid_until = date_i18n('d F Y', get_option( 'hw_manutenzione_expiry'));
	$stato_del_servizio = ($status == 1) ? '<span style="color:green;">ATTIVO</span>' : '<span style="color:red">SOSPESO</span>';
	$last_update = date_i18n('d F Y', get_option( 'hw_manutenzione_last_update'));
	// Recupera l'URL del sito WordPress
	$site_url = hw_manutenzione_get_siteurl();
	?>
	<div class="wrap">
	<style>
	.table { 
		border-radius: 15px;
		background-color: #fff;
		padding: 20px;
	}
	.table th {
		padding: 8px 0;
		text-align: right;
	}

	.table td {
		padding: 6px 0 5px 15px;
	}

	</style>
		<h2><?php _e('Manutenzione gestita da Hardweb.it'); ?></h2>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . "?page=".HW_MANUTENZIONE_PLUGIN_PAGE; ?>">
		<h2 style="padding: 10px 0;">Dettagli</h2>
			<table class="table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Dominio'); ?>:
						</th>
						<td>
							<label class="description" for="hw_manutenzione_domain"><?php echo $site_url; ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Tipo di Manutenzione'); ?>:
						</th>
						<td>
							<label class="description" for="hw_manutenzione_type"><?php echo $maintenance_type; ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Stato del servizio'); ?>:
						</th>
						<td>
							<label class="description" for="hw_manutenzione_is_suspended"><?php echo $stato_del_servizio; ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Scadenza'); ?>:
						</th>
						<td>
							<label class="description" for="hw_manutenzione_valid_until"><?php echo $valid_until; ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Ultimo aggiornamento del sito'); ?>:
						</th>
						<td>
							<label class="description" for="hw_manutenzione_last_update"><?php echo $last_update; ?></label>
						</td>
					</tr>
					<?php if( $status == false ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('Azioni'); ?>
							</th>
							<td>
								<?php wp_nonce_field( 'hw_manutenzione_nonce', 'hw_manutenzione_nonce' ); ?>
								<input type="submit" class="button-secondary" name="hw_manutenzione_update" value="<?php _e('Aggiorna status'); ?>"/>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</form>
	</div>
	<?php
}

/* Register dashboard widget */
add_action('wp_dashboard_setup', 'hw__manutenzione_add_dashboard_widget');
function hw__manutenzione_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'hw__manutenzione_add_dashboard_widget',
        'Stato Manutenzione del sito',
        'hw__manutenzione_render_dashboard_widget'
    );
}

/* Dashboard Widget content */
function hw__manutenzione_render_dashboard_widget() {

	$status  = get_option( 'hw_manutenzione_status' );
	$valid_until = date_i18n('d F Y', get_option( 'hw_manutenzione_expiry'));
	$stato_del_servizio = ($status == 1) ? '<span style="color:green;">ATTIVO</span>' : '<span style="color:red">SOSPESO</span>';
    ?>
	<style>
	.hw-manutenzione-dashboard-icon-size {
		width: 80px;
		height: 80px;
		font-size: 80px;
	}
	#dashboard_site_health { display: none!important; }
	</style>
    <div class="dashboard-widget-wrap" style="display: grid;grid-template-columns: 1fr 2fr;grid-auto-rows: minmax(64px,auto);column-gap: 16px;align-items: center;">
        <div class="dashboard-widget-left" style="margin-bottom: 0;text-align: center;">
			<?php if ($status == false) { ?>
			<i class="dashicons dashicons-warning hw-manutenzione-dashboard-icon-size" style="color:red;"></i>
			<?php } else { ?>
			<i class="dashicons dashicons-plugins-checked hw-manutenzione-dashboard-icon-size" style="color:#1abc9c;"></i>
			<?php } ?>
        </div>
        <div class="dashboard-widget-right">
			<?php if ($status == 0) {
			echo "<p>Il piano di manutenzione per questo sito risulta <b>scaduto</b> dal $expired_on!<br><br>Contatta <a href=\"https://hardweb.it/\" target=\"_blank\">Hardweb.it</a> per maggiori informazioni.</p>";
			} else {
			echo "<p><b>Complimenti!</b> Il piano di manutenzione per questo sito risulta attivo e regolare. Contatta <a href=\"https://hardweb.it/\" target=\"_blank\">Hardweb.it</a> se hai bisogno di assistenza tecnica!<br><br>La scadenza del piano avverr&agrave; il $valid_until</p>";
			} ?>
        </div>
    </div>
    <?php
}


# ADMIN FOOTER CONTACTS
add_action('in_admin_footer', 'hw_admin_custom_footer');
function hw_admin_custom_footer() {
$expire_check_message = ' | ' . hw_manutenzione_get_message();
echo '
<div id="footer" style="position: fixed;bottom: 0;width: 100%;margin-left: -20px;">
	<div id="footer-bottom" style="background-color:#fff;border-radius: 3px;">
		<p style="padding: 15px 0px 15px 15px;">Questo sito &egrave; gestito da Hardweb.it © 2017 - '. date('Y', time()).' | <a href="mailto:assistenza@hardweb.it">assistenza@hardweb.it</a> | <a href="https://hardweb.it" target="_blank">hardweb.it</a> | <a href="tel:3891671634" target="_blank">+39 389 1671634</a> | <a style="color:#1EBEA5" href="https://wa.me/393287759424" target="_blank">Whatsapp business</a>'.$expire_check_message.' <a href="/wp-admin/plugins.php?page=hw-manutenzione-check" style="color:#fff;background-color:#444;padding:3px 10px;border-radius:5px;text-decoration:none;display:inline-block;width:fit-content;margin-left:10px;">verifica</a></p>
	</div>
</div>';
}

#ADD NOTICE IF EXPIRED
add_action( 'admin_notices', 'hw__manutenzione_expired_notice');
function hw__manutenzione_expired_notice() {
	$expired = (get_option('hw_manutenzione_status') == 'suspended') ? true : false;
	$expired_on = date_i18n('d F Y', get_option('hw_manutenzione_expiry'));
	if ($expired) {
		print( '<div class="notice notice-warning is-dismissible"><p><b>ATTENZIONE:</b> Il piano di manutenzione per questo sito risulta <b>scaduto</b> dal '.$expired_on.'! Contatta <a href="https://hardweb.it/" target="_blank">Hardweb.it</a> per maggiori informazioni.</p></div>');
	}
}

# Prepare message
function hw_manutenzione_get_message() {
$is_status_valid = get_option( 'hw_manutenzione_status');
$valid_until = date_i18n('d F Y', get_option( 'hw_manutenzione_expiry'));
$maintenance_type = get_option( 'hw_manutenzione_type');

	if ( $is_status_valid == true ) {

		$message = sprintf(	__( '<span style="color:#000">Servizio ATTIVO fino al %s</span>' ), $valid_until);

	} else {

		$message = sprintf(	__( '<span style="color:#ff0000">Servizio <span style="color:red;">SCADUTO</span> il %s</span>' ), $valid_until);
	}

return $message;
}

?>