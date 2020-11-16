#!/usr/bin/php
<?php

//************************************************
//*********  graph_doRefreshCommunitiesMembers.php
//************************************************

require_once __DIR__ . '/vendor/autoload.php';
require_once "includes/config.php";
require_once "includes/utils.php";

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;

$progName = basename(__FILE__, '.php'); 
initProgram ($progName);

$start_time = microtime(true);

$accessToken = createAccessToken ($error_code, $error_msg);        if ($error_code != 200) {Error ($error_code, $error_msg, 10);}
$graph = createGraphObject ($accessToken, $eror_code, $error_msg); if ($error_code != 200) {Error ($error_code, $error_msg, 20);}
db_init($db);
//número que identifica el curso académico
$numpar = db_getCurrentNumpar ($db);

$long_year  = db_getCurrentYearFromCurrentNumpar ($db);
$short_year = substr ($long_year, 2, 2);

$sql = db_getSqlComunidadesToRefresh ($numpar, $long_year, $short_year);
$result = pg_query ($db, $sql) or die('Query failed: ' . pg_last_error());
$cont = 0;
while ($row = pg_fetch_assoc($result)){
  ++$cont;
  $idnumber   = trim($row['idnumber']);   

  $sql2 = "SELECT idoff365 FROM mdl_uv_o365 WHERE idnumber = '" . $idnumber . "' AND estado = 'done' AND accion = 'CREAGROUP' AND enabled = 't'";
  // $idnumber = 'c21c033a44822gPT';
  // $sql2 = "SELECT idoff365 FROM mdl_uv_o365 WHERE idnumber = '" . $idnumber . "' AND estado = 'done' AND accion = 'CREAGROUP'";

  $result2 = pg_query ($db, $sql2) or die('Query failed: ' . pg_last_error());
  $tot = pg_num_rows ($result2);
  $row2 = pg_fetch_assoc($result2);
  if ($tot == 1) {$idoff365 = trim($row2['idoff365']);} else {$idoff365 = '';}

  //xlog ("$cont idnumber=$idnumber idoff365=$idoff365");

  if ($idoff365 <> '') {
    xlog ("Refreshing $idoff365");

    $data_curso = db_parseaCurrutaca ($idnumber);
    $tipo_grupo = $data_curso['tipo'];
    $group_id = $idoff365;
    if (($tipo_grupo == TIPO_DOCENCIA) or ($tipo_grupo == TIPO_TITULACION)) {$ROL_KING = ROL_KING_1; $ROL_PAWN = ROL_PAWN_1;} elseif ($tipo_grupo == TIPO_COMUNIDAD) {$ROL_KING = ROL_KING_2; $ROL_PAWN = ROL_PAWN_2;}
    $sql_rol_king = db_getSqlMiembrosGrupos ($idnumber, $numpar, $tipo_grupo, $ROL_KING, $data_curso['asignatura'], $data_curso['grupo'], $data_curso['tlugar'], $data_curso['lugar']);
    $result3 = pg_query ($db, $sql_rol_king) or die('Query failed: ' . pg_last_error());
    while ($row3 = pg_fetch_assoc($result3)){
      $email   = trim($row3['email']); $usuario = trim($row3['usuario']); $firstname = trim($row3['firstname']); $lastname = trim($row3['lastname']);
      xdebug (ROL_KING_1 . " email=$email usuario=$usuario firstname=$firstname lastname=$lastname");
      if (existsUserFromUserPrincipalName ($graph, $email, $error_code, $error_msg) == false) {
        $password = getNewRandomPassword();
        xlog ("   -->Creating " . ROL_KING_1 . " $email ($usuario)");
        $userid = createUser ($graph, $usuario, $email, $firstname, $lastname, $password, $error_code, $error_msg);
        xlog ("   -->Enviando emails");
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Creada cuenta en Office365", mail_msgNuevoUsuarioEnvioCambioPassword ($firstname, $lastname, $usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
        xsleep(2);
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Contraseña", mail_msgNuevoUsuarioEnvioEsLaPassword ($usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
        xlog ("   -->Ajustando licencias a usuario");
        setLicensesToUser ($graph, ROL_KING_1, $email, $error_code, $error_msg);
      }
      xlog ("   -->Insertando " . ROL_KING_1 . " ($email) en grupo ($group_id)");
      insertMemberInGroup ($graph, $group_id, $email, ROL_KING_1, $error_code, $error_msg); if ($error_code != 200) {if (Error ($error_code, $error_msg, 100, 1) == 1) continue;}
    }

    $sql_rol_pawn = db_getSqlMiembrosGrupos ($idnumber, $numpar, $tipo_grupo, $ROL_PAWN, $data_curso['asignatura'], $data_curso['grupo'], $data_curso['tlugar'], $data_curso['lugar']);
    $result3 = pg_query ($db, $sql_rol_pawn) or die('Query failed: ' . pg_last_error());
    while ($row3 = pg_fetch_assoc($result3)){
      $email   = trim($row3['email']); $usuario = trim($row3['usuario']); $firstname = trim($row3['firstname']); $lastname = trim($row3['lastname']);
      xdebug (ROL_PAWN_1 . " email=$email usuario=$usuario firstname=$firstname lastname=$lastname");
      if (existsUserFromUserPrincipalName ($graph, $email, $error_code, $error_msg) == false) {
        $password = getNewRandomPassword();
        xlog ("  -->Creating " . ROL_PAWN_1 . " $email ($usuario)");
        $userid = createUser ($graph, $usuario, $email, $firstname, $lastname, $password, $error_code, $error_msg);
        xlog ("   -->Enviando emails");
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Creada cuenta en Office365", mail_msgNuevoUsuarioEnvioCambioPassword ($firstname, $lastname, $usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
        xsleep(2);
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Contraseña", mail_msgNuevoUsuarioEnvioEsLaPassword ($usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
        xlog ("   -->Ajustando licencias a usuario");
        setLicensesToUser ($graph, ROL_PAWN_1, $email, $error_code, $error_msg);
      }
      xlog ("   -->Insertando " . ROL_PAWN_1 . " ($email) en grupo ($group_id)");
      insertMemberInGroup ($graph, $group_id, $email, ROL_PAWN_1, $error_code, $error_msg); if ($error_code != 200) {if (Error ($error_code, $error_msg, 100, 1) == 1) continue;}
    }

  } 

}

endProgram ();

?>
