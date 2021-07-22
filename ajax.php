<?php

namespace mi_vita;

use mi_vita\libs\Url;
use mi_vita\libs\Debug;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require __DIR__ . '/config.php';
require __DIR__ . '/libs/Debug.php';
require __DIR__ . '/libs/Url.php';


if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

function validate_as_member(){
	// leer SECRET y APIKET de config.php

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

validate_as_member();