<?php

/*
	Settings
*/

/*
	API
*/
define('API_KEY', 'test');
define('SECRET',  'test');
define('MIVITA_API_DEV',  'https://x163aso6z1.execute-api.us-west-2.amazonaws.com/wsvitadev/validar');
define('MIVITA_API_PROD', 'https://m30gs3t5a0.execute-api.us-west-2.amazonaws.com/wsmivita/prod/validar');

/*
	Defino si uso la API de producción o la de desarrollo
*/
define('MIVITA_API_IN_USE', MIVITA_API_PROD);

/*
	Título
*/
define('SECTION_HEADER', 'Descuentos con Mi Vita');

/*
	Texto del Input
*/
define('INPUT_VISIBILITY', false);
define('INPUT_PLACEHOLDER', 'Ingrese su RUT ej: xxxxxxxx-x');

/*
	Texto del Botón
*/
define('VALUE_BUTTON', 'Validar RUT&nbsp;&nbsp;&nbsp;&nbsp;');

/*
	Mensajes
*/
define('MEMBERSHIP_VERIFIED', 'Bienvenido! Membresía verificada.');
define('MEMBERSHIP_NOT_VERIFIED', 'Ud. parece no ser miembro.');
define('SERVICE_UNAVAILABLE', 'Servicio no disponible');
define('UNKNOWN_ERROR', 'Error desconocido.');
define('RUT_IS_REQUIRED', '<strong>RUT</strong> es un campo requerido.');
define('RUT_IS_INVALID', '<strong>RUT</strong> no es válido.');

/*
	Cuponera
*/
define('OPEN_COUPON_BOX_WHEN_IS_VALID', true);