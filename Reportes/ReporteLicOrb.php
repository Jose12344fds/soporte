
<?php
require_once __DIR__ . '/../config/settings.php'; 
include_once '../public/ajax/conexion.php';
include_once '../includes/functions.php';


$objeto = new Conexion();
$conexion2 = $objeto->Conectar();

$consulta = "SELECT * FROM usuariosorbis";


$resultado = $conexion2->prepare($consulta);


$resultado->execute();


$data1=$resultado->fetchAll(PDO::FETCH_ASSOC);


?>
<?php
header("Pragma: public");
header("Expires: 0");
$filename = "Tama.xls";
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
              
          <th class="text-center" colspan="1" rowspan="5" style="border-color: #002752 !important;"> <center><img src="<?php echo htmlspecialchars(REPORT_LOGO_URL, ENT_QUOTES, 'UTF-8'); ?>" WIDTH="120" HEIGHT="50"></center></th>
     <th class="text-center" colspan="3" rowspan="2" style="border-color: #002752 !important;"> SISTEMA INTEGRADO DE GESTIÓN</th>
     <th class="text-center" colspan="3"  style="border-color: #002752 !important;"> T-TI-F-10</th>
             
       
          </tr>
           <tr>
              <th class="text-center" style="border-color: #002752 !important;"> Fecha </th>
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> 02/03/2020 </th>           
                      
  
          </tr>
          
             <tr>
              <th class="text-center" colspan="3" rowspan="3" style="border-color: #002752 !important;"> REGISTRO DE GESTION DE AUTORIZACIONES GENERALES DEL SISTEMA ORBIS </th>
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Versión  </th> 
               <th class="text-center" colspan="2" style="border-color: #002752 !important;"> 01 </th>    
                      
  
          </tr>
            <tr>
          
                 <th class="text-center" style="border-color: #002752 !important;"> Elaborado  </th> 
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Revisado </th>    
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Aprobado  </th>         
  
          </tr>
            <tr>
          
                 <th class="text-center"  colspan="1" style="border-color: #002752 !important;"> TI </th> 
               <th class="text-center" colspan="1" style="border-color: #002752 !important;"> GG </th> 
               <th class="text-center" colspan="1" style="border-color: #002752 !important;"> GG </th>         
  
          </tr>
    
          <tr>
             
                 <th class="text-center"  style="width: 45%;background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">ITEM / N°</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">AREA</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">NOMBRE Y APELLIDOS</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">USUARIO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">PASSWORD</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">UTILIDAD</small></th>
                <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">ESTADO/OBSER</small></th>
                 
              
             
               </tr>
       
            </thead>
 <tbody>
     
       <?php foreach($data1 as $rull1):?>
  

          <tr>
        <td><?php echo count_id();?></td>
         <td><?php echo $rull1['area']?></td>
          <td><?php echo $rull1['NomUsuario']?></td>
           <td><?php echo $rull1['CuentaOrbis'] ?></td>     
           <td><?php echo $rull1['ContraOrbis']?></td>
                <td><?php echo $rull1['Utilidad']?></td>
             
                     
              <td class="text-center">
           <?php if($rull1['Estado'] == '1'): ?>
            <span class="label label-success"><?php echo "Activo"; ?></span>
          <?php else: ?>
            <span class="label label-danger"><?php echo "Inactivo"; ?></span>
          <?php endif;?>
           </td>
              
  
          </tr>
           <?php endforeach;  ?>
                </tbody>
                </table>
      

