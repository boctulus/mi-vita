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

		.mivita_box {
			border: 1px solid #d3ced2;
			padding: 20px;
			margin: 2em 0;
			text-align: left;
			border-radius: 5px;
		}

		.show{
			display:block;
		}

		.hide{
			display:none;
		}
	</style>

	<script type="text/javascript">	
		function show_validation_error(){
			document.querySelector('#user_rut').classList.add('error');
		}

		function remove_validation_error(){
			document.querySelector('#user_rut').classList.remove('error');
		}

		function modify_checkout_coupon(){
			woocommerce = document.querySelectorAll('div.woocommerce')[0];
			toggle = document.querySelectorAll('div.woocommerce-form-coupon-toggle')[0];
			form1  = document.querySelectorAll('form.checkout_coupon.woocommerce-form-coupon')[0];
			form2  = document.querySelectorAll('form.checkout.woocommerce-checkout')[0];
			pform  = document.querySelectorAll('form.checkout_coupon.woocommerce-form-coupon > p')[0];

			var form = document.createElement('form');
			form.setAttribute("id", "mivita_desc");
			form.classList.add("mivita_box");
			form.innerHTML = `
			<h3>
				<?php echo SECTION_HEADER ?>
			</h3>

			</br/>

			<div style="margin-top: -15px">
			<p class="form-row form-row-first my-field-class form-row-wide validate-required" id="user_rut_field" data-priority="" style="">
				<input type="text" class="input-text" id="u_rut" placeholder="<?php echo INPUT_PLACEHOLDER ?>" value="">
			</p>

			<p class="form-row form-row-last">
				<button type="submit" class="button" id="validate_rut"><?php echo VALUE_BUTTON ?></button>
			<p/>
			</div>
			<div class="clear"></div>
			`;

			/*
				Notices !
			*/

			var div = document.createElement('div');
			div.setAttribute("id", "mivita_desc_notices");
			div.classList.add("woocommerce-NoticeGroup");
			div.innerHTML = `
			<ul class="hidden" role="alert" id="mivita_desc_notice_list">
				
			</ul>
			<p/>`;
	
			woocommerce.insertBefore(form, toggle);
			woocommerce.insertBefore(div, toggle);

			document.getElementById("mivita_desc").addEventListener('submit', function(event){
				let rut = document.getElementById("u_rut").value;
				validate(rut);
				event.preventDefault();
			});
		}

		function setMiVitaNotice(message, type){
			if (type != 'error' && type != 'info'){
				throw "Tipo de notificación inválida para " + type;
			}

			if (message === ""){
				throw "Mensaje de notificación no puede quedar vacio";
				return;
			}

			let list = document.getElementById('mivita_desc_notice_list');

			list.innerHTML = message;

			if (type == 'error'){
				list.classList.remove('woocommerce-info');
			} else {
				list.classList.remove('woocommerce-error');
			}
			
			list.classList.add('woocommerce-'+type);
			list.classList.remove('hide');
			list.classList.add('show');
		}

		function hideMiVitaNotice(){
			let list = document.getElementById('mivita_desc_notice_list');
			list.classList.remove('show');
			list.classList.add('hide');
		}

		/*
			https://gist.github.com/donpandix/f1d638c3a1a908be02d5
		*/
		const rut_validator = {
			// Valida el rut con su cadena completa "XXXXXXXX-X"
			valida : function (rutCompleto) {
				if (!/^[0-9]+[-|‐]{1}[0-9kK]{1}$/.test( rutCompleto ))
					return false;
				var tmp 	= rutCompleto.split('-');
				var digv	= tmp[1]; 
				var rut 	= tmp[0];
				if ( digv == 'K' ) digv = 'k' ;
				return (rut_validator.dv(rut) == digv );
			},
			dv : function(T){
				var M=0,S=1;
				for(;T;T=Math.floor(T/10))
					S=(S+T%10*(9-M++%6))%11;
				return S?S-1:'k';
			}
		}

		/*
			Usar para copiar el valor del RUT al INPUT 
		*/
		function set_rut(rut){
			document.getElementById("user_rut").value = rut;
		}

		function parseJSON(response) {
			return response.text().then(function(text) {
				return text ? JSON.parse(text) : {}
			})
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

			if (rut === ""){
				hideMiVitaNotice();
				return;
			}

			if (!rut_validator.valida(rut)){
				//console.log("RUT inválido");
				setMiVitaNotice('<?php echo RUT_IS_INVALID ?>', 'error');
				return;
			}

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
				return parseJSON(res);
			})
			.then(res => {
				if (res.status != 200){
					console.log('Error code: ' +res.status);					
					setMiVitaNotice('<?php echo SERVICE_UNAVAILABLE ?>', 'error');				
				} else {
					let is_member = res.data.is_member;
					//console.log(is_member);	
					
					if (is_member){
						set_rut(rut);
						setMiVitaNotice('<?php echo MEMBERSHIP_VERIFIED ?>', 'info');
						setTimeout(() => {
							form1.style.removeProperty("display");
						}, 500);						
					} else {
						setMiVitaNotice('<?php echo MEMBERSHIP_NOT_VERIFIED ?>', 'error');
					}
				}
			})
			.catch(err => {
				// handle the error
				console.log(err);
				setMiVitaNotice('<?php echo UNKNOWN_ERROR ?>', 'error');
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
	$old_val = $checkout->get_value( 'user_rut' );

    echo '<div id="rut" placeholder="'. INPUT_PLACEHOLDER .'">';

	if (!INPUT_VISIBILITY){
		echo "<input type=\"hidden\" class=\"input-text\" name=\"user_rut\" id=\"user_rut\" value=\"$old_val\" />";
	} else {		
    	woocommerce_form_field( 'user_rut', array(
        'type'          => 'text',
        'class'         => array('my-field-class form-row-wide'),
        'label'         => __('RUT'),
        'placeholder'   => __(INPUT_PLACEHOLDER),
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
        wc_add_notice( __(RUT_IS_REQUIRED), 'error' );
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