<?php
/*
* Plugin Name: Manutenzione by Hardweb.it
* Plugin URI:  https://www.hardweb.it
* Description: Plugin di gestione manutenzione portali
* Version:     2.1.2
* Author:      Hardweb.it
* Author URI:  https://www.hardweb.it
* Copyright: © 2020 Hardweb IT
* License:      GPL2
* License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/
#SECURITY CHECK
if(!defined('ABSPATH')) exit;
#DEFINE
define( 'HW_MANUTENZIONE_PLUGIN_VERSION', '2.1.2' ); //VERSION
define( 'HW_MANUTENZIONE_PLUGIN_SLUG', 'hw-manutenzione' ); //SLUG
define( 'HW_MANUTENZIONE_REPO_URL', 'http://hardweb.it' );
define( 'HW_MANUTENZIONE_ITEM_ID', 2151 );
define( 'HW_MANUTENZIONE_PLUGIN_LICENSE_PAGE', 'hw-manutenzione-license' );
define( 'HW_MANUTENZIONE_ITEM_NAME', 'Manutenzione by Hardweb.it' );
define( 'HW_MANUTENZIONE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HW_MANUTENZIONE_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'HW_MANUTENZIONE_PLUGIN_FULLPATH', dirname( __FILE__ ).'/hw-manutenzione.php');
#INCLUDE
include_once(HW_MANUTENZIONE_PLUGIN_DIR.'/includes/hw-manutenzione-functions.php');
#UPDATER
include_once(HW_MANUTENZIONE_PLUGIN_DIR.'/includes/hw-manutenzione-plugin-updater.php');

function hw_manutenzione_license_menu() {
	add_plugins_page( 'Manutenzione by Hardweb.it', 'Manutenzione by Hardweb.it', 'manage_options', HW_MANUTENZIONE_PLUGIN_LICENSE_PAGE, 'hw_manutenzione_license_page' );
}
add_action('admin_menu', 'hw_manutenzione_license_menu');
function hw_manutenzione_license_page() {
	$license = get_option( 'hw_manutenzione_license_key' );
	$status  = get_option( 'hw_manutenzione_license_status' );
	if( $status !== false && $status == 'valid' ) {
		$license_message = "<span style='color:green;'>Attiva</span>";
	} else {
		$license_message = "<span style='color:black;'>Inserisci la tua chiave di licenza</span>";
	}
	?>
	<div class="wrap">
		<h2><?php _e('Licenza Manutenzione'); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields('hw_manutenzione_license'); ?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Chiave di Licenza'); ?>
						</th>
						<td>
							<input id="hw_manutenzione_license_key" name="hw_manutenzione_license_key" type="password" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="hw_manutenzione_license_key"><?php echo $license_message; ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Scadenza'); ?>
						</th>
						<td>
							<label class="description" for="hw_manutenzione_license_expire"><?php echo hw_manutenzione_get_license_message(); ?></label>
						</td>
					</tr>					
					<?php if( false !== $license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('Azioni'); ?>
							</th>
							<td>
								<?php wp_nonce_field( 'hw_manutenzione_nonce', 'hw_manutenzione_nonce' ); ?>
								<?php if( $status !== false && $status == 'valid' ) { ?>
									<input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Disattiva licenza'); ?>"/>
								<?php } else { ?>
									<input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e('Attiva licenza'); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php submit_button('Aggiorna la chiave di Licenza'); ?>
		</form>
	<?php hw__manutenzione_page(); ?>
	</div>
	<?php
}


add_filter('plugin_row_meta', function($plugin_meta, $pluginFile) {
    //Only modify our own plugin.
    if (plugin_basename(__FILE__) === $pluginFile) {

        //Check if the details link is already among the links (because there is an update)
        foreach ($plugin_meta as $existing_link) {
            if (strpos($existing_link, 'tab=plugin-information') !== false) {
                return $plugin_meta;
            }
        }

        //Get plugin info (need the name to mirror WP's own method)
        $plugin_info = get_plugin_data(__FILE__);
		
		$license_valid = hw_manutenzione_get_check_license();
		if (!$license_valid) {
			$message = '<tr class="plugin-update-tr" id="' . HW_MANUTENZIONE_PLUGIN_SLUG . '-update" data-slug="' . HW_MANUTENZIONE_PLUGIN_SLUG . '" data-plugin="' . HW_MANUTENZIONE_PLUGIN_SLUG . '/' . HW_MANUTENZIONE_PLUGIN_FULLPATH . '">';
			$message .= '<td colspan="3" class="plugin-update colspanchange">';
			$message .= '<div class="update-message notice inline notice-warning notice-alt">';
			$message .= 'LICENZA NON VALIDA';
			$message .= '</div></td></tr>';
			$plugin_meta[] = $message;		
		}
    }
    return $plugin_meta;
}, 10, 2);
?>
