#!/usr/bin/php
<?php

//***********************************
//*********  graph_doAllProcesses.php
//***********************************

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
$numpar = db_getCurrentNumpar ($db);

$sql = db_getSqlActions ();
$result = pg_query ($db, $sql) or die('Query failed: ' . pg_last_error());
$cont = 0;
while ($row = pg_fetch_assoc($result)){
  ++$cont;
  db_getParamsActions ($row, $id, $idnumber, $idoff365, $nombre, $descripcion, $fechacreacion, $estado, $accion, $emailcreator);
  xlog ("------------------------------------------------------------");
  xlog ("Main iteration $cont (\"$accion\", \"$estado\", \"$idnumber\")");
  xlog (date("Y-m-d H:i:s"));                                       

  ////////////////////////////   CREAGROUP
  if (($accion == "CREAGROUP") and ($estado == 'none')) {
    $data_curso = db_parseaCurrutaca ($idnumber);  
    $tipo_grupo = $data_curso['tipo'];
    if (($tipo_grupo == TIPO_DOCENCIA) or ($tipo_grupo == TIPO_TITULACION)) {$ROL_KING = ROL_KING_1; $ROL_PAWN = ROL_PAWN_1;} elseif ($tipo_grupo == TIPO_COMUNIDAD) {$ROL_KING = ROL_KING_2; $ROL_PAWN = ROL_PAWN_2;}
    
    xlog ("   -->Creando grupo vacio \"$nombre\" tipo=$tipo_grupo");
    $group_id = createGroupEmpty ($graph, $nombre, $descripcion, $idnumber, $error_code, $error_msg); if ($error_code != 200) {if (Error ($error_code, $error_msg, 40, 1) == 1) continue;}
    xlog ("   -->   ($group_id)");
          
    xlog ("   -->Incluyendo grupo en Unidad Administrativa");
    includeGroupInAdministrativeUnit ($graph, $tipo_grupo, $data_curso['curso'], $group_id, $error_code, $error_msg); if ($error_code != 200) {if (Error ($error_code, $error_msg, 50, 1) == 1) continue;}
            
    $sql_rol_king = db_getSqlMiembrosGrupos ($idnumber, $numpar, $tipo_grupo, $ROL_KING, $data_curso['asignatura'], $data_curso['grupo'], $data_curso['tlugar'], $data_curso['lugar']);
    $result2 = pg_query ($db, $sql_rol_king) or die('Query failed: ' . pg_last_error());
    $tot = pg_num_rows ($result2);
    xlog ("   -->Total " . $ROL_KING . " " . $tot);
    $i = 0;
    while ($row2 = pg_fetch_assoc($result2)){
      ++$i;
      $email   = trim($row2['email']); $usuario = trim($row2['usuario']); $firstname = trim($row2['firstname']); $lastname = trim($row2['lastname']);  
      xdebug ($ROL_KING . " email=$email usuario=$usuario firstname=$firstname lastname=$lastname");
      if (existsUserFromUserPrincipalName ($graph, $email, $error_code, $error_msg) == false) {
        $password = getNewRandomPassword();
        xlog ("   -->Creating " . $ROL_KING . " $email ($usuario)");
        $userid = createUser ($graph, $usuario, $email, $firstname, $lastname, $password, $error_code, $error_msg);
        xlog ("   -->Enviando emails");
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Creada cuenta en Office365", mail_msgNuevoUsuarioEnvioCambioPassword ($firstname, $lastname, $usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
        xsleep(2);
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Contraseña", mail_msgNuevoUsuarioEnvioEsLaPassword ($usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}        
        xlog ("   -->Ajustando licencias a usuario");
        setLicensesToUser ($graph, $ROL_KING, $email, $error_code, $error_msg);
      }
      xlog ("   -->Insertando $i / $tot " . $ROL_KING . " ($email) en grupo ($group_id)");
      insertMemberInGroup ($graph, $group_id, $email, $ROL_KING, $error_code, $error_msg); if ($error_code != 200) {xlog("REFRESH $idnumber"); if (Error ($error_code, $error_msg, 100, 1) == 1) continue;}
    }
    
    $sql_rol_pawn = db_getSqlMiembrosGrupos ($idnumber, $numpar, $tipo_grupo, $ROL_PAWN, $data_curso['asignatura'], $data_curso['grupo'], $data_curso['tlugar'], $data_curso['lugar']);
    $result2 = pg_query ($db, $sql_rol_pawn) or die('Query failed: ' . pg_last_error());
    $tot = pg_num_rows ($result2);
    xlog ("   -->Total " . $ROL_PAWN . " " . $tot);
    $i = 0;
    while ($row2 = pg_fetch_assoc($result2)){
      ++$i;
      $email   = trim($row2['email']); $usuario = trim($row2['usuario']); $firstname = trim($row2['firstname']); $lastname = trim($row2['lastname']);  
      xdebug ($ROL_PAWN . " email=$email usuario=$usuario firstname=$firstname lastname=$lastname");
      if (existsUserFromUserPrincipalName ($graph, $email, $error_code, $error_msg) == false) {
        $password = getNewRandomPassword();
        xlog ("  -->Creating " . $ROL_PAWN . " $email ($usuario)");
        $userid = createUser ($graph, $usuario, $email, $firstname, $lastname, $password, $error_code, $error_msg);
        xlog ("   -->Enviando emails");
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Creada cuenta en Office365", mail_msgNuevoUsuarioEnvioCambioPassword ($firstname, $lastname, $usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
        xsleep(2);
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Contraseña", mail_msgNuevoUsuarioEnvioEsLaPassword ($usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}        
        xlog ("   -->Ajustando licencias a usuario");
        setLicensesToUser ($graph, $ROL_PAWN, $email, $error_code, $error_msg);
      }
      xlog ("   -->Insertando $i / $tot " . $ROL_PAWN . " ($email) en grupo ($group_id)");
      insertMemberInGroup ($graph, $group_id, $email, $ROL_PAWN, $error_code, $error_msg); if ($error_code != 200) {xlog("REFRESH $idnumber"); if (Error ($error_code, $error_msg, 100, 1) == 1) continue;}
    }  

    $b = loopFunction ("createTeamFromGroup", $graph, $group_id, $error_code, $error_msg);    
    if ($b == true) {
      xlog ("   -->Creado Team \"$nombre\" para $group_id");
      $new_estado = 'done';
      xlog ("   -->Eliminando del grupo (" . OFF365ADMIN . ")");
      deleteMemberInGroup ($graph, $group_id, OFF365ADMIN, $ROL_KING, $error_code, $error_msg);      
      xlog ("   -->Obteniendo URL de Teams");
      $teamUrl = getTeamUrl ($graph, $group_id, $error_code, $error_msg);
      xlog ("   -->Enviando email Creado equipo al Creator");
      if (sendEmail (NOREPLYEMAIL, $emailcreator, "", MAILPREFIX . "Creado nuevo equipo de Teams en Office365", mail_msgNuevoGrupoTeams ($emailcreator, $nombre, $teamUrl)) == false) {xlog ("Error enviando email a $emailcreator");}
      xlog ("   -->Ajustando valor action de tabla de Off365 a \"$new_estado\"");
      if (db_doSqlUpdateTablaOffice ($db, $id, $group_id, $new_estado, $teamUrl) <> 1) xlog ("Error updating id=$id");    
      xlog ("   -->Enviando email Aviso miembros Teams");
      $members = getMailAllMembersGroup ($graph, $group_id, $error_code, $error_msg);
      foreach ($members as $key => $email_member) {
        sendEmail (NOREPLYEMAIL, $email_member, "", MAILPREFIX . "[Teams] $nombre", mail_msgUserAddedToNuevoTeam ($email_member, $nombre, $teamUrl));
      }            
      
    } else {
      $new_estado = $estado;
      xlog ("Error en fuction createTeamFromGroup");        
    } 

  //////////////////////////// REFRESHMEMBERSGROUP  
  } elseif (($accion == "REFRESHMEMBERSGROUP") and ($estado == 'none')) {  
    $data_curso = db_parseaCurrutaca ($idnumber);   
    $tipo_grupo = $data_curso['tipo'];
    if (($tipo_grupo == TIPO_DOCENCIA) or ($tipo_grupo == TIPO_TITULACION)) {$ROL_KING = ROL_KING_1; $ROL_PAWN = ROL_PAWN_1;} elseif ($tipo_grupo==TIPO_COMUNIDAD) {$ROL_KING = ROL_KING_2; $ROL_PAWN = ROL_PAWN_2;}
    $group_id = $idoff365;                       

    $sql_rol_king = db_getSqlMiembrosGrupos ($idnumber, $numpar, $tipo_grupo, ROL_KING, $data_curso['asignatura'], $data_curso['grupo'], $data_curso['tlugar'], $data_curso['lugar']);
    $result2 = pg_query ($db, $sql_rol_king) or die('Query failed: ' . pg_last_error());
    while ($row2 = pg_fetch_assoc($result2)){
      $email   = trim($row2['email']); $usuario = trim($row2['usuario']); $firstname = trim($row2['firstname']); $lastname = trim($row2['lastname']);  
      xdebug (ROL_KING . " email=$email usuario=$usuario firstname=$firstname lastname=$lastname");
      if (existsUserFromUserPrincipalName ($graph, $email, $error_code, $error_msg) == false) {
        $password = getNewRandomPassword();
        xlog ("   -->Creating " . ROL_KING . " $email ($usuario)");
        $userid = createUser ($graph, $usuario, $email, $firstname, $lastname, $password, $error_code, $error_msg);
        xlog ("   -->Enviando emails");
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Creada cuenta en Office365", mail_msgNuevoUsuarioEnvioCambioPassword ($firstname, $lastname, $usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
        xsleep(2);
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Contraseña", mail_msgNuevoUsuarioEnvioEsLaPassword ($usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}        
        xlog ("   -->Ajustando licencias a usuario");
        setLicensesToUser ($graph, ROL_KING, $email, $error_code, $error_msg);
      }
      xlog ("   -->Insertando " . ROL_KING . " ($email) en grupo ($group_id)");
      insertMemberInGroup ($graph, $group_id, $email, ROL_KING, $error_code, $error_msg); if ($error_code != 200) {if (Error ($error_code, $error_msg, 100, 1) == 1) continue;}
    }
    
    $sql_rol_pawn = db_getSqlMiembrosGrupos ($idnumber, $numpar, $tipo_grupo, ROL_PAWN, $data_curso['asignatura'], $data_curso['grupo'], $data_curso['tlugar'], $data_curso['lugar']);
    $result2 = pg_query ($db, $sql_rol_pawn) or die('Query failed: ' . pg_last_error());
    while ($row2 = pg_fetch_assoc($result2)){
      $email   = trim($row2['email']); $usuario = trim($row2['usuario']); $firstname = trim($row2['firstname']); $lastname = trim($row2['lastname']);  
      xdebug (ROL_PAWN . " email=$email usuario=$usuario firstname=$firstname lastname=$lastname");
      if (existsUserFromUserPrincipalName ($graph, $email, $error_code, $error_msg) == false) {
        $password = getNewRandomPassword();
        xlog ("  -->Creating " . ROL_PAWN . " $email ($usuario)");
        $userid = createUser ($graph, $usuario, $email, $firstname, $lastname, $password, $error_code, $error_msg);
        xlog ("   -->Enviando emails");
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Creada cuenta en Office365", mail_msgNuevoUsuarioEnvioCambioPassword ($firstname, $lastname, $usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
        xsleep(2);
        if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Contraseña", mail_msgNuevoUsuarioEnvioEsLaPassword ($usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}        
        xlog ("   -->Ajustando licencias a usuario");
        setLicensesToUser ($graph, ROL_PAWN, $email, $error_code, $error_msg);
      }
      xlog ("   -->Insertando " . ROL_PAWN . " ($email) en grupo ($group_id)");
      insertMemberInGroup ($graph, $group_id, $email, ROL_PAWN, $error_code, $error_msg); if ($error_code != 200) {if (Error ($error_code, $error_msg, 100, 1) == 1) continue;}
    }  

    xlog ("   -->Ajustando valor action de tabla de Off365 a \"done\"");
    if (db_doSqlUpdateTablaOffice ($db, $id, $group_id, 'done', '') <> 1) xlog ("Error updating id=$id");    

  //////////////////////////// CREAUSER
  } elseif (($accion == "CREAUSER") and ($estado == 'none')) {  
    $result2 = pg_query ($db, "select username, email, firstname, lastname from mdl_user where username = '" . $nombre . "'") or die('Query failed: ' . pg_last_error());
    if (pg_num_rows($result2) <> 1)  {if (Error ($error_code, $error_msg, 100, 1) == 1) continue;}
    $row2 = pg_fetch_assoc($result2);
    $email = trim($row2['email']); $usuario = trim($row2['username']); $firstname = trim($row2['firstname']); $lastname = trim($row2['lastname']);  
    if (existsUserFromUserPrincipalName ($graph, $email, $error_code, $error_msg) == false) {      
      $password = getNewRandomPassword();
      xlog ("   -->Creating user $email ($usuario)");
      $userid = createUser ($graph, $usuario, $email, $firstname, $lastname, $password, $error_code, $error_msg);
      xlog ("   -->Enviando emails");
      if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Creada cuenta en Office365", mail_msgNuevoUsuarioEnvioCambioPassword ($firstname, $lastname, $usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}
      xsleep(2);
      if (sendEmail (NOREPLYEMAIL, $email, "", MAILPREFIX . "Contraseña", mail_msgNuevoUsuarioEnvioEsLaPassword ($usuario, $email, $password)) == false) {xlog ("Error enviando email a $email");}        
      xlog ("   -->Ajustando licencias a usuario");
      $pos = strpos($email, "@alumni.uv.es");
      if ($pos === false) {
        setLicensesToUser ($graph, ROL_KING_1, $email, $error_code, $error_msg);
      } else {
        setLicensesToUser ($graph, ROL_PAWN_1, $email, $error_code, $error_msg);
      }  
    } else {
      xlog ("   -->El usuario \"$email\" ya existía.");
    }    
    xlog ("   -->Ajustando valor action de tabla de Off365 a \"done\"");
    if (db_doSqlUpdateTablaOffice ($db, $id, '', 'done', '') <> 1) xlog ("Error updating id=$id");    

  //////////////////////////// NOTHING
  } else {
    xlog ("Nothing to do!");
  }

  // REFRESH TOKEN
  $end_time = microtime(true);
  $duration = $end_time - $start_time;
  if ($duration > TIME2REFRESH_TOKEN) {
    xlog ('Refreshing Auth token for Graph API...');
    $start_time = microtime(true);    
    $accessToken = createAccessToken ($error_code, $error_msg);        if ($error_code != 200) {Error ($error_code, $error_msg, 10);}
    $graph = createGraphObject ($accessToken, $eror_code, $error_msg); if ($error_code != 200) {Error ($error_code, $error_msg, 20);}
  }

}

endProgram ();

?>
