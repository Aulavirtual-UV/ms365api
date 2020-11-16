<?php

//****************
//****  config.php
//****************

define ('DEBUG', 1);

// OFF365 
define ('TENANTID',             '');
define ('CLIENTID',             '');
define ('CLIENTSECRET',         '');
define ('UA_AULAVIRTUAL_20_21', '');
define ('UA_AULAVIRTUAL_21_22', '');
define ('UA_AULAVIRTUAL_22_23', '');
define ('UA_AULAVIRTUAL_23_24', '');
define ('UA_AULAVIRTUAL_24_25', '');
define ('UA_COMUNIDADES',       '');
define ('API_VERSION',          'beta');
define ('OFF365ADMIN',          '@uv.es');
define ('OFF365EMAIL',          '@uv.es');
define ('AULAVIRTUALEMAIL',     '@uv.es');
define ('TEAM_TEMPLATE_STA',    'standard');
define ('TEAM_TEMPLATE_EDU',    'educationClass');
define ('TIME2REFRESH_TOKEN',   60 * 45); //45 minutes in seconds
define ('GRAPH_TIMEOUT',        1000);
define ('PORTALOFFICE365',      'https://portal.office.com');
define ('MAILPREFIX',           '[OFF365] ');
define ('ID_PASSWORD_METHOD',   '');

// En uso:
define ('SKU_PROFESOR_A1', '');  // "skuPartNumber": "STANDARDWOFFPACK_IW_FACULTY"
define ('SKU_ALUMNO_A3',   '');  // "skuPartNumber": "ENTERPRISEPACKPLUS_STUDENT"

define ('SKU_PROFESOR_A3', '');  // "skuPartNumber": "ENTERPRISEPACKPLUS_FACULTY"
define ('SKU_ALUMNO_A1',   '');  // "skuPartNumber": "STANDARDWOFFPACK_IW_STUDENT"

// MOODLE PROD POSTGRESQL
define ('DB_PROD_NAME',     '');
define ('DB_PROD_USER',     '');
define ('DB_PROD_PASSWORD', '');
define ('DB_PROD_HOST',     '');
define ('DB_PROD_PORT',     );
define ('DSN_PROD',         "host=" . DB_PROD_HOST . " port=" . DB_PROD_PORT . " user='" . DB_PROD_USER . "' password='" . DB_PROD_PASSWORD . "' dbname=" . DB_PROD_NAME);

// MOODLE DES POSTGRESQL
define ('DB_DES_NAME',     '');
define ('DB_DES_USER',     '');
define ('DB_DES_PASSWORD', '');
define ('DB_DES_HOST',     '');
define ('DB_DES_PORT',     );
define ('DSN_DES',         "host=" . DB_DES_HOST . " port=" . DB_DES_PORT . " user='" . DB_DES_USER . "' password='" . DB_DES_PASSWORD . "' dbname=" . DB_DES_NAME);

// Usar la DB de Desarrollo (DSN_DES) / Producción (DSN_PROD)
define ('DSN',             DSN_PROD);

define ('TMDLUVO365',         'mdl_uv_o365');    
define ('TMDLUVO365_PRUEBAS', 'mdl_uv_o365_pruebas');

define ('TIPO_DOCENCIA',   1);
define ('TIPO_COMUNIDAD',  2);
define ('TIPO_TITULACION', 3);

define ('LOG_TIPO',   2); //0 syslog, 1 stdout, 2 both
define ('DEBUG_TIPO', 0); //0 syslog, 1 stdout, 2 both

define ('NOREPLYEMAIL',  'no-reply@uv.es');

define ('LOOP_MAXCOUNT', 10);
define ('LOOP_SLEEP',    5);  // in seconds

define ('ROL_KING_1',    'profesor');
define ('ROL_KING_2',    'gestor');
define ('ROL_PAWN_1',    'alumno');
define ('ROL_PAWN_2',    'member');

?>
