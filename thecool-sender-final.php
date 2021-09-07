<?php

add_action('upload_orders_2_tdv', 'resend_order_2_tdv');
function resend_order_2_tdv() {
	# Resend  all orders
	error_log("Entering resend_orders_2_tdv");
	global $wpdb;
	$tablename = $wpdb->prefix . "posts";
	$previous_day = date('Y-m-d',strtotime("-5 days"));
	error_log("PreviousDay: $previous_day");

	$all_orders = wc_get_orders(
		array(
			'date_created' => '>=' . $previous_day,
			'date_created' => '<' . ( time() - HOUR_IN_SECONDS ),
			'status' => array('wc-processing', 'wc-completed')
		)
	);

	$orders_count = sizeof($all_orders);
	error_log("Browsing $orders_count orders.");
	foreach( $all_orders as $order ){
		if ( $order->get_meta( '_wc_acof_12' ) !=  "Si" ){
			$order_id = $order->get_id();
			error_log("Trying to resend OrderID $order_id.");
			create_TDV_order($order_id);
		}
	}
}

/**
 *	Kalium WordPress Theme
 *
 *	Laborator.co
 *	www.laborator.co
 */

/**
 * @snippet       Close Ship to Different Address @ Checkout Page
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @testedwith    WooCommerce 3.9
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

// After theme setup hooks
function kalium_child_after_setup_theme() {
	// Load translations for child theme
	load_child_theme_textdomain( 'kalium-child', get_stylesheet_directory() . '/languages' );
}

add_action( 'after_setup_theme', 'kalium_child_after_setup_theme' );

// This will enqueue style.css of child theme
function kalium_child_wp_enqueue_scripts() {
	wp_enqueue_style( 'kalium-child', get_stylesheet_directory_uri() . '/style.css' );
    wp_enqueue_script('kalium-child',  get_stylesheet_directory_uri() . '/scriptsv1.js', array( 'jquery' ), false, true);
}

add_action( 'wp_enqueue_scripts', 'kalium_child_wp_enqueue_scripts', 100 );

if( is_admin() ){
	add_action( 'wp_default_scripts', 'wp_default_custom_scripts' );
	function wp_default_custom_scripts( $scripts ){
		$scripts->add( 'wp-color-picker', "/wp-admin/js/color-picker.js", array( 'iris' ), false, 1 );
		did_action( 'init' ) && $scripts->localize(
			'wp-color-picker',
			'wpColorPickerL10n',
			array(
				'clear'            => __( 'Clear' ),
				'clearAriaLabel'   => __( 'Clear color' ),
				'defaultString'    => __( 'Default' ),
				'defaultAriaLabel' => __( 'Select default color' ),
				'pick'             => __( 'Select Color' ),
				'defaultLabel'     => __( 'Color value' ),
			)
		);
	}
}

function sv_wc_api_allow_acof_protected_meta() {
	add_filter( 'is_protected_meta', 'sv_wc_acof_is_protected_meta', 10, 2 );
}

add_action( 'woocommerce_api_loaded', 'sv_wc_api_allow_acof_protected_meta' );
function sv_wc_acof_is_protected_meta( $protected, $meta_key ) {
	if ( 0 === strpos( $meta_key, '_wc_acof_' ) ) {
		$protected = false;
	}
	return $protected;
}

add_shortcode("quitar_filtros","quitar_filtros_button");

function quitar_filtros_button(){
	$button_str = '';
	if (isset($_GET["filter_talla"]) || is_product_category()){
		$shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
		$button_str = "<a id=\"quitar_filtros_btn\" href=\"$shop_page_url\">Quitar Filtros</a>";
	}
	return $button_str;
}


add_filter( 'woocommerce_catalog_orderby', 'cool_remove_default_sorting_options' );

function cool_remove_default_sorting_options( $options ){
	unset( $options[ 'menu_order' ] );
	unset( $options[ 'rating' ] );
	unset( $options[ 'date' ] );
		$options[ 'popularity' ] = 'Relevancia'; // rename
	$options[ 'price' ] = 'Menor a Mayor'; // rename
	$options[ 'price-desc' ] = 'Mayor a menor'; // rename

	//unset( $options[ 'price' ] );
	//unset( $options[ 'price-desc' ] );

	return $options;
}

add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'cool_filter_dropdown_args', 10 );

function cool_filter_dropdown_args( $args ) {
    $args['show_option_none'] = 'Elegir talla';
    return $args;
}

add_action( 'wp_ajax_nopriv_show_daily_orders','show_daily_orders' );
add_action( 'wp_ajax_show_daily_orders','show_daily_orders' );
function show_daily_orders()
{
	$order_ids = array(2093,2094,2095,2096);
	foreach ($order_ids as $order_id) {

		$order = wc_get_order( $order_id);

		do_action( 'woocommerce_before_resend_order_emails', $order, 'new_order' );

		WC()->payment_gateways();
		WC()->shipping();
		WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order,true );
		echo "<br/><br/><hr/><br/><br/>";

	}
	die();
}

add_filter( 'woocommerce_default_address_fields', 'make_postcode_optional' );
function make_postcode_optional( $address_fields ) {
    $address_fields['postcode']['required'] = false;

    return $address_fields;
}

add_filter( 'default_checkout_billing_state', 'change_default_checkout_state' );
add_filter( 'default_checkout_shipping_state', 'change_default_checkout_state' );
function change_default_checkout_state() {
    return ''; //set state code if you want to set it otherwise leave it blank.
}

add_action('woocommerce_after_checkout_form', 'custom_cool_js');

function custom_cool_js() {
?>
<script type="text/javascript" id="sns_global_scripts_in_head">
	jQuery(document).ready(function(){

		jQuery("input[name=factura]").click(function(){
			if(jQuery(this).val() == "Factura"){
				jQuery("#billing_id").prop("placeholder","RUC");
				jQuery("#billing_id").val("");
				jQuery("#billing_company_field").show();
				jQuery("#billing_company").val("");
				jQuery("#billing_document_type_field").hide();
				jQuery("input[name=factura]").val = "Factura";
			} else {
				jQuery("#billing_document_type_field").show();
				jQuery("#billing_id").prop("placeholder","DNI");
				jQuery("#billing_id").val("");
				jQuery("#billing_company_field").hide();
				jQuery("input[name=factura]").val = "Boleta";
			}
		});
		if(jQuery("input[name=factura]").val() == "Boleta"){
			jQuery("input[value=Boleta]").trigger("click");
		}
		jQuery("input[value=DNI]").trigger("click");
	});
</script>
<?php
}

add_filter('woocommerce_json_search_found_products','removeemptydesc',10,1);
	function removeemptydesc($products){
		if (!empty($products))
			foreach ($products as $product_id => $product_title) {
				$products[$product_id] = str_replace('<span class="description"></span>', '', $product_title);
			}
		return $products;
	}

add_action("woocommerce_order_details_after_customer_details","show_tracking_if_exist",100);
function show_tracking_if_exist($order){
	$shipping_tracking = get_field("track_de_envio",$order->get_id());
	if ($shipping_tracking !== '' && $shipping_tracking !== null)
		echo "<a href='".$shipping_tracking."'>Seguimiento de envío</a></p>";

	echo '<div id="libro_link""><p>Si tuviste algún problema con tu orden <a href="https://thecool.pe/libro-de-reclamaciones/">haz click acá</a></p></div>';
}

add_action( 'wp_footer', 'technik_checkout_js' );
function technik_checkout_js(){

	// we need it only on our checkout page
	if( !is_checkout() ) return;

	?>
    <style>
        .invalid_field_at_checkout {
            border-color: red !important;
        }
        .woocommerce-input-wrapper .select2.select2-container:first-of-type {
            display: none;
        }
    </style>
	<script>
		jQuery(function($){
			function onChangeBillingDocument(elementObject){
				var billing_document_type = document.getElementsByName('billing_document_type');
				var selected_billing_document_type;
				for (var i = 0, length = billing_document_type.length; i < length; i++) {
					if (billing_document_type[i].checked) {
						selected_billing_document_type = billing_document_type[i].value;
						break;
					}
				}
				var error = false;
				var error_message = "";
				if (selected_billing_document_type == 'DNI' ) {
					if (jQuery("#billing_id").val().length != 8 ){
						error = true;
						error_message = "DNI debe contener 8 dígitos.";
						alert(error_message);
					} else {
						var api_url = "https://apiperu.dev/api/dni/" + jQuery("#billing_id").val();
						jQuery.ajax({
							type : "get",
							dataType : "json",
							url : api_url,
							crossDomain: true,
							contentType: "application/json",
							headers: {"Authorization": "Bearer 25385246e12579caa00dab3386ccd6df5d9b3f5ece0c2d8478ef88ae1ff46d3d" },
							success: function(response) {
								if(response.success) {
									jQuery("#billing_first_name").val(response.data.nombres);
									jQuery("#billing_last_name").val(response.data.apellido_paterno + " " +response.data.apellido_materno);
								} else {
									alert("DNI no hallado en el verificador, por favor verifique su no de DNI.");
								}
							}
						});
						$(this).removeClass('invalid_field_at_checkout');
						jQuery("#place_order").prop( "disabled", false );
						wrapper.addClass('woocommerce-validated');
					}
				} else if (selected_billing_document_type == 'Pasaporte' ) {
					if (jQuery("#billing_id").val().length != 12 ){
						error = true;
						error_message = "Pasaporte debe contener 12 dígitos.";
						alert(error_message);
					}
				} else if (selected_billing_document_type == 'CE' ) {
					if (jQuery("#billing_id").val().length != 12 ){
						error = true;
						error_message = "CE debe contener 12 dígitos.";
						alert(error_message);
					}
				}
				if (error) {
					elementObject.addClass('invalid_field_at_checkout');
					wrapper.addClass('woocommerce-invalid');
					alert(error_message);
					jQuery("#place_order").prop( "disabled", true );
					jQuery("#billing_id").focus();
				} else {
					elementObject.removeClass('invalid_field_at_checkout');
					jQuery("#place_order").prop( "disabled", false );
					wrapper.addClass('woocommerce-validated');
				}
			}
			// ************************* billing_id onChange
			$('body').on('blur change', '#billing_id', function(){
				var wrapper = $(this).closest('.form-row');
				var billing_document = document.getElementsByName('factura');
				var selected_billing_document;

				for (var i = 0, length = billing_document.length; i < length; i++) {
					if (billing_document[i].checked) {
						selected_billing_document = billing_document[i].value;
						break;
					}
				}
				if ( selected_billing_document == "Factura"){
					// Check billing_id on SUNAT
					// console.log('Check on SUNAT');
					var api_url = "https://apiperu.dev/api/ruc/" + jQuery("#billing_id").val();
					jQuery.ajax({
						type : "get",
						dataType : "json",
						url : api_url,
						crossDomain: true,
						contentType: "application/json",
						headers: {"Authorization": "Bearer 25385246e12579caa00dab3386ccd6df5d9b3f5ece0c2d8478ef88ae1ff46d3d" },
						success: function(response) {
							if(response.success) {
								jQuery("#billing_company").val(response.data.nombre_o_razon_social);
								jQuery("#billing_address_1").val(response.data.direccion);
							} else {
								alert("RUC no hallado en el verificador, por favor verifique el no de RUC.");
							}
						}
					});
				} else {
					onChangeBillingDocument($(this));
				}
	        });
			// ************************* billing_id_document_type onChange
			$('body').on('blur change', '#billing_document_type', function(){
				onChangeBillingDocument($(this));
			});
		});
	</script>
	<?php
}



function create_TDV_order($order_id){
	if ( ! $order_id ){
		error_log("No Order ID $order_id");
        return;
	}
	//ARRAY PRODUCTO - SKU
	$arr_sku_keys = array(
		"COOL MASK ESCUDO TALLA L" => "7757738000019",
		"COOL MASK ESCUDO TALLA M" => "7757738000026",
		"COOL MASK ESCUDO TALLA S" => "7757738000033",
		"COOL MASK FOREST TALLA L" => "7757738000101",
		"COOL MASK FOREST TALLA M" => "7757738000118",
		"COOL MASK FOREST TALLA S" => "7757738000125",
		"COOL MASK BEAR TALLA L" => "7757738000132",
		"COOL MASK BEAR TALLA M" => "7757738000149",
		"COOL MASK BEAR TALLA S" => "7757738000156",
		"COOL MASK TIE DYE TALLA L" => "7757738000163",
		"COOL MASK TIE DYE TALLA M" => "7757738000170",
		"COOL MASK TIE DYE TALLA S" => "7757738000187",
		"COOL MASK BLACK TALLA L" => "7757738000194",
		"COOL MASK BLACK TALLA M" => "7757738000200",
		"COOL MASK BLACK TALLA S" => "7757738000217",
		"COOL MASK BLUE TALLA M" => "7757738000231",
		"COOL MASK BLUE TALLA S" => "7757738000248",
		"COOL MASK LIGHTBLUE TALLA L" => "7757738000255",
		"COOL MASK LIGHTBLUE TALLA M" => "7757738000262",
		"COOL MASK LIGHTBLUE TALLA S" => "7757738000279",
		"COOL MASK ALIANZA BLANQUIAZUL TALLA S" => "7757738000286",
		"COOL MASK ALIANZA BLANQUIAZUL TALLA M" => "7757738000293",
		"COOL MASK ALIANZA BLANQUIAZUL TALLA L" => "7757738000309",
		"COOL MASK ALIANZA AZUL TALLA S" => "7757738000316",
		"COOL MASK ALIANZA AZUL TALLA M" => "7757738000323",
		"COOL MASK ALIANZA AZUL TALLA L" => "7757738000330",
		"COOL MASK ALIANZA BLANCO TALLA S" => "7757738000347",
		"COOL MASK ALIANZA BLANCO TALLA M" => "7757738000354",
		"COOL MASK JEFFERSON FARFÁN TALLA S" => "7757738000408",
		"COOL MASK JEFFERSON FARFÁN TALLA M" => "7757738000415",
		"COOL MASK JEFFERSON FARFÁN TALLA L" => "7757738000422"
			);

	// Get an instance of the WC_Order object
	$order = wc_get_order( $order_id );
	$order_data = $order->get_data();
	$order_key = $order->get_order_key();

	if ( $order->get_meta( '_wc_acof_12' ) ==  "Si" ){
		error_log("Order $order_id is already uploaded.");
        return;
	}

	$order_meta_data_factura = $order->get_meta('factura');
	$order_meta_data_distrito_id = $order->get_meta('_billing_distrito');
	$order_meta_data_distrito = rt_ubigeo_get_distrito_por_id( $order_meta_data_distrito_id );

	$shipping_address_checked = ( $order->get_billing_address_1() != $order->get_shipping_address_1() )? true : false;

	if ($shipping_address_checked) {
		$order_meta_data_shipping_distrito_id = $order->get_meta('_shipping_distrito');
		$order_meta_data_shipping_distrito = rt_ubigeo_get_distrito_por_id( $order_meta_data_shipping_distrito_id );
	} else {
		$order_meta_data_shipping_distrito = array(
			'ubigeo'  =>  '',
		);
	}

	$order_type_int = 0;
	$order_type = get_field('tipo_de_orden', $order_id);

	if ($order_type == 'Donacion') {
		$order_type_int = 3;
	} else if ($order_type == 'Muestras') {
		$order_type_int = 1;
	} else {
		$order_type_int = 2;
	}

	error_log("Valor de order_type: $order_type - ID: $order_type_int");

	if ($order_meta_data_factura == 'Factura') {
		$order_client_name = $order_data['billing']['company'];
		$order_doc_type = 'FA';
	} else {
		$order_client_name = $order_data['billing']['last_name'] . ", " . $order_data['billing']['first_name'];
		$order_doc_type = 'BV';
	}

	$product_list = [];

	foreach ( $order->get_items() as $item_id => $item ) {
		if( $item['variation_id'] > 0 ){
			$product_id = $item['variation_id']; // variable product
		} else {
			$product_id = $item['product_id']; // simple product
		}

		// Get the product object
		$product = wc_get_product( $product_id );

		if( $product->is_type('variation') ){
			// Get the variation attributes
			$variation_attributes = $product->get_variation_attributes();
			// Loop through each selected attributes
			foreach($variation_attributes as $attribute_taxonomy => $term_slug ){
				// Get product attribute name or taxonomy
				$taxonomy = str_replace('attribute_', '', $attribute_taxonomy );
				// The label name from the product attribute
				$attribute_name = wc_attribute_label( $taxonomy, $product );
				// The term name (or value) from this attribute
				if( taxonomy_exists($taxonomy) ) {
					$attribute_value = get_term_by( 'slug', $term_slug, $taxonomy )->name;
				} else {
					$attribute_value = $term_slug; // For custom product attributes
				}
			}
			$sku = $product->get_sku();
		}else{

			$attribute_value = "";
			//$get_meta_data_n = $item->get_data()['meta_data'];
			$get_meta_data = $item->get_meta_data();
			$len_meta_data = count($get_meta_data);
			$slice_array_meta_data = array_slice($get_meta_data,1,$len_meta_data);
			//
			$sub_products = [];
			$sku_all = "";
			foreach($slice_array_meta_data as $key=>$value){

				$sub_producto  = preg_replace('!\s+!', ' ', $value->get_data()["key"]);
				$talla = $value->get_data()["value"];

				$sub_product = array(
					'sub_producto'  => $sub_producto,
					'talla' 		=> $talla
									);

				array_push($sub_products, $sub_product);
				$attribute_value = "";//$sub_products;
				// SKU Zone.
				$sub_producto_mod = str_replace("CoolMask ","",$sub_producto);
				//"COOL MASK LIGHTBLUE TALLA M" => "7757738000262",
				$sku_key_name = "COOL MASK"." ".strtoupper($sub_producto_mod)." TALLA ".strtoupper($talla);
				$sku_ = $arr_sku_keys[$sku_key_name];
				$sku_4 = substr($sku_,-4);


				$sku_all = $sku_4.",".$sku_all;
				}
				$sku_all = substr($sku_all,0,-1); //Quitamos la última coma
				$sku = "PACK_C(".$sku_all.")";
		}




		$product_detail= [];
		if( $product->is_on_sale() ) {
        	$sales_price = (float) number_format( $product->get_sale_price(), 2 );
		} else {
        	$sales_price = (float) number_format( $product->get_regular_price(), 2 );
		}

		$product_detail = array(
			'codigo'			=> $sku,
			'descripcion'		=> $product->get_title(),
			'color'				=> "",
			'talla'				=> $attribute_value,
			'cantidad'			=> $item->get_quantity(),
			'preciounitario'	=> $sales_price
		);

		array_push($product_list, $product_detail);
	}

	$order_payment_date = $order->get_date_paid();
	if (isset($order_payment_date)) {
		$date_created_1 = $order_data['date_created']->date('Y-m-d') . 'T' . $order_data['date_created']->date('H:i:s');
	}else {
		$date_created_1 = "";
	}
	$date_payment_1 = $order_payment_date->date('Y-m-d') . 'T' . $order_payment_date->date('H:i:s');

	if ($order->get_meta('factura') == 'Factura') {
		$TipoDocumento = "006";
	} else {
		if ($order->get_meta('billing_document_type') == 'DNI') {
			$TipoDocumento = "001";
		} else {
			$TipoDocumento = "004";
		}
	}
	$shipping_order_number = 'TV' . strval($order->get_order_number());

	$data_4_tdv = array(
		'FechaPedido' 		=> $date_created_1,
		'codTipoCP'			=> $order_doc_type,
		'TipoPedido'		=> $order_type_int,
		'NroPedido'			=> $order->get_order_number(),
		'NroGuia'			=> $shipping_order_number,
		'FechaPago'			=> $date_payment_1,
		'TipoDocumento'		=> $TipoDocumento,
		'NroDocumento'		=> $order->get_meta('billing_id'),
		'NomCliente'		=> $order_client_name,
		'DirCliente'		=> $order_data['billing']['address_1']  . ", " . $order_data['billing']['address_2'],
		'EmailCliente'		=> $order_data['billing']['email'],
		'TelefonoCliente'	=> $order_data['billing']['phone'],
		'Descuento'			=> (float) $order_data['discount_total'],
		'Flete'				=> (float) $order_data['shipping_total'],
		'UbigeoCliente'		=> $order_meta_data_distrito['ubigeo'],
		'ProvinciaEntrega'	=> $order->get_billing_state(),
		'DistritoEntrega'	=> $order_data['billing']['city'],
		'DirecciondeEnvio'	=> $shipping_address_checked,
		'DirClienteEntrega'	=> $order_data['shipping']['address_1']  . ", " . $order_data['shipping']['address_2'],
		'NombreEntrega'		=> $order_data['shipping']['first_name'],
		'ApellidoEntrega'	=> $order_data['shipping']['last_name'],
		'UbigeoEntrega'		=> $order_meta_data_shipping_distrito['ubigeo'],
		'OrderNotes'		=> $order->get_customer_note(),
		'listaDetalle'		=> $product_list
	);
	/* TEST ZONE
	$url_test = "https://698dd116ed5aab70a783404fa305b6df.m.pipedream.net";
	$test_post= wp_remote_post( $url_test, array(
			'method' => 'POST',
			'headers' => array(
				'Content-Type' 	=> 'application/json; charset=utf-8'
			),
			'body' => json_encode($data_4_tdv)
		) );
	*/

	$order->update_meta_data( '_wc_acof_12', "No" );

	$login_url = "https://tdv4.textildelvalle.pe/TiendaVirtual/api/login";

	$user = [];
	$user['usuario'] 	= "";
	$user['password'] 	= '';
	$add_2_cron = false;

	$login_response = wp_remote_post( $login_url, array(
		'method' => 'POST',
		'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
		'body' => json_encode($user)
	)
									);
	if ( is_wp_error( $login_response ) or wp_remote_retrieve_response_code( $login_response ) != 200 ) {
		if ( is_wp_error( $login_response ) ){
			$error_message = $login_response->get_error_message();
		} else {
			$error_message = wp_remote_retrieve_response_message( $login_response );
		}
		$order->update_meta_data( '_wc_acof_12', 'No' );
		// update_post_meta($order_id, '_wc_acof_12', 'No')
		$order->update_meta_data( '_wc_acof_13', $error_message );
		error_log("Error login TDV Endpoint: $error_message");
	} else {
		$response_data_json = wp_remote_retrieve_body($login_response);
		$response_data = json_decode($response_data_json, true);
		$id_token = $response_data['token'];
 		$body = json_encode($data_4_tdv);

		$create_order_tdv_url = "https://tdv4.textildelvalle.pe/TiendaVirtual/api/Pedido/create";
		$create_order_tdv_response = wp_remote_post( $create_order_tdv_url, array(
			'method' => 'POST',
			'headers' => array(
				'Authorization' => 'Bearer ' . $id_token,
				'Content-Type' 	=> 'application/json; charset=utf-8'
			),
			'body' => json_encode($data_4_tdv)
		) );
		$response_data = wp_remote_retrieve_body($create_order_tdv_response);
		if ( is_wp_error( $create_order_tdv_response ) or wp_remote_retrieve_response_code( $create_order_tdv_response ) != 201 ) {
			if (is_wp_error( $create_order_tdv_response )) {
				$error_message = $create_order_tdv_response->get_error_message();
			} else {
				$error_code = wp_remote_retrieve_response_code( $create_order_tdv_response );
				$error_message = "Error accediendo el Servidor de TDV. Código: " . $error_code;
				$error_message .= " " . wp_remote_retrieve_response_message( $create_order_tdv_response );
				$response_order_json = wp_remote_retrieve_body($create_order_tdv_response);
				$response_data = json_decode($response_order_json, true);
				$error_message .= " - " . $response_data["Mensaje"];
			}
			$order->update_meta_data( '_wc_acof_12', 'No' );
			$order->update_meta_data( '_wc_acof_13', $error_message );

			error_log($error_message );
		} else {
			// Flag the action as done (to avoid repetitions on reload for example)
			$order->update_meta_data( '_wc_acof_12', "Si" );
		}
	}
	$order->save();
}

function wrap_2_create_TDV_order( $order_id, $old_status, $new_status, $order ){
	error_log("ID de Pedido: $order_id , Estado anterior: $old_status - Estado actual: $new_status");
	if (( $old_status == "pending" and $new_status == "processing" ) or $new_status == "completed" ){
        create_TDV_order($order_id);
    }
}

add_action( 'woocommerce_order_status_changed', 'wrap_2_create_TDV_order', 99, 4 );

?>
