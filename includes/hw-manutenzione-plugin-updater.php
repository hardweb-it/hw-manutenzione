<?php
#UPDATE CLASS
if( !class_exists( 'HW_Plugin_Class_Updater' ) ) {
	// load our custom updater
	include( HW_MANUTENZIONE_PLUGIN_DIR . '/includes/HW_Plugin_Class_Updater.php' );
}

add_action( 'admin_init', 'hw_manutenzione_updater', 0 );
function hw_manutenzione_updater() {

	// setup the updater
	$edd_updater = new HW_Plugin_Class_Updater( HW_MANUTENZIONE_REPO_URL, HW_MANUTENZIONE_PLUGIN_FULLPATH,
		array(
			'version' => HW_MANUTENZIONE_PLUGIN_VERSION,	// current version number
			'item_id' => HW_MANUTENZIONE_ITEM_ID,       	// ID of the product
			'author'  => 'Hardweb.it', 						// author of this plugin
			'beta'    => false,
		)
	);
}

function hw_manutenzione_get_siteurl() {
	// Recupera l'URL del sito WordPress
	$site_url = get_site_url();
	// Utilizza la funzione parse_url per analizzare l'URL
	$url_parts = parse_url($site_url);
	// Ottieni l'host (dominio) dalla parte analizzata dell'URL
	$host = $url_parts['host'];
	// Separa il dominio e l'estensione
	$domain_parts = explode(".", $host);	
	$full_domain = $domain_parts[count($domain_parts) - 2] . "." . $domain_parts[count($domain_parts) - 1];

return urlencode($full_domain);	
}

add_action('admin_init', 'hw_manutenzione_check_maintenance');
function hw_manutenzione_check_maintenance() {
	// Recupera l'URL del sito WordPress
	$website_url = hw_manutenzione_get_siteurl();

	// URL dell'API
	$api_url = 'https://clienti.hardweb.it/wp-json/clienti/v1/check-manutenzione';
	// Aggiungi il parametro GET 'website_url' all'URL
	$api_url_with_params = $api_url . '?website_url=' . $website_url;
	// opzioni della richiesta HTTP
	$headers = array(
		'Content-Type' => 'application/json',
		'X-API-KEY' => '8BYHU6DGXOZUE983NND671MF',
	);
	// argomenti della chiamata
	$args = array(
		'method' => 'GET', // Metodo HTTP (GET, POST, PUT, DELETE, ecc.)
		'timeout' => 45,   // Timeout della richiesta in secondi
		'headers' => $headers, // Aggiungi gli header personalizzati qui
	);


	// Effettua la chiamata API
	$response = wp_remote_request($api_url_with_params, $args);

	// Verifica se la chiamata è stata effettuata con successo
	if (is_wp_error($response)  || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		echo 'Errore nella chiamata API: ' . $response->get_error_message();
	} else {
		// Ottieni la risposta JSON (o altro formato) dalla chiamata API
		$body = wp_remote_retrieve_body($response);

		// Decodifica la risposta JSON in un array o un oggetto
		$data = json_decode($body);

		// Ora posso lavorare con i dati ottenuti dall'API
		$is_status_valid = $data->valid;
		$valid_until = $data->valid_until;
		$maintenance_type = $data->maintenance_type;
		$last_update = $data->last_update;

		/* update options */
		update_option( 'hw_manutenzione_status', $is_status_valid );
		update_option( 'hw_manutenzione_expiry', $valid_until );
		update_option( 'hw_manutenzione_type', $maintenance_type );
		update_option( 'hw_manutenzione_last_update', $last_update );

	}
}

/************************************
* this illustrates how to activate
* a license key
*************************************/
add_action('admin_init', 'hw_manutenzione_update_check');
function hw_manutenzione_update_check() {
	// listen for our activate button to be clicked
	if( isset( $_POST['hw_manutenzione_update'] ) ) {
		// run a quick security check
	 	if( ! check_admin_referer( 'hw_manutenzione_nonce', 'hw_manutenzione_nonce' ) ) { 
			return; // get out if we didn't click the Activate button
		}
		
		// update
		hw_manutenzione_check_maintenance();
	}
}
?>