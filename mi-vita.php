<?php
/*
Plugin Name: Mi Vita
Description: integración de MiVita con WooCommerce
Version: 1.0.0
Author: boctulus@gmail.com <Pablo>
*/

use mi_vita\libs\Debug;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/libs/Debug.php';
require __DIR__ . '/config.php';

if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

/**
 * Check if WooCommerce is active
 */
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

add_action('init', 'start_session', 1);

function start_session() {
	if(!session_id()) {
		session_start();
	}
}

/*
	La variable de session $_SESSION['mivita_member'] debe ser seteada
	al validar el campo de RUT en el cajón de "cupones"

*/
function filter_woocommerce_coupon_is_valid($is_valid, $coupon) { 
	$name = $coupon->get_code();

	$is_member = $_SESSION['mivita_member'] ?? false;

	if (substr($name, 0, 3) == 'mvt'){
		if (!$is_member){
			$is_valid = false;
		}
	}

    return $is_valid; 
}
         
// add the filter 
add_filter( 'woocommerce_coupon_is_valid', 'filter_woocommerce_coupon_is_valid', 10, 2 ); 


/*
	La idea acá es colocar un JS que mueva dentro del DOM el INPUT TEXT a otra ubicación
	(a dentro de la caja de cupones)

	Cómo moverlo:
	https://stackoverflow.com/a/20910214/980631

*/
add_action('woocommerce_after_checkout_form', 'boctulus_add_jscript_checkout');

function boctulus_add_jscript_checkout() {
?>
	<!-- HTML/CSS/JS HERE -->

	<style>
		.error {
			border: 1px solid  #a00 !important;
		}
	</style>

	<script type="text/javascript">
		function removeShowCouponFeature(){
			//var element = document.querySelectorAll(".showcoupon")[0];
			//element.classList.remove("showcoupon");
		}

		function show_validation_error(){
			document.querySelector('#user_rut').classList.add('error');
		}

		function remove_validation_error(){
			document.querySelector('#user_rut').classList.remove('error');
		}

		function modify_checkout_coupon(){
			form1 = document.querySelectorAll('form.checkout_coupon.woocommerce-form-coupon')[0];
			form2 = document.querySelectorAll('form.checkout.woocommerce-checkout')[0];
			pform = document.querySelectorAll('form.checkout_coupon.woocommerce-form-coupon > p')[0];

			setTimeout(() => {
				form1.style.removeProperty("display");
				removeShowCouponFeature();
			}, 500);

			var div = document.createElement('div');
			div.setAttribute("id", "new_div");
			div.innerHTML = `
			<p>
				<h3>
					Descuentos con Mi Vita
				</h3>
			</p></br/>

			<div style="margin-top: -15px;">
				<p class="form-row form-row-first my-field-class form-row-wide validate-required" id="user_rut_field" data-priority="" style="">
					<input type="text" class="input-text" id="u_rut" placeholder="ingrese su RUT" value="">
				</p>

				<p class="form-row form-row-last">
				<button type="submit" class="button" id="validate_rut">Validar RUT&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</button>
				</p>
			</div>
			<p/>

			<div class="clear"></div>
			
			<div class="woocommerce-NoticeGroup" style="margin-top: 10px; margin-left:-12px;">
				<ul class="woocommerce-error" role="alert">
					<li><strong>RUT</strong> no es de un miembro.</li>
				</ul>
			</div>
			<p/>

			<div class="clear" style="margin-bottom: -20px;"></div>`;

			form1.insertBefore(div, pform);

			document.addEventListener("click", function(){
				let btn = document.getElementById("validate_rut");
				let rut = document.getElementById("u_rut").value;

				validate(rut);
			});
		}

		/*
			Usar para copiar el valor del RUT al INPUT 
		*/
		function set_rut(rut){
			document.getElementById("user_rut").value = rut;
		}

		/*
			Consume /wp-content/plugins/mi-vita/ajax.php?rut=xxxxxxxxxx

			Valida y copia el valor del RUT a al campo RUT <<hidden>>

			u_rut -> user_rut
		*/
		function validate(rut){
			<?php
				$ini = strpos(__DIR__, '/wp-content/');
				$rel_path = substr(__DIR__, $ini);
			?>

			const endpoint = '<?php echo $rel_path ?>/ajax.php';

			let url = endpoint + '?rut=' + rut;
			
			/*
				{
					"status":200,
					"msg":"",
					"data":{
						"is_member":true
					}
				}    
			*/

			fetch(url)
			.then(function(res) {
				return res.json();
			})
			.then(res => {
				if (res.status != 200){
					console.log('Error code: ' +res.status);
				} else {
					let is_member = res.data.is_member;
					console.log(is_member);
				}
			})
			.catch(err => {
				// handle the error

				console.log(err);
			});

		}


		modify_checkout_coupon();
	</script>

<?php
}


#Adding a Custom Special Field
#To add a custom field is similar. Let’s add a new field to checkout, after the order notes, by hooking into the following:

/**
 * Add the field to the checkout
 */

add_action( 'woocommerce_after_order_notes', 'add_rut_field' );

function add_rut_field($checkout)
{
	define('VISIBLE', true);

	$old_val = $checkout->get_value( 'user_rut' );

    echo '<div id="rut">';

	if (!VISIBLE){
		echo "<input type=\"hidden\" class=\"input-text\" name=\"user_rut\" id=\"user_rut\" value=\"$old_val\" />";
	} else {		
    	woocommerce_form_field( 'user_rut', array(
        'type'          => 'text',
        'class'         => array('my-field-class form-row-wide'),
        'label'         => __('RUT'),
        'placeholder'   => __('ingrese su RUT'),
		'required'		=> true
        ), $checkout->get_value( 'user_rut' ));
	}
	
    echo '</div>';

}

#Next we need to validate the field when the checkout form is posted. For this example the field is required and not optional:

/**
 * Process the checkout
 */
add_action('woocommerce_checkout_process', 'rut_process');

function rut_process() {
    // Check if set, if its not set add an error.
    if ( ! $_POST['user_rut'] )
        wc_add_notice( __('<strong>RUT</strong> es un campo requerido.'), 'error' );
}

#Finally, let’s save the new field to order custom fields using the following code:

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'rut_update_order_meta' );

function rut_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['user_rut'] ) ) {
        update_post_meta( $order_id, 'RUT', sanitize_text_field( $_POST['user_rut'] ) );
    }
}