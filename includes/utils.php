<?php

//****************
//****  utils .php
//****************

require_once "/u/soft/UVPROG/includes/config.php";

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;

function vacio ($var) {
  if (!isset($var)) return true;
  if ($var == NULL) return true;
  if (empty($var)) return true;  
  if ($var == "") return true;
  return false;
}

function xlog ($st) {
  switch (LOG_TIPO) {
    case 0: syslog (LOG_NOTICE, $st); break;
    case 1: echo "$st\n"; break;
    case 2: syslog (LOG_NOTICE, $st); 
            echo "$st\n"; 
            break;
   }
}

function xdebug ($st) {
  if (DEBUG == 0) {return;}
  $st = "(DEBUG)--> $st";
  switch (DEBUG_TIPO) {
    case 0: syslog (LOG_NOTICE, $st); break;
    case 1: echo "$st\n"; break;
    case 2: syslog (LOG_NOTICE, $st); 
            echo "$st\n"; 
            break;
   }
}

function xbool ($b) {
  if ($b == true) {xdebug ("true");} else {xdebug ("false");}
}

function Error ($error_code, $error_msg, $exit_code, $modo = 0) {
  echo "   error --> $error_code - $error_msg\n";
      if ($modo == 1) {return 1;}                      // CONTINUE NEXT ITEM IN THE LOOP
  elseif ($modo == 0) {exit ($exit_code); return 0;}   // EXIT THE PROGRAM
  elseif ($modo == 2) {return 2;}                      // ONLY DISPLAY ERROR AND CONTINUE THE NEXT INSTRUCTION
}

function xsleep ($segundos) {
  // if (DEBUG) echo "(DEBUG)--> xsleep " . $segundos . "\n";    
  sleep ($segundos);
}

function loopFunction ($func, $graph, $group_id, &$error_code, &$error_msg) {
  $i = 0;
  do {
     ++$i;
     xdebug ("   --> Iteration $func $i");
     if ($func == "createTeamFromGroup") {
       $b = createTeamFromGroup ($graph, $group_id, $error_code, $error_msg);
       xlog ("      --> iteration $i");
       // if ($b == false) {xdebug ($error_code, $error_msg);}
     }
     xsleep (LOOP_SLEEP);
     } while (($b == false) and ($i < LOOP_MAXCOUNT));
  return $b;                                                                      
}

function initProgram ($progName) {
  openlog ($progName, LOG_PID, LOG_USER);
  xdebug ("BEGIN");
}

function endProgram() {
  xdebug ("END");
  closelog ();
  exit (0);
}

function sendEmail ($from, $to, $cc, $subject, $message) {
   $parameters = '';
   $headers = "MIME-Version: 1.0" . "\r\n";
   $headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";
   $headers .= 'From: ' . $from . "\r\n";
   $headers .= 'Cc: ' . $cc . "\r\n";
   $b = mail ($to, $subject, $message, $headers, $parameters);
   return $b;
}

function mail_msgUserAddedToNuevoTeam ($email, $nombre, $teamUrl) {
  $st  = "
          Estimat $email:
 
            Se li ha afegit al nou grup de Teams 

               $nombre    

            Pot entrar amb el client de Teams o des de l'adreça web

               $teamUrl

            Salutacions des del Servei d'Informàtica

          -----------------------------------------------------------------------

          Estimado $email:
              
            Se le ha añadido al nuevo grupo de Teams 
          
               $nombre    

            Puede entrar con el cliente de Teams o desde la dirección

               $teamUrl

            Saludos desde el Servei d'Informàtica                                       
  ";  
  return $st;
}

function mail_msgNuevoGrupoTeams ($emailcreator, $nombre, $teamUrl) {
  $st  = "
          Estimat $emailcreator:
 
            S'ha creat el seu grup de Teams 

               $nombre    

            Pot entrar amb el client de Teams o des de l'adreça web

               $teamUrl

            Salutacions des del Servei d'Informàtica

          -----------------------------------------------------------------------

          Estimado $emailcreator:
              
            Se ha creado su grupo de Teams 
          
               $nombre    

            Puede entrar con el cliente de Teams o desde la dirección

               $teamUrl

            Saludos desde el Servei d'Informàtica                                       
  ";  
  return $st;
}
 
function mail_msgNuevoUsuarioEnvioCambioPassword ($firstname, $lastname, $usuario, $email, $password) {
  $st  = "
          Estimat $firstname $lastname:

            S'ha creat el seu compte en Office365 de la Universitat de València.
            Pot entrar amb el client de Teams o des de l'adreça web

                 " . PORTALOFFICE365 . "

             amb l'usuari

                 $email

            La contrasenya se li enviarà en el següent missatge
             (i se l'obligarà a canviar-la en el següent login).

          Salutacions des del Servei d'Informàtica 

          -----------------------------------------------------------------------

          Estimado $firstname $lastname:
              
            Se ha creado su cuenta en Office365 de la Universitat de València.    
            Puede entrar con el cliente de Teams o desde la dirección                                                     
            
                 " . PORTALOFFICE365 . "
                 
            con el usuario                                                         
            
                 $email                                                               
                 
            La contraseña se le enviará en el siguiente mensaje                        
              (y se le obligará a cambiarla en el siguiente login).                  
                                                                                          
               Saludos desde el Servei d'Informàtica                                       
  ";  
  return $st;
}
 
function mail_msgNuevoUsuarioEnvioEsLaPassword ($usuario, $email, $password) {
  $st = "

     $password
  
  ";
  return $st;
}

function getNewRandomPassword () {
  $pw = "N" . substr(md5(rand()), 0, 8) . "_2020";
  return $pw;
}

function isGroupClass ($communitykey) {
  xdebug ("communitykey=$communitykey");  
  $pos = strpos ($communitykey, "comunidadc");
  if ($p === false) {
    $tipo = TIPO_DOCENCIA;                                             // c21c033a34886gA
    if (substr($communitykey, 7, 1) == "t") {$tipo = TIPO_TITULACION;} // c21c033t1406a1
  } else {
    $tipo = TIPO_COMUNIDAD;                                            // comunidadc067
  }
  return $tipo;  
}

function db_getCurrentNumpar ($db) {
  $sqlnumpar = "SELECT numpar FROM mdl_uv_parcur WHERE curso_acad = (SELECT MAX (curso_acad) FROM mdl_uv_parcur)";
  $xresult = pg_query ($db, $sqlnumpar) or die('Query failed: ' . pg_last_error());
  if (!$xresult) {return '';}
  // xdebug ("Total=" . pg_num_rows ($xresult));
  $row = pg_fetch_assoc($xresult);
  $numpar = $row['numpar'];  
  return $numpar;
}

function db_getCurrentYearFromCurrentNumpar ($db) {
  $sqlnumpar = "SELECT MAX (curso_acad) as year FROM mdl_uv_parcur";
  $xresult = pg_query ($db, $sqlnumpar) or die('Query failed: ' . pg_last_error());
  if (!$xresult) {return '';}
  // xdebug ("Total=" . pg_num_rows ($xresult));
  $row = pg_fetch_assoc($xresult);
  $year = $row['year'];  
  return $year;
}

function db_init (&$db) {
  try {
    xdebug ("DSN=" . DSN);
    // xlog ("DSN=" . DSN);    
    $db  = pg_pconnect(DSN) or die('Error connecting to database: ' . pg_last_error());
  } catch (Exception $e) {
    return false;
  }
  if (!$db) {return false;}
  return true;
}

function db_getSqlActions () {
  $sql = "SELECT id, idnumber, idoff365, nombre, descripcion, fechacreacion, estado, accion, email FROM " . TMDLUVO365 . " WHERE enabled = true ORDER BY id";
  return $sql;
}

//Cada curso de Moodle está identificado por el idnumber (llamado currutaca)
//EL IDNUMBER DE LAS COMUNIDADES EMPIEZA POR: comunidadcXXXX
//EL IDNUMBER DE LOS CURSOS TIENE EL SIGUIENTE FORMATO 
//0  3   7     13 16
// c15c009a34444gARsL01
// 
// CAACnnnammmmmGgg
//             --- C         fija
//             --- AA        curso
//             --- C            Tlugar
//             --- nnn          Lugar
//             --- a         Será una 'a' curso oficial, puede ser una t para titulacion
//             --- mmmmm        Modulo
//             --- G            fija
//             --- gg        Grupo
//TIPO:
// TIPO_DOCENCIA  c21c033a34886gA (asignaturas oficiales de la Universidad) 
//TIPO_TITULACION c21c033t1406a1   (titulaciones oficiales de la Universidad)
// TIPO_COMUNIDAD comunidadcXXXX  (grupos de trabajo y cursos no oficiales)
function db_parseaCurrutaca ($currutaca) {
  $cero   = substr ($currutaca, 0, 1);   $fija_1     = $cero;                    //C
  $uno    = substr ($currutaca, 0, 3);   $curso      = substr ($uno, 1, 2);      //15
  $dos    = substr ($currutaca, 3, 4);   $centro     = $dos;                     //c009
  $dos_1  = substr ($currutaca, 7, 1);   $tipo_com   = $dos_1;                   //a / t 
  if ($tipo_com == "a") {
    $tres   = substr ($currutaca, 7, 6);   $asignatura = substr ($tres, 1, 5);   //34444
    $cuatro = substr ($currutaca, 13, 3);  $grupo      = substr ($cuatro, 1, 2); //AR
    $cinco  = substr ($currutaca, 16, 4);  $subgrupo   = substr ($cinco, 1, 3);  //L01
  } elseif ($tipo_com == "t") {
    $tres   = substr ($currutaca, 7, 5);   $asignatura = substr ($tres, 1, 4);   //1406
    $cuatro = substr ($currutaca, 12, 3);  $grupo      = $cuatro;                //a1 / out / inc
    $subgrupo = "";
  }  
  $year   = "20" . $curso;                                                       //2015

  // c009
  $tlugar = strtoupper(substr ($currutaca, 3, 1)); //c --> C
  $lugar  = substr ($currutaca, 4, 3); //009

  $p = strpos ($currutaca, 'comunidadc');
  if ($p === false) {
    $tipo = TIPO_DOCENCIA;
    if ($tipo_com == "t") {$tipo = TIPO_TITULACION;}
  } else {
    $tipo = TIPO_COMUNIDAD;
  }

  $data = array ("currutaca"=>$currutaca, "curso"=>$curso, "centro"=>$centro, "asignatura"=>$asignatura, "grupo"=>$grupo, "subgrupo"=>$subgrupo, 
                 "year"=>$year, "tipo"=>$tipo, "tlugar"=>$tlugar, "lugar"=>$lugar);
  return $data;
} 

function db_getParamsActions ($row, &$id, &$idnumber, &$idoff365, &$nombre, &$descripcion, &$fechacreacion, &$estado, &$accion, &$emailcreator) {
  $id            = $row['id'];            xdebug ("id=$id");                       //
  $idnumber      = $row['idnumber'];      xdebug ("idnumber=$idnumber");           // currutaca
  $idoff365      = $row['idoff365'];      xdebug ("idoff365=$idoff365");           // ID de off365
  $nombre        = $row['nombre'];        xdebug ("nombre=$nombre");               //
  $descripcion   = $row['descripcion'];   xdebug ("descripcion=$descripcion");     $descripcion = ($descripcion == '') ? $nombre : $descripcion;
  $fechacreacion = $row['fechacreacion']; xdebug ("fechacreacion=$fechacreacion"); //
  $estado        = $row['estado'];        xdebug ("estado=$estado");               //
  $accion        = $row['accion'];        xdebug ("accion=$accion");               // CREADOCENCIA
  $emailcreator  = $row['email'];         xdebug ("emailcreator=$emailcreator");   //
}

function db_getSqlMiembrosGrupos ($communitykey, $numpar, $tipo_grupo, $rol, $asignatura, $grupo, $tlugar, $lugar) {
  xdebug ("db_getSqlMiembrosGrupos");

  //------------------------------------------------------
  $pruebas = 0;
  if ($pruebas == 1) {
    if (($rol == ROL_KING_1) or ($rol == ROL_KING_2)) {
      $sql = "SELECT email, usuario, firstname, lastname FROM " . TMDLUVO365_PRUEBAS . " WHERE rol = '" . ROL_KING_1 . "'"; 
    } elseif (($rol == ROL_PAWN_1) or ($rol == ROL_PAWN_2)) {
      $sql = "SELECT email, usuario, firstname, lastname FROM " . TMDLUVO365_PRUEBAS . " WHERE rol = '" . ROL_PAWN_1 . "'"; 
    }
    return $sql;
  }  
  //------------------------------------------------------
  $sql = "";
  if ($tipo_grupo == TIPO_DOCENCIA) {
    if ($rol == ROL_KING_1) {
				//Estas SQL se pueden cambiar por las del sistema de gestión académica

      $sql = "SELECT usr.email as email, usr.username as usuario, usr.firstname as firstname, usr.lastname as lastname
                   FROM mdl_course c
                   INNER JOIN mdl_context cx          ON C.id = cx.instanceid AND cx.contextlevel = '50'
                   INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
                   INNER JOIN mdl_role r              ON ra.roleid = r.id
                   INNER JOIN mdl_user usr            ON ra.userid = usr.id
                   WHERE C.idnumber like '" . $communitykey . "' AND (r.shortname='editingteacher' or r.shortname='manager') AND (usr.email  like '%uv.es' OR usr.email like '%valencia.edu') ORDER BY 1";
    } elseif ($rol == ROL_PAWN_1) {
		//Estas SQL se pueden cambiar por las del sistema de gestión académica
      $sql = "SELECT usr.email as email, usr.username as usuario, usr.firstname as firstname, usr.lastname as lastname
                   FROM mdl_course c
                   INNER JOIN mdl_context cx          ON C.id = cx.instanceid AND cx.contextlevel = '50'
                   INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
                   INNER JOIN mdl_role r              ON ra.roleid = r.id
                   INNER JOIN mdl_user usr            ON ra.userid = usr.id
                   WHERE C.idnumber like '" . $communitykey . "' AND (r.shortname='student' or r.shortname='member') AND (usr.email  like '%uv.es' OR usr.email like '%valencia.edu') ORDER BY 1";
    } 
  } elseif ($tipo_grupo == TIPO_TITULACION) {
    if ($rol == ROL_KING_1) {
				//Estas SQL se pueden cambiar por las del sistema de gestión académica
      $sql = "SELECT usr.email as email, usr.username as usuario, usr.firstname as firstname, usr.lastname as lastname
                   FROM mdl_course c
                   INNER JOIN mdl_context cx          ON C.id = cx.instanceid AND cx.contextlevel = '50'
                   INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
                   INNER JOIN mdl_role r              ON ra.roleid = r.id
                   INNER JOIN mdl_user usr            ON ra.userid = usr.id
                   WHERE C.idnumber like '" . $communitykey . "' AND (r.shortname='editingteacher' or r.shortname='manager') AND (usr.email  like '%uv.es' OR usr.email like '%valencia.edu') ORDER BY 1";
    } elseif ($rol == ROL_PAWN_1) {
				//Estas SQL se pueden cambiar por las del sistema de gestión académica
      $sql = "SELECT usr.email as email, usr.username as usuario, usr.firstname as firstname, usr.lastname as lastname
                   FROM mdl_course c
                   INNER JOIN mdl_context cx          ON C.id = cx.instanceid AND cx.contextlevel = '50'
                   INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
                   INNER JOIN mdl_role r              ON ra.roleid = r.id
                   INNER JOIN mdl_user usr            ON ra.userid = usr.id
                   WHERE C.idnumber like '" . $communitykey . "' AND (r.shortname='student' or r.shortname='member') AND (usr.email  like '%uv.es' OR usr.email like '%valencia.edu') ORDER BY 1";
    } 
  } elseif ($tipo_grupo == TIPO_COMUNIDAD) {
    if ($rol == ROL_KING_2) {
      $sql = "SELECT usr.email as email, usr.username as usuario, usr.firstname as firstname, usr.lastname as lastname
                   FROM mdl_course c
                   INNER JOIN mdl_context cx          ON C.id = cx.instanceid AND cx.contextlevel = '50'
                   INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
                   INNER JOIN mdl_role r              ON ra.roleid = r.id
                   INNER JOIN mdl_user usr            ON ra.userid = usr.id
                   WHERE C.idnumber like '" . $communitykey . "' AND (r.shortname='editingteacher' or r.shortname='manager') AND (usr.email  like '%uv.es' OR usr.email like '%valencia.edu') ORDER BY 1";
    } elseif ($rol == ROL_PAWN_2) {
      $sql = "SELECT usr.email as email, usr.username as usuario, usr.firstname as firstname, usr.lastname as lastname
                   FROM mdl_course c
                   INNER JOIN mdl_context cx          ON C.id = cx.instanceid AND cx.contextlevel = '50'
                   INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
                   INNER JOIN mdl_role r              ON ra.roleid = r.id
                   INNER JOIN mdl_user usr            ON ra.userid = usr.id
                   WHERE C.idnumber like '" . $communitykey . "' AND (r.shortname='student' or r.shortname='member') AND (usr.email  like '%uv.es' OR usr.email like '%valencia.edu') ORDER BY 1";
    } 
  }  
  return $sql;
}

function db_getSqlComunidadesToRefresh ($numpar, $long_year, $short_year) {
  xdebug ("db_getSqlComunidadesToRefresh");

  //Se insertan los idnumber de los cursos que se sabe que han tenido cambios en la matricula. Los scripts de gestion de matricula se encargan de incluir aqui el idnumber. Diariamente se actualizan los miembros de estos idnumbers
  $sql = "SELECT idnumber from mdl_uv_o365_refresh where fechacreacion > now() - interval '1 day'";
  // xdebug ($sql);
  return $sql;
}

function db_doSqlUpdateTablaOffice ($db, $id, $group_id, $accion, $teamUrl) {
  $sql = "UPDATE " . TMDLUVO365 . " SET idoff365='$group_id', estado='$accion', fechacreacion=now(), weburl='$teamUrl' WHERE id='$id'";
  // xdebug ($sql);
  $result2 = pg_query ($db, $sql);
  $rows_updated = pg_affected_rows($result2);          
  return $rows_updated;  
}
         
function createAccessToken (&$error_code, &$error_msg)  {
  xdebug ("createAccessToken");
  $error_code = '200'; $error_msg = 'Ok';
  $guzzle = new \GuzzleHttp\Client();
  $url_login = 'https://login.microsoftonline.com/' . TENANTID . '/oauth2/v2.0/token';
  try {
    $response = $guzzle->post($url_login,
      ['form_params' =>
           ['client_id'     => CLIENTID,
            'client_secret' => CLIENTSECRET,
            'scope'         => 'https://graph.microsoft.com/.default',
            'grant_type'    => 'client_credentials',
           ],
      ])->getBody()->getContents();
  } catch (Exception $e) {
    if ($e->hasResponse()) {
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];      
    }
  }
  if (isset($response) and vacio ($response) == false) { 
    return json_decode($response)->access_token;
  } else {  
    return '';
  }
}

function createGraphObject ($accessToken, &$error_code, &$error_msg) {
  xdebug ("createGraphObject");
  $error_code = '200'; $error_msg = 'Ok';    
  $graph = new Graph();
  try {
    $graph->setAccessToken ($accessToken);
    $graph->setBaseUrl ("https://graph.microsoft.com/");
    $graph->setApiVersion (API_VERSION);
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }     
  return $graph;
}

function includeGroupInAdministrativeUnit ($graph, $tipo, $curso, $group_id, &$error_code, &$error_msg) {
  xdebug ("includeGroupInAdministrativeUnit");
  $error_code = '200'; $error_msg = 'Ok';    
  $data = [
    "@odata.id" => "https://graph.microsoft.com/beta/groups/$group_id",
  ];
  // xdebug ($group_id);
  if (($tipo == TIPO_DOCENCIA) or ($tipo == TIPO_TITULACION)) {
    switch ($curso) {
      case 21: $UA = UA_AULAVIRTUAL_20_21; break;
      case 22: $UA = UA_AULAVIRTUAL_21_22; break;
      case 23: $UA = UA_AULAVIRTUAL_22_23; break;
      case 24: $UA = UA_AULAVIRTUAL_23_24; break;
      case 25: $UA = UA_AULAVIRTUAL_24_25; break;
    }
    $url_op = "/administrativeUnits/" . $UA . "/members/\$ref";
  } elseif ($tipo == TIPO_COMUNIDAD) {
    $url_op = "/administrativeUnits/" . UA_COMUNIDADES . "/members/\$ref";
  }
  // xdebug ("$url_op");
  try {
    $gresponse = $graph->createRequest ("POST", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody ($data)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
}

function getGroupIdFromDisplayName ($graph, $name, &$error_code, &$error_msg) {
  xdebug ("getGroupIdFromDisplayName");
  $res = array ();
  $error_code = '200'; $error_msg = 'Ok';    
  $url_op = "/groups?\$filter=displayName eq '" . $name . "'&\$select=displayName,id";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\Group::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $tot = count($gresponse);
    for ($i = 0; $i < $tot; ++$i) {
      xdebug ($gresponse[$i]->getProperties()['id'] . "-->" . $gresponse[$i]->getProperties()['displayName']);
      $id = $gresponse[$i]->getProperties()['id'];
      $res[$i] = $id;
    }  
  }
  return $res; 
}

function getGroupsIdsFromDisplayName ($graph, $name, &$error_code, &$error_msg) {
  xdebug ("getGroupsIdsFromDisplayName");
  $res = array ();
  $error_code = '200'; $error_msg = 'Ok';    
  $url_op = "/groups/?\$filter=startswith(displayName, '" . $name . "')&\$select=displayName,id";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\Group::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }  
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $tot = count($gresponse);
    for ($i = 0; $i < $tot; ++$i) {
      xdebug ($gresponse[$i]->getProperties()['id'] . "-->" . $gresponse[$i]->getProperties()['displayName']);
      $id = $gresponse[$i]->getProperties()['id'];
      $res[$i] = $id;
    }  
  } 
  return $res;
}

function getInfoGroupFromId ($graph, $id, &$error_code, &$error_msg) {
  xdebug ("getInfoGroupFromId");
  $res = array ();
  $error_code = '200'; $error_msg = 'Ok';    
  $url_op = "/groups/?\$filter=id eq '" . $id . "'&\$select=id,displayName,description,classification,createdDateTime,mail,mailNickname,mailEnabled,securityEnabled,visibility";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\Group::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }                          
  }  
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $res['id']                 = $gresponse[0]->getProperties()['id'];
    $res['displayName']        = $gresponse[0]->getProperties()['displayName'];
    $res['description']        = $gresponse[0]->getProperties()['description'];
    $res['classification']     = $gresponse[0]->getProperties()['classification'];
    $res['createdDateTime']    = $gresponse[0]->getProperties()['createdDateTime'];
    $res['mail']               = $gresponse[0]->getProperties()['mail'];    
    $res['mailNickname']       = $gresponse[0]->getProperties()['mailNickname'];
    $res['mailEnabled']        = $gresponse[0]->getProperties()['mailEnabled'];
    $res['securityEnabled']    = $gresponse[0]->getProperties()['securityEnabled'];
    $res['visibility']         = $gresponse[0]->getProperties()['visibility'];
  } 
  return $res;
}

function deleteGroupFromId ($graph, $id, &$error_code, &$error_msg) {
  xdebug ("deleteGroupFromId");
  $error_code = '200'; $error_msg = 'Ok';    
  $url_op = "/groups/" . $id;
  try {
    $gresponse = $graph->createRequest ("DELETE", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\Group::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }  
  return '';
}

// Se pueden insertar un maximo de 20 en cada llamada
function insertMembers20InGroup ($graph, $id, $data, &$error_code, &$error_msg) {
  xdebug ("insertMembers20InGroup");
  $error_code = '200'; $error_msg = 'Ok';
  $url_op = "/groups/" . $id;
  try {
    $gresponse = $graph->createRequest("PATCH", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\Team::class)
              ->execute();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
} 

function insertMemberInGroup ($graph, $group_id, $username, $rol, &$error_code, &$error_msg) {
  xdebug ("insertMemberInGroup");
  $error_code = '200'; $error_msg = 'Ok';
  $data = [
    "@odata.id" => "https://graph.microsoft.com/v1.0/users/". $username,
  ];  
  if (($rol == ROL_KING_1) or ($rol == ROL_KING_2)) {
    $url_op = "/groups/" . $group_id . "/owners/\$ref";
  } elseif (($rol == ROL_PAWN_1) or ($rol == ROL_PAWN_2)) {
    $url_op = "/groups/" . $group_id . "/members/\$ref";
  }
  try {
    $gresponse = $graph->createRequest("POST", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\Team::class)
              ->execute();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
} 

// GET https://graph.microsoft.com/v1.0/users/{id | userPrincipalName}?$select=displayName,givenName,postalCode
function existsUserFromUserPrincipalName ($graph, $email, &$error_code, &$error_msg) {
  xdebug ("existsUserFromUserPrincipalName");
  $res = array ();
  $error_code = '200'; $error_msg = 'Ok';    
  $url_op = "/users/" . $email . "?\$select=id";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\User::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
      return false;
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    // xdebug ($email . "-->" . $gresponse->getProperties()['id']);
    return true;
  } else {
    return false;
  }
}

function getUserFromUserPrincipalName ($graph, $email, &$error_code, &$error_msg) {
  xdebug ("getUserFromUserPrincipalName");
  $error_code = '200'; $error_msg = 'Ok';    
  $url_op = "/users/" . $email . "?\$select=id";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\User::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
      return "";
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    return $gresponse->getProperties()['id'];
  } else {
    return "";
  }
}
function getInfoUserFromPrincipalName ($graph, $email, &$error_code, &$error_msg) {
  xdebug ("getInfoUserFromPrincipalName");
  $res = array ();
  $error_code = '200'; $error_msg = 'Ok';
  $url_op = "/users/?\$filter=userPrincipalName eq '" . $email . "'&\$select=id,displayName,givenName,surname";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\User::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) {
    $res['id']                 = $gresponse[0]->getProperties()['id'];
    $res['displayName']        = $gresponse[0]->getProperties()['displayName'];
    $res['givenName']          = $gresponse[0]->getProperties()['givenName'];
    $res['surname']            = $gresponse[0]->getProperties()['surname'];
  } else {
  }
  return $res;
}

function deleteUserFromUserPrincipalName ($graph, $username, &$error_code, &$error_msg) {
  xdebug ("deleteUserFromUserPrincipalName");
  $error_code = '200'; $error_msg = 'Ok';
  $url_op = "/users/" . $username;
  try {
    $gresponse = $graph->createRequest ("DELETE", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\User::class)
              ->execute ();
  } catch (Exception $e) {
    var_dump ($e);
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }  
  return '';
}

function createUser ($graph, $username, $email, $firstname, $lastname, $password, &$error_code, &$error_msg) {
  xdebug ("createUser");
  $error_code = '200'; $error_msg = 'Ok';
  $data = [
    "accountEnabled"    => true,
    "displayName"       => $firstname . " " . $lastname, // displayName        string  The name to display in the address book for the user.
    "mail"              => $email,
    "givenName"         => $firstname,
    "surname"           => $lastname,    
    "mailNickname"      => $username,                    //$email, // mailNickname        string  The mail alias for the user.
    "userPrincipalName" => $email, 
    "passwordProfile"   => [
       "forceChangePasswordNextSignIn" => true,
       "password"                      => "$password",
    ],
    "businessPhones"    => [],    
    "jobTitle"          => null,
    "mobilePhone"       => null,
    "officeLocation"    => null,
    "preferredLanguage" => "es-ES",
    "usageLocation"     => "ES",
    // "onPremisesImmutableId" => "",  // string  Only needs to be specified when creating a new user account if you are using a federated domain for the user's userPrincipalName (UPN) property.
  ];
  $url_op = "/users";
  try {
    $gresponse = $graph->createRequest("POST", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\User::class)
              ->execute();
  } catch (Exception $e) {
    // var_dump ($e);
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $id = $gresponse->getProperties()['id'];
    return $id;
  } else {
    return '0';
  }
}

function setLicensesToUser ($graph, $rol, $userPrincipalName, &$error_code, &$error_msg) {
  xdebug ("setLicensesToUser");
  $error_code = '200'; $error_msg = 'Ok';

  if (($rol == ROL_KING_1) or ($rol == ROL_KING_2)) {
    $sku = SKU_PROFESOR_A1;
  } elseif (($rol == ROL_PAWN_1) or ($rol == ROL_PAWN_2)) {
    $sku = SKU_ALUMNO_A3;
  } else {
    return false;
  }

  $data = array (
                'addLicenses' => 
                   array (
                     0 => 
                       array (
                             'skuId' => $sku,
                             ),
                         ),
                'removeLicenses' => 
                  array (
                        ),
                );

  $url_op = "/users/" . $userPrincipalName . "/assignLicense";
  xdebug ($url_op);
  try {
    $gresponse = $graph->createRequest("POST", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\User::class)
              ->execute();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
      return false;
    }
  }
  return true;
} 

function createGroupWithMembers ($graph, $data, &$error_code, &$error_msg) {
  xdebug ("createGroupWithMembers");
  $error_code = '200'; $error_msg = 'Ok';
  $url_op = "/groups";
  try {
    $gresponse = $graph->createRequest("POST", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\Group::class)
              ->execute();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $group_id = $gresponse->getProperties()['id'];
    return $group_id;
  } else {
    return '0';
  }
}

function existsGroupFromDisplayName ($graph, $displayName, &$error_code, &$error_msg) {
  xdebug ("existsGroupFromDisplayName");
  $res = array ();
  $error_code = '200'; $error_msg = 'Ok';    
  $url_op = "/groups?\$filter=displayName eq '" . $displayName . "'&\$select=displayName,id";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
               ->addHeaders (array("Content-Type" => "application/json"))
               ->setTimeout(GRAPH_TIMEOUT)
               ->setReturnType(Model\Group::class)
               ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $tot = count($gresponse);
    for ($i = 0; $i < $tot; ++$i) {
      xdebug ($gresponse[$i]->getProperties()['id'] . "-->" . $gresponse[$i]->getProperties()['displayName']);
      $id = $gresponse[$i]->getProperties()['id'];
      $res[$i] = $id;
    }  
  }
  return $res; 
}

function getTeamUrl ($graph, $group_id, &$error_code, &$error_msg) {
  xdebug ("getTeamUrl");
  $res = "";
  $error_code = '200'; $error_msg = 'Ok';
  $url_op = "/groups/" . $group_id . "/team/?\$select=webUrl";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
              ->addHeaders (array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\Group::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) {
    $webUrl = $gresponse->getProperties()['webUrl'];        
    // xdebug ($group_id . "-->" . $webUrl);
  }
  return $webUrl;
}

// https://github.com/microsoftgraph/microsoft-graph-docs/blob/master/concepts/teams-create-group-and-team.md
function createGroupEmpty ($graph, $nombre, $descripcion, $idnumber, &$error_code, &$error_msg) {
  xdebug ("createGroupEmpty");
  $data = [
    "displayName"        => $nombre,
    "description"        => $descripcion,
    "groupTypes"         => ["Unified"],
    "visibility"         => "Private",
    "securityEnabled"    => false,
    "mailEnabled"        => true,
    "mailNickname"       => $idnumber,
    "preferredLanguage"  => null,
    "owners@odata.bind"  => [
                            "https://graph.microsoft.com/v1.0/users/" . OFF365ADMIN,
                            ],
  ];
  $error_code = '200'; $error_msg = 'Ok';
  $url_op = "/groups";
  try {
    $gresponse = $graph->createRequest("POST", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\Group::class)
              ->execute();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $group_id = $gresponse->getProperties()['id'];
    return $group_id;
  } else {
    return '0';
  }
}

function createTeamFromGroup ($graph, $group_id, &$error_code, &$error_msg) {
  xdebug ("createTeamFromGroup");
  $b = true;
  $error_code = '200'; $error_msg = 'Ok';
  $data = [
       "template@odata.bind" => "https://graph.microsoft.com/beta/teamsTemplates('" . TEAM_TEMPLATE_STA . "')",  
       "group@odata.bind"    => "https://graph.microsoft.com/beta/groups('$group_id')",
  ];
  $url_op = "/teams";
  try {
    $gresponse = $graph->createRequest("POST", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\Team::class)
              ->execute();
  } catch (Exception $e) {
    // var_dump ($e);
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
      $b = false;
    }    
  }
  return $b;
} 

// DELETE https://graph.microsoft.com/v1.0/groups/{id}/owners/{id}/$ref
//        https://graph.microsoft.com/v1.0/groups/{id}/members/{id}/$ref
function deleteMemberInGroup ($graph, $group_id, $email, $rol, &$error_code, &$error_msg) {
  xdebug ("deleteMemberInGroup");

  $user_id = getUserFromUserPrincipalName ($graph, $email, $error_code, $error_msg);
  if ($user_id == "") return false;

  $error_code = '200'; $error_msg = 'Ok';
  if (($rol == ROL_KING_1) or ($rol == ROL_KING_2)) {
    $url_op = "/groups/" . $group_id . "/owners/" . $user_id . "/\$ref";
  } elseif (($rol == ROL_PAWN_1) or ($rol == ROL_PAWN_2)) {
    $url_op = "/groups/" . $group_id . "/members/" . $user_id . "/\$ref";
  }
  try {
    $gresponse = $graph->createRequest("DELETE", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\Team::class)
              ->execute();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      var_dump ($e);
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
      return false;
    }
  }
  return true;
} 

function getMailAllMembersGroup ($graph, $group_id, &$error_code, &$error_msg) {
  // https://graph.microsoft.com/beta/groups/5a8afbef-7471-45cd-a464-6ed4b59101ad/members?$count=true&$select=mail
  xdebug ("getMailAllMembersGroup");
  $res = array ();
  $error_code = '200'; $error_msg = 'Ok';    
  $url_op = "/groups/$group_id/members?\$count=true&\$select=mail";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
               ->addHeaders (array("Content-Type" => "application/json"))
               ->setTimeout(GRAPH_TIMEOUT)
               ->setReturnType(Model\Group::class)
               ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  $tot = 0;
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $tot = count($gresponse);
    for ($i = 0; $i < $tot; ++$i) {
      xdebug ($gresponse[$i]->getProperties()['mail']);
      $res[$i] = $gresponse[$i]->getProperties()['mail'];
    }  
  }

  $url_op = "/groups/$group_id/owners?\$count=true&\$select=mail";
  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
               ->addHeaders (array("Content-Type" => "application/json"))
               ->setTimeout(GRAPH_TIMEOUT)
               ->setReturnType(Model\Group::class)
               ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $tot2 = count($gresponse);
    for ($j = 0; $j < $tot2; ++$j) {
      xdebug ($gresponse[$j]->getProperties()['mail']);
      $res[$tot + $j] = $gresponse[$j]->getProperties()['mail'];
    }  
  }

  return $res; 
}

function resetUserPassword ($graph, $userPrincipalName, $newpassword, &$error_code, &$error_msg) {
  xdebug ("resetUserPassword");
  $data = [
    "passwordProfile" => [
       "forceChangePasswordNextSignIn" => true, 
       "password"                      => $newpassword,
       ],
  ];
  $error_code = '200'; $error_msg = 'Ok';
  $url_op = "/users/" . $userPrincipalName;
  try {
    $gresponse = $graph->createRequest("PATCH", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\User::class)
              ->execute();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
      return false;
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) {
    return true;
  } else {
    return false;
  }
}

function resetUserPassword_NOVA ($graph, $userPrincipalName, $newpassword, &$error_code, &$error_msg) {
  xdebug ("resetUserPassword");
  $data = [
    "newPassword"        => $newpassword,
  ];
  $error_code = '200'; $error_msg = 'Ok';
  // POST https://graph.microsoft.com/beta/users/{userPrincipalName}/authentication/passwordMethods/{id}/resetPassword
  $url_op = "/users/" . $userPrincipalName . "/authentication/passwordMethods/" . ID_PASSWORD_METHOD . "/resetPassword";
  try {
    $gresponse = $graph->createRequest("POST", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\User::class)
              ->execute();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
      return false;
    }
  }
  if (isset($gresponse) and vacio ($gresponse) == false) {
    return true;
  } else {
    return false;
  }
}

function getUsersWithNoName ($graph, $error_code, &$error_msg) {
  xdebug ("getNoNameUsers");
  $res = array ();
  $error_code = '200'; $error_msg = 'Ok';    
  
  //Todos los usuarios  
  //$url_op = "/users?\$count=true&\$select=userPrincipalName&\$top=999&\$orderby=userPrincipalName asc&\$filter=surname eq null or givenName eq null";  
  
  // @alumni.uv.es
  //$url_op = "/users?\$count=true&\$select=userPrincipalName&\$top=999&\$orderby=userPrincipalName asc&\$filter=endsWith(mail,'@alumni.uv.es') and (surname eq null or givenName eq null)";  
  
  // @uv.es
  //$url_op = "/users?\$count=true&\$select=userPrincipalName&\$top=999&\$orderby=userPrincipalName asc&\$filter=endsWith(mail,'@uv.es') and (surname eq null or givenName eq null)";  
  
  // @ext.uv.es
  $url_op = "/users?\$count=true&\$select=userPrincipalName&\$top=9&\$orderby=userPrincipalName asc&\$filter=endsWith(mail,'ext.uv.es') and (surname eq null or givenName eq null)";  

  try {
    $gresponse = $graph->createRequest ("GET", $url_op)
              ->addHeaders (array("Content-Type" => "application/json", "ConsistencyLevel" => "eventual"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->setReturnType(Model\User::class)
              ->execute ();
  } catch (Exception $e) {
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }                          
  }  
  if (isset($gresponse) and vacio ($gresponse) == false) { 
    $tot = count($gresponse);
    //xlog ("Total en MS365=$tot");
    for ($i = 0; $i < $tot; ++$i) {
      $userPrincipalName = $gresponse[$i]->getProperties()['userPrincipalName'];
      $res[$i] = $userPrincipalName;
    }
    return $res;
  } else {
  }
  return $res;
}

function setNameToUsersWithNoName ($graph, $email, $firstname, $lastname, &$error_code, &$error_msg) {
  xdebug ("setNameToUsersWithNoName");
  $error_code = '200'; $error_msg = 'Ok';
  $data = [
    //"mail"              => $email,
    "givenName"         => $firstname,
    "surname"           => $lastname,    
    "userPrincipalName" => $email    
  ];
  $url_op = "/users/" . $email;
  try {
    $gresponse = $graph->createRequest("PATCH", $url_op)
              ->addHeaders(array("Content-Type" => "application/json"))
              ->setTimeout(GRAPH_TIMEOUT)
              ->attachBody($data)
              ->setReturnType(Model\User::class)
              ->execute();
  } catch (Exception $e) {
    // var_dump ($e);
    if ($e->hasResponse()) {    
      $contenidos = $e->getResponse()->getBody()->getContents();
      $k = json_decode ($contenidos, true);
      $error_code = $k['error']['code'];
      $error_msg  = $k['error']['message'];
    }
  }
}

?>
