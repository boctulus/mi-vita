<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
	Settings
*/

define('API_KEY', 'test');
define('SECRET',  'test');
define('MIVITA_API_DEV',  'https://x163aso6z1.execute-api.us-west-2.amazonaws.com/wsvitadev/validar');
define('MIVITA_API_PROD', 'https://m30gs3t5a0.execute-api.us-west-2.amazonaws.com/wsmivita/prod/validar');

/*
	Defino si uso la API de producción o la desarrollo
*/
define('MIVITA_API_IN_USE', MIVITA_API_PROD);