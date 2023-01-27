<?php
#UPDATE CLASS
if( !class_exists( 'HW_Plugin_Class_Updater' ) ) {
	// load our custom updater
	include( HW_MANUTENZIONE_PLUGIN_DIR . '/includes/HW_Plugin_Class_Updater.php' );
}

add_action( 'admin_init', 'hw_manutenzione_updater', 0 );
function hw_manutenzione_updater() {

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'hw_manutenzione_license_key' ) );

	// setup the updater
	$edd_updater = new HW_Plugin_Class_Updater( HW_MANUTENZIONE_REPO_URL, HW_MANUTENZIONE_PLUGIN_FULLPATH,
		array(
			'version' => HW_MANUTENZIONE_PLUGIN_VERSION,	// current version number
			'license' => $license_key,             			// license key (used get_option above to retrieve from DB)
			'item_id' => HW_MANUTENZIONE_ITEM_ID,       	// ID of the product
			'author'  => 'Hardweb.it', 						// author of this plugin
			'beta'    => false,
		)
	);
	
	/*
	if (WP_DEBUG_DISPLAY) {
		echo "<div style='margin-left:200px;'><h3>HW Manutenzione debug info:</h3><br>";
		print_r($edd_updater);
		echo "</div>";
	}
	*/

}
function hw_manutenzione_register_option() {
	// creates our settings in the options table
	register_setting('hw_manutenzione_license', 'hw_manutenzione_license_key', 'edd_sanitize_license' );
}
add_action('admin_init', 'hw_manutenzione_register_option');
function edd_sanitize_license( $new ) {
	$old = get_option( 'hw_manutenzione_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'hw_manutenzione_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}

function hw_manutenzione_get_license_message() {
	// retrieve the license from the database
	$license = trim( get_option( 'hw_manutenzione_license_key' ) );
	// data to send in our API request
	$api_params = array(
		'edd_action' => 'activate_license',
		'license'    => $license,
		'item_id' => HW_MANUTENZIONE_ITEM_ID,
		'item_name'  => urlencode( HW_MANUTENZIONE_ITEM_NAME ), // the name of our product in EDD
		'url'        => home_url()
	);
	// return message
	$message = "";
	// Call the custom API.
	$response = wp_remote_post( HW_MANUTENZIONE_REPO_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			//error
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			$expire_date = date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) );
			if ( false === $license_data->success ) {
				switch( $license_data->error ) {
					case 'expired' :
						$message = sprintf(	__( '<span style="color:#ff0000">SERVIZIO SCADUTO IL %s</span>' ), $expire_date);
						update_option( 'hw_manutenzione_license_expired', 'true' );
						update_option( 'hw_manutenzione_license_expired_on', $expire_date );
						break;
					case 'no_activations_left':
						$message = __( 'Questa licenza ha raggiunto il limite massimo di attivazioni.' );
						update_option( 'hw_manutenzione_license_expired', 'true' );
						update_option( 'hw_manutenzione_license_expired_on', $expire_date );
						break;						
					default :
						$message = __( 'SERVIZIO SCADUTO' );
						update_option( 'hw_manutenzione_license_expired', 'true' );
						update_option( 'hw_manutenzione_license_expired_on', $expire_date );
						break;
				}
			} elseif ($license_data->expires == 'lifetime') {
				
				$message = __( 'SERVIZIO ATTIVO SENZA SCADENZA' );
				update_option( 'hw_manutenzione_license_expired', 'false' );
				update_option( 'hw_manutenzione_license_valid_until', 'Nessuna scadenza prevista' );				
				
			} else {
				
				$message = sprintf( __( 'SERVIZIO ATTIVO FINO AL %s' ), $expire_date );
				update_option( 'hw_manutenzione_license_expired', 'false' );
				update_option( 'hw_manutenzione_license_valid_until', $expire_date );
			}
		}
		//print_r($license_data);
	return $message;
}

/************************************
* this illustrates how to activate
* a license key
*************************************/
function hw_manutenzione_activate_license() {
	// listen for our activate button to be clicked
	if( isset( $_POST['edd_license_activate'] ) ) {
		// run a quick security check
	 	if( ! check_admin_referer( 'hw_manutenzione_nonce', 'hw_manutenzione_nonce' ) )
			return; // get out if we didn't click the Activate button
		// retrieve the license from the database
		$license = trim( get_option( 'hw_manutenzione_license_key' ) );
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_id' => HW_MANUTENZIONE_ITEM_ID,
			'item_name'  => urlencode( HW_MANUTENZIONE_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);
		// Call the custom API.
		$response = wp_remote_post( HW_MANUTENZIONE_REPO_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'Si è verificato un errore. Riprova.' );
			}
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( false === $license_data->success ) {
				switch( $license_data->error ) {
					case 'expired' :
						$message = sprintf(
							__( 'La tua licenza è scaduta il %s.' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'disabled' :
					case 'revoked' :
						$message = __( 'La tua licenza è stata disabilitata.' );
						break;
					case 'missing' :
						$message = __( 'Licenza non valida.' );
						break;
					case 'invalid' :
					case 'site_inactive' :
						$message = __( 'La tua licenza non è abilitata per questo sito.' );
						break;
					case 'item_name_mismatch' :
						$message = sprintf( __( 'La licenza inserita non è valida per %s.' ), HW_MANUTENZIONE_ITEM_NAME );
						break;
					case 'no_activations_left':
						$message = __( 'Questa licenza ha raggiunto il limite massimo di attivazioni.' );
						break;
					default :
						$message = __( 'Si è verificato un errore. Riprova.' );
						break;
				}
			}
		}
		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			$base_url = admin_url( 'plugins.php?page=' . HW_MANUTENZIONE_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );
			wp_redirect( $redirect );
			exit();
		}
		// $license_data->license will be either "valid" or "invalid"
		update_option( 'hw_manutenzione_license_status', $license_data->license );
		wp_redirect( admin_url( 'plugins.php?page=' . HW_MANUTENZIONE_PLUGIN_LICENSE_PAGE ) );
		exit();
	}
}
add_action('admin_init', 'hw_manutenzione_activate_license');
/***********************************************
* Illustrates how to deactivate a license key.
* This will decrease the site count
***********************************************/
function hw_manutenzione_deactivate_license() {
	// listen for our activate button to be clicked
	if( isset( $_POST['edd_license_deactivate'] ) ) {
		// run a quick security check
	 	if( ! check_admin_referer( 'hw_manutenzione_nonce', 'hw_manutenzione_nonce' ) )
			return; // get out if we didn't click the Activate button
		// retrieve the license from the database
		$license = trim( get_option( 'hw_manutenzione_license_key' ) );
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_id' => HW_MANUTENZIONE_ITEM_ID,
			'item_name'  => urlencode( HW_MANUTENZIONE_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);
		// Call the custom API.
		$response = wp_remote_post( HW_MANUTENZIONE_REPO_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'Si è verificato un errore. Riprova.' );
			}
			$base_url = admin_url( 'plugins.php?page=' . HW_MANUTENZIONE_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );
			wp_redirect( $redirect );
			exit();
		}
		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' ) {
			delete_option( 'hw_manutenzione_license_status' );
		}
		wp_redirect( admin_url( 'plugins.php?page=' . HW_MANUTENZIONE_PLUGIN_LICENSE_PAGE ) );
		exit();
	}
}
add_action('admin_init', 'hw_manutenzione_deactivate_license');
/************************************
* this illustrates how to check if
* a license key is still valid
* the updater does this for you,
* so this is only needed if you
* want to do something custom
*************************************/
function hw_manutenzione_check_license() {
	global $wp_version;
	$license = trim( get_option( 'hw_manutenzione_license_key' ) );
	$api_params = array(
		'edd_action' => 'check_license',
		'license' => $license,
		'item_id' => HW_MANUTENZIONE_ITEM_ID,
		'item_name' => urlencode( HW_MANUTENZIONE_ITEM_NAME ),
		'url'       => home_url()
	);
	// Call the custom API.
	$response = wp_remote_post( HW_MANUTENZIONE_REPO_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
	if ( is_wp_error( $response ) )
		return false;
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );
	if( $license_data->license == 'valid' ) {
		echo 'Valida'; exit;
		// this license is still valid
	} else {
		echo 'Non valida'; exit;
		// this license is no longer valid
	}
}

function hw_manutenzione_get_check_license() {
	global $wp_version;
	$license = trim( get_option( 'hw_manutenzione_license_key' ) );
	$api_params = array(
		'edd_action' => 'check_license',
		'license' => $license,
		'item_id' => HW_MANUTENZIONE_ITEM_ID,
		'item_name' => urlencode( HW_MANUTENZIONE_ITEM_NAME ),
		'url'       => home_url()
	);
	// Call the custom API.
	$response = wp_remote_post( HW_MANUTENZIONE_REPO_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
	if ( is_wp_error( $response ) )
		return false;
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );
	if( $license_data->license == 'valid' ) {
		return true;
		// this license is still valid
	} else {
		return false;
		// this license is no longer valid
	}
}

/**
 * This is a means of catching errors from the activation method above and displaying it to the customer
 */
function hw_manutenzione_admin_notices() {
	if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {
		switch( $_GET['sl_activation'] ) {
			case 'false':
				$message = urldecode( $_GET['message'] );
				?>
				<div class="error">
					<p><?php echo $message; ?></p>
				</div>
				<?php
				break;
			case 'true':
			default:
				// Developers can put a custom success message here for when activation is successful if they way.
				break;
		}
	}
}
add_action( 'admin_notices', 'hw_manutenzione_admin_notices' );
?>