<?php

use mi_vita\libs\Url;
use mi_vita\libs\Debug;

require __DIR__ . '/libs/Url.php';

/*
	REST

*/

function get_coupons( $data ) {
	global $wpdb;
  
	$prefix = $wpdb->prefix;

	$cupones = $wpdb->get_results("SELECT post_name, meta_key, meta_value
	FROM `{$prefix}posts` AS pc
	INNER JOIN `{$prefix}postmeta` AS pmc ON  pc.`ID` = pmc.`post_id`
	WHERE pc.post_type = 'shop_coupon'
	AND `meta_key`= 'product_ids'");

	return $cupones;
}

function validate_as_member()
{
    $rut  = $_GET['rut'] ?? null;

    if (empty($rut)){
        echo json_encode([
            'status' => null,
            'msg'    => "No hay RUT",
            'data' => ['is_member' => false]
        ]);

        return;
    }

	$url  = MIVITA_API_IN_USE;
	$url .= '?secret='. SECRET. '&apikey='. API_KEY . "&rut=$rut";

	/*
		{"status": {"estado": "VIGENTE"}}

        Suele usarse  wp_remote_post() para consumir APIs:
        https://maswordpress.info/questions/5180/enviar-datos-la-api-de-terceros-con-wp-remote-post-en-wp-log
	*/
	$ret = Url::consume_api($url, 'GET');

	if ($ret['http_code'] != 200){
        if(!session_id()) {
            session_start();
        }

        $_SESSION['mivita_member'] = false;
	} else {
        $data   = $ret['data'];
        $estado = $data["status"]['estado'] ?? null;
    
        if(!session_id()) {
            session_start();
        }
    
        $_SESSION['mivita_member'] = ($estado == 'VIGENTE');
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
    echo json_encode([
        'status' => $ret['http_code'],
        'msg'    => $ret['error'],
        'data' => ['is_member' => $_SESSION['mivita_member']]
    ]);
}

/*
	/wp-json/mi-vita/v1/xxxxx
*/
add_action( 'rest_api_init', function () {
    #	/wp-json/mi-vita/v1/mivita_members
	register_rest_route( 'mi-vita/v1', '/mivita_members', array(
		'methods' => 'GET',
		'callback' => 'validate_as_member',
        'permission_callback' => '__return_true'
	) );
	
	#	/wp-json/mi-vita/v1/coupons
	register_rest_route( 'mi-vita/v1', '/coupons', array(
		'methods' => 'GET',
		'callback' => 'get_coupons',
        'permission_callback' => '__return_true'
	) );
} );




