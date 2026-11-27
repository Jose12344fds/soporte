
<?php
require_once __DIR__ . '/../config/settings.php'; 
include_once '../public/ajax/conexion.php';
include_once '../includes/functions.php';


$objeto = new Conexion();
$conexion2 = $objeto->Conectar();

$consulta = "SELECT * FROM usuariossap T0 INNER JOIN usuariotreminalsap T1 ON `T1`.`UsuTerminal`=`T0`.`CuentaTer` INNER JOIN users T2 ON `T2`.`name`=`T0`.`NomUsuario`";


$resultado = $conexion2->prepare($consulta);


$resultado->execute();


$data1=$resultado->fetchAll(PDO::FETCH_ASSOC);


?>
<?php
header("Pragma: public");
header("Expires: 0");
$filename = "Reporte_T-TI-F-12.xls";
header("Content-Type: application/xls"); 
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
?>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #0f0f0f;
  text-align: center;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
</style>
<meta charset="UTF-8">
<table>
<thead class="panel-default table-bordered table-striped " style="color: #002752 !important; border: 2">
               <tr style="border-color: #002752 !important;">
              
          <th class="text-center" colspan="2" rowspan="5" style="border-color: #002752 !important;"> <center><img src="<?php echo htmlspecialchars(REPORT_LOGO_URL, ENT_QUOTES, 'UTF-8'); ?>" WIDTH="120" HEIGHT="50"></center></th>
     <th class="text-center" colspan="5" rowspan="2" style="border-color: #002752 !important;"> SISTEMA INTEGRADO DE GESTIÓN</th>
     <th class="text-center" colspan="6"  style="border-color: #002752 !important;"> T-TI-F-12</th>
             
       
          </tr>
           <tr>
              <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Fecha </th>
                 <th class="text-center" colspan="4" style="border-color: #002752 !important;"> 02/03/2020 </th>           
                      
  
          </tr>
          
             <tr>
              <th class="text-center" colspan="5" rowspan="3" style="border-color: #002752 !important;"> ASIGNACIÓN DE CUENTAS Y LICENCIAS EN ERP SAP BUSINESS ONE  </th>
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Versión  </th> 
               <th class="text-center" colspan="4" style="border-color: #002752 !important;"> 01 </th>    
                      
  
          </tr>
            <tr>
          
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Elaborado  </th> 
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Revisado </th>    
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> Aprobado  </th>         
  
          </tr>
            <tr>
          
                 <th class="text-center"  colspan="2" style="border-color: #002752 !important;"> TI </th> 
               <th class="text-center" colspan="2" style="border-color: #002752 !important;"> GG </th> 
               <th class="text-center" colspan="2" style="border-color: #002752 !important;"> GG </th>         
  
          </tr>
    
          <tr>
             
                 <th class="text-center"  style="width: 5%;background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">ITEM</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">USUARIO TERMINAL</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">CONTRASEÑA</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">USUARIO SAP</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">GERENCIA</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">AREA</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">CORREO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">PUESTO SAP</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">NOMBRE USUARIO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">SUPERUSUARIO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">LICENCIA SAP</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">CONTRASEÑA SAP</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">ESTADO/ OBSER</small></th>
                 
                 
               
                 
             
               </tr>
       
            </thead>
 <tbody>
     
       <?php foreach($data1 as $rull1):?>
  

          <tr>
        <td><?php echo count_id();?></td>
         <td><?php echo $rull1['CuentaTer']?></td>
          <td><?php echo $rull1['ContraTerminal']?></td>
           <td><?php echo $rull1['UsuarioSap'] ?></td>     
           <td><?php echo $rull1['area']?></td>
                <td><?php echo $rull1['subarea']?></td>
                     <td><?php echo $rull1['Correo']?></td>
                      <td><?php echo $rull1['NompuSap']?></td>
                      <td><?php echo $rull1['NomUsuario']?></td>
                      <td><?php echo $rull1['SuperUsuario']?></td>
                      <td><?php echo $rull1['Licencia']?></td>
                      <td><?php echo $rull1['ContraSap']?></td>
    <td><?php echo $rull1['Estado']?></td>
              
  
          </tr>
           <?php endforeach;  ?>
                </tbody>
                </table>
  
         
           
