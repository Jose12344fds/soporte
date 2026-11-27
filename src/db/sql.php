<?php
/**
 * Funciones SQL del Sistema
 * Este archivo es cargado por includes/load.php
 * NO incluir load.php aquí para evitar loops
 */

/*--------------------------------------------------------------*/
/* Function for find all database table rows by table name
/*--------------------------------------------------------------*/
function find_all($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM ".$db->escape($table));
   }
}



function find_all_EXT($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM {$db->escape($table)} WHERE DescripcionPro='EXT'");
   }
}
function find_all_PROIMPOR($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM {$db->escape($table)} ");
   }
}
function find_all_INY($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM {$db->escape($table)} WHERE DescripcionPro='INY'");
   }
}
function find_all1($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM {$db->escape($table)} u INNER JOIN celulares m ON m.IdTelefo = u.IdTelefo WHERE m.IdCelu IN (SELECT MAX(IdCelu) FROM celulares GROUP BY `IdTelefo`) ORDER BY `m`.`IdTelefo`  DESC");
   }
}
function find_all11($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM {$db->escape($table)} u ORDER BY u.IdTelefo  DESC");
   }
}
function find_all_LicenciasOffice($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM {$db->escape($table)} WHERE Tipo_Licencias='Office' AND Utilizado='LIBRE' ");
   }
}
function find_all_LicenciasWindows($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM {$db->escape($table)} WHERE Tipo_Licencias='Windows' AND Utilizado='LIBRE' ");
   }
}

function find_all_LicenciasOfficedit($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM {$db->escape($table)} WHERE Tipo_Licencias='Office' AND Utilizado='LIBRE'  ");
   }
}

/*--------------------------------------------------------------*/
/* Function for Perform queries
/*--------------------------------------------------------------*/
function find_by_sql($sql)
{
  global $db;
  $result = $db->query($sql);
  $result_set = $db->while_loop($result);
 return $result_set;
}

/*--------------------------------------------------------------*/
/* Function for Count Variables (OT Padre Counter)
/* Consulta: SELECT COUNT(*) as NUM FROM otpadre
/*--------------------------------------------------------------*/
function CountVariabel()
{
  global $db;
  // Busca contar el número máximo de variables/OT padre para generar nuevo ID
  $sql = "SELECT COUNT(*) as NUM FROM otpadre LIMIT 1";
  $result = $db->query($sql);
  if ($result && $row = $db->fetch_assoc($result)) {
    // Retorna array con formato: array(0 => array('NUM' => count))
    return array(array('NUM' => (int)$row['NUM'] + 1));
  }
  return array(array('NUM' => 1));
}

  function find_by_Correo($table, $id){
    global $db;
    $id = (int)$id;
    $correos = [];
      if (tableExists($table)) {
        $sql = $db->query("SELECT correo FROM {$db->escape($table)} WHERE EnviarCorreo='{$db->escape($id)}'");
        while ($row = $db->fetch_assoc($sql)) {
            $correos[] = $row['correo'];
        }  }
    return $correos; // Devuelve solo los correos
}
/*--------------------------------------------------------------*/
/*  Function for Find data from table by id
/*--------------------------------------------------------------*/
function find_by_id($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE id='{$db->escape($id)}' LIMIT 1");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}

function find_allq($table) {
   global $db;
   
   if (tableExists($table)) {
      // Obtener el año actual y el año anterior
      $current_year = date("Y");
      $previous_year = $current_year - 1;

      // Modificar la consulta para obtener los registros de ambos años
      return find_by_sql("SELECT YEAR(fecha) AS anio FROM ".$db->escape($table)." 
                          WHERE YEAR(fecha) IN ($current_year, $previous_year) 
                          GROUP BY YEAR(fecha);");
   }
}

function find_by_manteusu($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE id='{$db->escape($id)}' LIMIT 1");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id1($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE IdInci='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}

function find_by_id2($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE IdCelu='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id3($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE IdMaquina='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id3LICE($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE IdLicencias='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id4($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT c.IdAsMaq,c.id,c.IdMaquina,c.Fecha,c.Estado,c.Comentarios,u.name,m.Host FROM {$db->escape($table)} c LEFT JOIN users u ON u.id = c.id LEFT JOIN maquina m ON m.IdMaquina = c.IdMaquina WHERE c.IdAsMaq='{$db->escape($id)}'");
       
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id5($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} p
 INNER JOIN users c ON p.Usuario = c.id
INNER JOIN maquina m ON  p.Equipo = m.IdMaquina WHERE IdMante='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id6($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE name LIKE  '%{$db->escape($id)}%' ");
        

          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}

function find_by_id7($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} p
 INNER JOIN users c ON p.Usuario = c.id
INNER JOIN maquina m ON  p.Equipo = m.IdMaquina WHERE p.IdMante='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}


function find_by_id8($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE Idtelefo='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}

function find_by_id9($table,$id)
{
  global $db;
  $id = (string)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE   ID_Usuariosf08='{$db->escape($id)}'");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id10($table)
{
  global $db;

    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} ORDER BY IdProcTer DESC LIMIT 1");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}

function find_by_id11($table)
{
  global $db;

    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} T0 INNER JOIN producto_pro T2 ON T2.`Id_Producto`=T0.`Id_Producto` ORDER BY IdAcondicionado DESC LIMIT 1");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id12($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE IdProInyec='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id13($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE IdUsuSap='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id14($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE IdAcondicionado='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id15($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE Id_PtApro='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
function find_by_id16($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE Id_Producto='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}

function find_by_id18($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE IdImportaciones='{$db->escape($id)}' ");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
/*--------------------------------------------------------------*/
/* Function for Delete data from table by id
/*--------------------------------------------------------------*/
function delete_by_id($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE id=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}
/*--------------------------------------------------------------*/
function delete_by_PROTERMINADO($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "UPDATE  ".$db->escape($table);
    $sql .= " SET estado='0' ";
    $sql .= " WHERE IdProcTer=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}
function delete_by_PROTERMINADO_APROBADO($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE Id_PtApro=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}
function delete_by_PROTERMINADO_ANALISIS($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE IdProcTer=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}

function delete_by_INYECCION($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE IdProInyec=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}
function delete_by_EXTRUSION($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE IdProExt=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}
function delete_by_ACONDICIONADO($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE IdAcondicionado=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}

function delete_by_IMPORTACIONES($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE IdImportaciones=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}


/* Function for Count id  By table name
/*--------------------------------------------------------------*/

function fechas($table){
  global $db;
  if(tableExists($table))
  {
    $sql    = "SELECT Estado AS fecha1 FROM ".$db->escape($table);
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
}

function count_by_id($table){
  global $db;
  if(tableExists($table))
  {
    $sql    = "SELECT COUNT(id) AS total FROM ".$db->escape($table);
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
}
function count_by_usuarios_Concelular($table){
  global $db;
  if(tableExists($table))
  {                              
  $sql  =" SELECT COUNT(id) AS total ";
    $sql  .=" FROM {$db->escape($table)} p";
    $sql  .=" LEFT JOIN users u ON u.id = p.Usuario";

      $sql  .=" LEFT JOIN chip ch ON ch.InChip = p.IdChip";
    $sql  .=" LEFT JOIN telefono m ON m.IdTelefo = p.IdTelefo";
    $sql  .=" WHERE p.EstUso=2";

    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
}
function count_by_usuarios_Siscelular($table){
  global $db;
  if(tableExists($table))
  {                              

    
      $sql  =" SELECT COUNT(*) AS total ";
    $sql  .=" from {$db->escape($table)} p";
    $sql  .="  LEFT JOIN celulares m ON m.IdTelefo = p.IdTelefo";

      $sql  .=" LEFT JOIN chip ch ON ch.InChip = m.IdChip";
    $sql  .=" LEFT JOIN users u ON u.id = m.Usuario";
    $sql  .=" where u.name='LIBRE' ";
    
    

    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
}
function count_by_id_incidencias($table){
  global $db;
  if(tableExists($table))
  {
    $sql    = "SELECT COUNT(IdInci) AS total FROM ".$db->escape($table);
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
}
function count_by_inci_usu($table){
  global $db;
  if(tableExists($table))
  {
    $sql    = "SELECT NomUsu, COUNT(*) AS MAS_INCIDENCIAS FROM ".$db->escape($table);
     $sql .= " GROUP BY NomUsu";
       $sql .= " HAVING COUNT(*) > 1";
        $sql .= " ORDER BY MAS_INCIDENCIAS DESC";
         $sql .= " LIMIT 1";
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
     

     
     
  }
}


function count_by_inci_usu1($table){
  global $db;
  if(tableExists($table))
  {
 $sql = "SELECT Fecha, COUNT(*)  as usu FROM   ".$db->escape($table);
 $sql .= " GROUP BY Fecha";

     
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
   }
}

function count_by_inci_usu2($table){
  global $db;
  if(tableExists($table))
  {
 $sql = "SELECT COUNT(NomUsu) AS cantidad FROM  ".$db->escape($table);
  $sql .= " GROUP BY NomUsu";




     
     
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
     
  }
}
/*--------------------------------------------------------------*/
/* Determine if database table exists
/*--------------------------------------------------------------*/
function tableExists($table){
  global $db;
  $table_exit = $db->query('SHOW TABLES FROM '.DB_NAME.' LIKE "'.$db->escape($table).'"');
      if($table_exit) {
        if($db->num_rows($table_exit) > 0)
              return true;
         else
              return false;
      }
  }
 /*--------------------------------------------------------------*/
 /* Login with the data provided in $_POST,
 /* coming from the login form.
/*--------------------------------------------------------------*/
  function user_password_matches($password, $stored_hash) {
    $stored_hash = (string)$stored_hash;
    $info = password_get_info($stored_hash);
    if (($info['algoName'] ?? 'unknown') !== 'unknown') {
      return password_verify((string)$password, $stored_hash);
    }
    return strlen($stored_hash) === 40 && hash_equals($stored_hash, sha1((string)$password));
  }

  function user_password_upgrade_if_needed($user_id, $password, $stored_hash) {
    global $db;
    $info = password_get_info((string)$stored_hash);
    if (($info['algoName'] ?? 'unknown') === 'unknown' || password_needs_rehash((string)$stored_hash, PASSWORD_DEFAULT)) {
      $new_hash = password_hash((string)$password, PASSWORD_DEFAULT);
      $db->query("UPDATE users SET password='".$db->escape($new_hash)."' WHERE id=".(int)$user_id." LIMIT 1");
    }
  }

  function authenticate($username='', $password='') {
    global $db;
    $username = $db->escape($username);
    $sql  = sprintf("SELECT id,username,password,user_level,status,estado FROM users WHERE username ='%s' LIMIT 1", $username);
    $result = $db->query($sql);
    if($db->num_rows($result)){
      $user = $db->fetch_assoc($result);
      if(user_password_matches($password, $user['password'])){
        // Verificar que el usuario esté activo (status=1 y estado=1)
        if($user['status'] != 1 || $user['estado'] != 1){
          return false; // Usuario inactivo o deshabilitado
        }
        user_password_upgrade_if_needed((int)$user['id'], $password, $user['password']);
        return $user['id'];
      }
    }
   return false;
  }
  /*--------------------------------------------------------------*/
  /* Login with the data provided in $_POST,
  /* coming from the login_v2.php form.
  /* If you used this method then remove authenticate function.
 /*--------------------------------------------------------------*/
   function authenticate_v2($username='', $password='') {
     global $db;
     $username = $db->escape($username);
     $sql  = sprintf("SELECT id,username,password,user_level,status,estado FROM users WHERE username ='%s' LIMIT 1", $username);
     $result = $db->query($sql);
     if($db->num_rows($result)){
       $user = $db->fetch_assoc($result);
       if(user_password_matches($password, $user['password'])){
         // Verificar que el usuario esté activo (status=1 y estado=1)
         if($user['status'] != 1 || $user['estado'] != 1){
           return false; // Usuario inactivo o deshabilitado
         }
         user_password_upgrade_if_needed((int)$user['id'], $password, $user['password']);
         return $user;
       }
     }
    return false;
   }


  /*--------------------------------------------------------------*/
  /* Find current log in user by session id
  /*--------------------------------------------------------------*/
  function current_user(){
      static $current_user;
      global $db;
      if(!$current_user){
         if(isset($_SESSION['user_id'])):
             $user_id = intval($_SESSION['user_id']);
             $current_user = find_by_id('users',$user_id);
        endif;
      }
    return $current_user;
  }
  /*--------------------------------------------------------------*/
  /* Find all user by
  /* Joining users table and user gropus table
  /*--------------------------------------------------------------*/
  function ensure_users_tipo_registro(){
      global $db;
      $check = $db->query("SHOW COLUMNS FROM users LIKE 'tipo_registro'");
      if ($db->num_rows($check) === 0) {
          $db->query("ALTER TABLE users ADD COLUMN tipo_registro VARCHAR(10) NOT NULL DEFAULT 'USUARIO' AFTER status");
      }
      $checkTi = $db->query("SHOW COLUMNS FROM users LIKE 'es_personal_ti'");
      if ($db->num_rows($checkTi) === 0) {
          $db->query("ALTER TABLE users ADD COLUMN es_personal_ti TINYINT(1) NOT NULL DEFAULT 0 AFTER tipo_registro");
      }
  }

  function find_all_user(){
      global $db;
      ensure_users_tipo_registro();
      $results = array();
      $sql = "SELECT u.id,u.name,u.username,u.user_level,u.status,u.tipo_registro,u.es_personal_ti,u.last_login,u.correo,u.area,u.telefono,";
      $sql .="g.group_name ";
      $sql .="FROM users u ";
      $sql .="LEFT JOIN user_groups g ";
      $sql .="ON g.group_level=u.user_level ORDER BY u.name ASC";
      $result = find_by_sql($sql);
      return $result;
  }
  function find_all_user1(){
      global $db;
      $results = array();
      $sql = "SELECT u.id,u.name,u.username,u.user_level,u.status,u.last_login,u.correo,u.teams,u.telefono,";
      $sql .="g.group_name ";
      $sql .="FROM users u ";
      $sql .="LEFT JOIN user_groups g ";
      $sql .="ON g.group_level=u.user_level ";
         $sql .="  WHERE u.status ='1' ";
         $sql .="  ORDER BY u.name ASC";
      $result = find_by_sql($sql);
      return $result;
  }


 function updateLastLogIn($user_id)
	{
		global $db;
    $date = make_date(ini_set('date.timezone','America/Lima'));
    $sql = "UPDATE users SET last_login='{$date}' WHERE id ='{$user_id}' LIMIT 1";
    $result = $db->query($sql);
    return ($result && $db->affected_rows() === 1 ? true : false);
	}

  /*--------------------------------------------------------------*/
  /* Find all Group name
  /*--------------------------------------------------------------*/
  function find_by_groupName($val)
  {
    global $db;
    $sql = "SELECT group_name FROM user_groups WHERE group_name = '{$db->escape($val)}' LIMIT 1 ";
    $result = $db->query($sql);
    return($db->num_rows($result) === 0 ? true : false);
  }
  /*--------------------------------------------------------------*/
  /* Find group level
  /*--------------------------------------------------------------*/
  function find_by_groupLevel($level)
  {
    global $db;
    $sql = "SELECT group_level FROM user_groups WHERE group_level = '{$db->escape($level)}' LIMIT 1 ";
    $result = $db->query($sql);
    return($db->num_rows($result) === 0 ? true : false);
  }
  /*--------------------------------------------------------------*/
  /* Function for cheaking which user level has access to page
  /*--------------------------------------------------------------*/
    function page_require_level($require_level){
     global $session;
     $current_user = current_user();
     $login_level = find_by_groupLevel($current_user['user_level']);
     //if user not login
     if (!$session->isUserLoggedIn(true)):
            $session->msg('d','Por favor Iniciar sesión...');
            redirect('index.php', false);
      //if Group status Deactive
     elseif($login_level['group_status'] = '0'):
           $session->msg('d','Este nivel de usaurio esta inactivo!');
           redirect('home.php',false);
      //cheackin log in User level and Require level is Less than or equal to
     elseif($current_user['user_level'] <= (int)$require_level):
              return true;
      else:
            $session->msg("d", "¡Lo siento!  no tienes permiso para ver la página.");
            redirect('home.php', false);
        endif;

     }
   
   

   function celular(){
     global $db;
     $sql  =" SELECT c.IdCelu,u.name,t.Imei,ch.Num,ch.Plan,ch.GB,c.FechaEntrega,c.Usuario,c.IdChip,c.IdTelefo,t.Modelo,t.Num_guia,c.EstUso,u.area";
    $sql  .=" FROM celulares c";
    $sql  .=" LEFT JOIN users u ON u.id = c.Usuario";
    $sql  .=" LEFT JOIN chip ch ON ch.InChip = c.IdChip";
     $sql  .=" LEFT JOIN telefono t ON t.IdTelefo = c.IdTelefo";
    return find_by_sql($sql);

   }
   function asig_maquina(){
     global $db;
     $sql  =" SELECT c.IdAsMaq,u.name,ch.Host,c.Fecha,c.Estado";
    $sql  .=" FROM asig_maquina c";
    $sql  .=" LEFT JOIN users u ON u.id = c.id";
    $sql  .=" LEFT JOIN maquina ch ON ch.IdMaquina = c.IdMaquina";

    return find_by_sql($sql);

   }

   function ensure_maquina_ubicacion_schema(){
    global $db;
    static $ready = false;
    if ($ready) return;

    $columnResult = $db->query("SHOW COLUMNS FROM maquina LIKE 'Ubicacion'");
    if ($db->num_rows($columnResult) === 0) {
      // Los equipos existentes pertenecen a la empresa mientras no se indique
      // expresamente otra ubicacion.
      $db->query("ALTER TABLE maquina
                  ADD COLUMN Ubicacion VARCHAR(20) NOT NULL DEFAULT 'EMPRESA'
                  AFTER EstadoMaquina");
    }

    $codigoContabilidadResult = $db->query("SHOW COLUMNS FROM maquina LIKE 'CodigoContabilidad'");
    if ($db->num_rows($codigoContabilidadResult) === 0) {
      // Se conserva como texto para admitir ceros iniciales y códigos mixtos.
      $db->query("ALTER TABLE maquina
                  ADD COLUMN CodigoContabilidad VARCHAR(100) NULL DEFAULT NULL
                  AFTER Host");
    }

    $categoriaResult = $db->query("SHOW COLUMNS FROM maquina LIKE 'CategoriaEquipo'");
    if ($db->num_rows($categoriaResult) === 0) {
      $db->query("ALTER TABLE maquina
                  ADD COLUMN CategoriaEquipo VARCHAR(30) NULL DEFAULT NULL
                  AFTER Tipo_Activo");
    }

    $db->query("CREATE TABLE IF NOT EXISTS tipos_activo (
                  IdTipoActivo INT NOT NULL AUTO_INCREMENT,
                  Codigo VARCHAR(30) NOT NULL,
                  Nombre VARCHAR(60) NOT NULL,
                  CategoriaPredeterminada VARCHAR(30) NOT NULL,
                  Activo TINYINT(1) NOT NULL DEFAULT 1,
                  Orden INT NOT NULL DEFAULT 0,
                  PRIMARY KEY (IdTipoActivo),
                  UNIQUE KEY uq_tipos_activo_codigo (Codigo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("INSERT IGNORE INTO tipos_activo (Codigo, Nombre, CategoriaPredeterminada, Activo, Orden) VALUES
                  ('PC','PC','COMPUTADORAS',1,10),
                  ('LAPTOP','Laptop','COMPUTADORAS',1,20),
                  ('SERVIDOR','Servidor','INFRAESTRUCTURA',1,30),
                  ('IMPRESORA','Impresora','PERIFERICOS',1,40),
                  ('SWITCH','Switch','INFRAESTRUCTURA',1,50),
                  ('FIREWALL','Firewall','INFRAESTRUCTURA',1,60),
                  ('TABLET','Tablet','COMPUTADORAS',1,70),
                  ('OTRO','Otro','PERIFERICOS',1,80)");

    $tipoIdResult = $db->query("SHOW COLUMNS FROM maquina LIKE 'IdTipoActivo'");
    if ($db->num_rows($tipoIdResult) === 0) {
      $db->query("ALTER TABLE maquina ADD COLUMN IdTipoActivo INT NULL AFTER Tipo_Activo");
      $db->query("ALTER TABLE maquina ADD INDEX idx_maquina_tipo_activo (IdTipoActivo)");
    }

    // Relacionar inventario histórico usando el código que ya estaba guardado.
    $db->query("UPDATE maquina m
                INNER JOIN tipos_activo t ON t.Codigo = UPPER(TRIM(m.Tipo_Activo))
                SET m.IdTipoActivo = t.IdTipoActivo,
                    m.CategoriaEquipo = CASE
                      WHEN m.CategoriaEquipo IS NULL OR TRIM(m.CategoriaEquipo) = ''
                      THEN t.CategoriaPredeterminada ELSE m.CategoriaEquipo END
                WHERE m.IdTipoActivo IS NULL OR m.IdTipoActivo = 0");

    $db->query("UPDATE maquina m
                INNER JOIN tipos_activo t ON t.IdTipoActivo = m.IdTipoActivo
                SET m.CategoriaEquipo = t.CategoriaPredeterminada
                WHERE m.CategoriaEquipo IS NULL OR TRIM(m.CategoriaEquipo) = ''");

    // Clasificación inicial segura: PC y LAPTOP siempre pertenecen a COMPUTADORAS.
    $db->query("UPDATE maquina
                SET CategoriaEquipo = 'COMPUTADORAS'
                WHERE UPPER(TRIM(Tipo_Activo)) IN ('PC', 'LAPTOP')
                  AND (CategoriaEquipo IS NULL OR TRIM(CategoriaEquipo) = '')");

    $db->query("CREATE TABLE IF NOT EXISTS areas_activo (
                  IdAreaActivo INT NOT NULL AUTO_INCREMENT,
                  Codigo VARCHAR(10) NOT NULL,
                  Nombre VARCHAR(80) NOT NULL,
                  Activo TINYINT(1) NOT NULL DEFAULT 1,
                  Orden INT NOT NULL DEFAULT 0,
                  PRIMARY KEY (IdAreaActivo),
                  UNIQUE KEY uq_areas_activo_codigo (Codigo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("INSERT IGNORE INTO areas_activo (Codigo,Nombre,Activo,Orden) VALUES
                  ('AD','Administración',1,10),('RAD','RAD',1,20),('TI','Tecnología de la Información',1,30),
                  ('GG','Gerencia General',1,40),('GO','Gerencia de Operaciones',1,50),
                  ('GC','Gerencia Comercial',1,60)");

    $areaIdResult = $db->query("SHOW COLUMNS FROM maquina LIKE 'IdAreaActivo'");
    if ($db->num_rows($areaIdResult) === 0) {
      $db->query("ALTER TABLE maquina ADD COLUMN IdAreaActivo INT NULL AFTER CategoriaEquipo");
      $db->query("ALTER TABLE maquina ADD INDEX idx_maquina_area_activo (IdAreaActivo)");
    }
    $securityColumns = [
      'Confidencialidad' => "TINYINT UNSIGNED NULL DEFAULT NULL AFTER IdAreaActivo",
      'Disponibilidad'   => "TINYINT UNSIGNED NULL DEFAULT NULL AFTER Confidencialidad",
      'Integridad'       => "TINYINT UNSIGNED NULL DEFAULT NULL AFTER Disponibilidad",
      'Criticidad'       => "VARCHAR(15) NULL DEFAULT NULL AFTER Integridad",
      'Clasificacion'    => "VARCHAR(20) NULL DEFAULT NULL AFTER Criticidad"
    ];
    foreach ($securityColumns as $column => $definition) {
      $columnCheck = $db->query("SHOW COLUMNS FROM maquina LIKE '{$column}'");
      if ($db->num_rows($columnCheck) === 0) $db->query("ALTER TABLE maquina ADD COLUMN {$column} {$definition}");
    }

    // Relacionar equipos históricos cuyo hostname contiene el código de área.
    $db->query("UPDATE maquina m INNER JOIN areas_activo a
                ON UPPER(m.Host) LIKE CONCAT('%-', a.Codigo, '-%')
                SET m.IdAreaActivo=a.IdAreaActivo
                WHERE m.IdAreaActivo IS NULL OR m.IdAreaActivo=0");
    $db->query("UPDATE maquina m
                INNER JOIN (SELECT IdMaquina, MAX(IdAsMaq) AS IdAsMaq FROM asig_maquina GROUP BY IdMaquina) ult ON ult.IdMaquina=m.IdMaquina
                INNER JOIN asig_maquina am ON am.IdAsMaq=ult.IdAsMaq
                INNER JOIN users u ON u.id=am.id
                INNER JOIN areas_activo a ON a.Codigo=CASE
                  WHEN UPPER(TRIM(u.area)) IN ('ADMINISTRACION','ADMINISTRACIÓN') THEN 'AD'
                  WHEN UPPER(TRIM(u.area))='RAD' THEN 'RAD'
                  WHEN UPPER(TRIM(u.area)) IN ('TI','SISTEMAS','TECNOLOGIA DE LA INFORMACION','TECNOLOGÍA DE LA INFORMACIÓN') THEN 'TI'
                  WHEN UPPER(TRIM(u.area)) IN ('GERENCIA_GENERAL','GERENCIA GENERAL','GG') THEN 'GG'
                  WHEN UPPER(TRIM(u.area)) IN ('GERENCIA_OPERACIONES','GERENCIA DE OPERACIONES','GERENCIA OPERACIONES','GO') THEN 'GO'
                  WHEN UPPER(TRIM(u.area)) IN ('GERENCIA_COMERCIAL','GERENCIA COMERCIAL','GC') THEN 'GC'
                  ELSE '' END
                SET m.IdAreaActivo=a.IdAreaActivo
                WHERE m.IdAreaActivo IS NULL OR m.IdAreaActivo=0");

    $ready = true;
   }

   function ensure_asig_maquina_schema(){
    global $db;
    static $ready = false;
    if ($ready) return;

    // En instalaciones antiguas IdAsMaq conserva la PRIMARY KEY, pero no
    // AUTO_INCREMENT. En ese caso cada nueva asignacion intenta usar el ID 0.
    $columnResult = $db->query("SHOW COLUMNS FROM asig_maquina LIKE 'IdAsMaq'");
    $column = $db->fetch_assoc($columnResult);

    if ($column && stripos((string)($column['Extra'] ?? ''), 'auto_increment') === false) {
      // Si ya existe una fila con ID 0, moverla primero para evitar que MySQL
      // la convierta en 1 y choque con una clave existente al modificar la tabla.
      $zeroResult = $db->query("SELECT COUNT(*) AS total FROM asig_maquina WHERE IdAsMaq = 0");
      $zeroRow = $db->fetch_assoc($zeroResult);

      if ((int)($zeroRow['total'] ?? 0) > 0) {
        $maxResult = $db->query("SELECT COALESCE(MAX(IdAsMaq), 0) AS max_id FROM asig_maquina");
        $maxRow = $db->fetch_assoc($maxResult);
        $nextAssignmentId = max(1, (int)($maxRow['max_id'] ?? 0) + 1);
        $db->query("UPDATE asig_maquina SET IdAsMaq = {$nextAssignmentId} WHERE IdAsMaq = 0");
      }

      $db->query("ALTER TABLE asig_maquina
                  MODIFY COLUMN IdAsMaq INT NOT NULL AUTO_INCREMENT");
    }

    $ready = true;
   }

   function ensure_activos_asignacion_historial(){
    global $db;
    static $ready = false;
    if ($ready) return;

    $db->query("CREATE TABLE IF NOT EXISTS activos_asignacion_historial (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      IdMaquina INT NOT NULL,
      usuario_anterior_id INT NULL,
      usuario_nuevo_id INT NULL,
      fecha_asignacion DATE NOT NULL,
      comentarios VARCHAR(250) NOT NULL DEFAULT '',
      accion VARCHAR(20) NOT NULL,
      registrado_por INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_activo_fecha (IdMaquina, created_at),
      KEY idx_usuario_nuevo (usuario_nuevo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Algunas instalaciones ya tenian la tabla con id=0 y sin AUTO_INCREMENT.
    // CREATE TABLE IF NOT EXISTS no corrige una estructura existente.
    $idColumnResult = $db->query("SHOW COLUMNS FROM activos_asignacion_historial LIKE 'id'");
    $idColumn = $db->fetch_assoc($idColumnResult);
    if ($idColumn && stripos((string)($idColumn['Extra'] ?? ''), 'auto_increment') === false) {
      // MySQL convierte un id=0 en 1 al activar AUTO_INCREMENT. Si el 1 ya
      // existe, primero movemos el cero al siguiente identificador libre.
      $zeroIdResult = $db->query("SELECT COUNT(*) AS total
                                  FROM activos_asignacion_historial WHERE id = 0");
      $zeroIdRow = $db->fetch_assoc($zeroIdResult);
      if ((int)($zeroIdRow['total'] ?? 0) > 0) {
        $maxIdResult = $db->query("SELECT COALESCE(MAX(id), 0) AS max_id
                                   FROM activos_asignacion_historial");
        $maxIdRow = $db->fetch_assoc($maxIdResult);
        $nextHistoryId = max(1, (int)($maxIdRow['max_id'] ?? 0) + 1);
        $db->query("UPDATE activos_asignacion_historial
                    SET id = {$nextHistoryId} WHERE id = 0");
      }

      $db->query("ALTER TABLE activos_asignacion_historial
                  MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
    }

    $ready = true;
   }

   function ensure_mantenimiento_ti_schema(){
    global $db;
    static $ready = false;
    if ($ready) return;

    $db->query("CREATE TABLE IF NOT EXISTS activos_mantenimientos (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      folio VARCHAR(30) NULL,
      IdMaquina INT NOT NULL,
      tipo VARCHAR(20) NOT NULL DEFAULT 'PREVENTIVO',
      prioridad VARCHAR(15) NOT NULL DEFAULT 'MEDIA',
      estado VARCHAR(20) NOT NULL DEFAULT 'PROGRAMADO',
      fecha_programada DATE NOT NULL,
      fecha_ejecucion DATE NULL,
      motivo TEXT NOT NULL,
      diagnostico TEXT NULL,
      trabajo_realizado TEXT NULL,
      foto_antes VARCHAR(500) NULL,
      foto_despues VARCHAR(500) NULL,
      tecnico_responsable VARCHAR(120) NULL,
      usuario_asignado_id INT NULL,
      usuario_asignado_nombre VARCHAR(150) NULL,
      creado_por INT NOT NULL DEFAULT 0,
      actualizado_por INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uq_activos_mant_folio (folio),
      KEY idx_activos_mant_equipo (IdMaquina),
      KEY idx_activos_mant_estado_fecha (estado, fecha_programada),
      KEY idx_activos_mant_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $executionDateCheck = $db->query("SHOW COLUMNS FROM activos_mantenimientos LIKE 'fecha_ejecucion'");
    if ($db->num_rows($executionDateCheck) === 0) {
      $db->query("ALTER TABLE activos_mantenimientos ADD COLUMN fecha_ejecucion DATE NULL AFTER fecha_programada");
    }

    foreach (['foto_antes', 'foto_despues'] as $photoColumn) {
      $photoColumnCheck = $db->query("SHOW COLUMNS FROM activos_mantenimientos LIKE '{$photoColumn}'");
      if ($db->num_rows($photoColumnCheck) === 0) {
        $db->query("ALTER TABLE activos_mantenimientos ADD COLUMN {$photoColumn} VARCHAR(500) NULL AFTER trabajo_realizado");
      }
    }

    $assignedUserIdCheck = $db->query("SHOW COLUMNS FROM activos_mantenimientos LIKE 'usuario_asignado_id'");
    if ($db->num_rows($assignedUserIdCheck) === 0) {
      $db->query("ALTER TABLE activos_mantenimientos ADD COLUMN usuario_asignado_id INT NULL AFTER tecnico_responsable");
    }
    $assignedUserNameCheck = $db->query("SHOW COLUMNS FROM activos_mantenimientos LIKE 'usuario_asignado_nombre'");
    if ($db->num_rows($assignedUserNameCheck) === 0) {
      $db->query("ALTER TABLE activos_mantenimientos ADD COLUMN usuario_asignado_nombre VARCHAR(150) NULL AFTER usuario_asignado_id");
    }
    foreach (['fecha_inicio', 'fecha_fin', 'proxima_fecha', 'costo'] as $obsoleteColumn) {
      $columnCheck = $db->query("SHOW COLUMNS FROM activos_mantenimientos LIKE '{$obsoleteColumn}'");
      if ($db->num_rows($columnCheck) > 0) {
        $db->query("ALTER TABLE activos_mantenimientos DROP COLUMN {$obsoleteColumn}");
      }
    }

    $db->query("CREATE TABLE IF NOT EXISTS activos_mantenimiento_detalle (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      mantenimiento_id BIGINT UNSIGNED NOT NULL,
      tipo VARCHAR(20) NOT NULL DEFAULT 'ACTIVIDAD',
      descripcion VARCHAR(500) NOT NULL,
      cantidad DECIMAL(12,2) NOT NULL DEFAULT 1,
      costo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
      resultado VARCHAR(250) NULL,
      creado_por INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_mant_detalle_orden (mantenimiento_id),
      CONSTRAINT fk_mant_detalle_orden FOREIGN KEY (mantenimiento_id)
        REFERENCES activos_mantenimientos (id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS activos_mantenimiento_historial (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      mantenimiento_id BIGINT UNSIGNED NOT NULL,
      accion VARCHAR(30) NOT NULL,
      estado_anterior VARCHAR(20) NULL,
      estado_nuevo VARCHAR(20) NULL,
      comentario VARCHAR(500) NULL,
      registrado_por INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_mant_historial_orden (mantenimiento_id, created_at),
      CONSTRAINT fk_mant_historial_orden FOREIGN KEY (mantenimiento_id)
        REFERENCES activos_mantenimientos (id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // No depender de un indice UNIQUE en instalaciones antiguas: algunas bases
    // permiten repetir el slug y terminaban creando una fila en cada visita.
    $db->query("INSERT INTO modulos (slug, nombre, descripcion, icono, estado, orden)
      SELECT 'mantenimiento_ti', 'Mantenimiento TI', 'Mantenimiento de computadoras y activos informaticos en MySQL', 'fas fa-screwdriver-wrench', 1, 45
      FROM DUAL
      WHERE NOT EXISTS (
        SELECT 1 FROM modulos WHERE slug = 'mantenimiento_ti'
      )");

    $groups = $db->query("SELECT group_level FROM user_groups WHERE group_status = 1");
    while ($group = $db->fetch_assoc($groups)) {
      $level = (int)$group['group_level'];
      $create = $level <= 2 ? 1 : 0;
      $edit = $level <= 2 ? 1 : 0;
      $delete = $level === 1 ? 1 : 0;
      $db->query("INSERT IGNORE INTO permisos_modulos
        (group_level, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar)
        VALUES ({$level}, 'mantenimiento_ti', 1, {$create}, {$edit}, {$delete})");
    }

    $ready = true;
   }
    function repopro(){
     global $db;
     $sql  =" SELECT r.TipoEntra,r.FechaEnt,r.Mes,r.Sem,r.Turno,r.Maquinaria,r.Operario,r.Proveedor,r.Producto,r.Color,r.MatePrima_Util,r.PesoProduc,r.ScrapTroquelado,r.Scrap_Corte_Vari,r.Merma,r.Scrap_Multicolor,r.Unidades,r.Hora_Ini,r.Hora_ter,r.Horas_Trabajadas,r.Tipo_MateriaPrima";
    $sql  .=" FROM repopro r";

    $sql  .=" ORDER BY r.IdRepoPro ASC";
    return find_by_sql($sql);
   
    }
   
    function incidencias(){
     global $db;
     $sql  =" SELECT *";
    $sql  .=" FROM incidencias p";
    $sql  .=" LEFT JOIN tipoproblema c ON c.CodTipoPro = p.CodTipoPro";
    $sql  .=" LEFT JOIN dificultad m ON m.CodDifi = p.CodDifi";
    $sql  .=" ORDER BY p.IdInci ASC";
    return find_by_sql($sql);
   
    }

       function Mantenimiento_sis(){
     global $db;
     $sql  =" SELECT p.IdMante,c.name,m.Host,p.Mantenimiento,p.Area,p.Serie,p.Periodo,p.Descripcion,p.Sugerencias,p.Fechapro,p.Fecharea,p.conformidadman";
    $sql  .=" FROM mantenimientos_sis p";
    $sql  .=" LEFT JOIN users c ON c.id = p.Usuario";
    $sql  .=" LEFT JOIN maquina m ON m.IdMaquina = p.Equipo";
 $sql  .=" ORDER BY p.IdMante DESC ";
    return find_by_sql($sql);
 }

    function repocelu(){
     global $db;
    $sql  =" SELECT *";
    $sql  .=" from telefono p";
    $sql  .="  LEFT JOIN celulares m ON m.IdTelefo = p.IdTelefo";

      $sql  .=" LEFT JOIN chip ch ON ch.InChip = m.IdChip";
    $sql  .=" LEFT JOIN users u ON u.id = m.Usuario";
        $sql  .=" Where EstUso = 2";
  
 

    return find_by_sql($sql);
   
    }


?>
