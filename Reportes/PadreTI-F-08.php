
<?php 
include_once '../public/ajax/conexion.php';
include_once '../includes/functions.php';


$objeto = new Conexion();
$conexion2 = $objeto->Conectar();

$consulta = "SELECT * FROM `usuariosf08` T0 INNER JOIN users T2 ON `T2`.`name`=`T0`.`NomApe` LEFT JOIN maquina T3 ON `T3`.`Host`=`T0`.`NumPC`;";


$resultado = $conexion2->prepare($consulta);


$resultado->execute();


$data1=$resultado->fetchAll(PDO::FETCH_ASSOC);


?>
<?php
header("Pragma: public");
header("Expires: 0");
$filename = "Consolidado_F08.xls";
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
        
          
         
    
          <tr>
             
                 <th class="text-center"  style="width: 5%;background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">Nombre</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">AREA</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">CARGO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">NUMERO PC</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">IP</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">USUARIO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">INICIO LABORES</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">COR TELEFONO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">CORREO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">NIVEL CORREO</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">CELULAR</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">NIVEL DE NAVEGACION</small></th>
              
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">Carpeta Compartida Modificar</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">Carpeta Compartida Veedor</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">Microsoft Office</small></th>
                 
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">ERP SAP</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">Licencia</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">USUARIO SAP</small></th>
                 
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">Requermientos Portal</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">Utilidad</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">USUARIO REQUE.</small></th>
                 
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">ORBIS</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">LICENCIA</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">USUARIO ORBIS</small></th>
                 
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">APPTAMA</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">LICENCIA</small></th>
                 <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">USUARIO APP</small></th>
                 
    
             
                 
       
                 
                 
               </tr>
       
            </thead>
 <tbody>
     
       <?php foreach($data1 as $rull1):?>
  

          <tr>
        
                       <td><?php echo $rull1['NomApe']?></td>
                     <td><?php echo $rull1['area']?></td>
                     <td><?php echo $rull1['Cargo'] ?></td>     
                     <td><?php echo $rull1['NumPC']?></td>
                      <td><?php echo $rull1['Ip']?></td>
                     <td><?php echo $rull1['UsuarioTamadom']?></td>
                      <td><?php echo $rull1['FechaLabores']?></td>
                      <td><?php echo $rull1['corrcelu_f8']?></td>
                      <td><?php echo $rull1['Correo']?></td>
                      <td><?php echo $rull1['nivcorr_f8']?></td>
                      <td><?php echo $rull1['Celular_f8']?></td>
                      <td><?php echo $rull1['ninavega_f8']?></td>
                      <td><?php echo $rull1['CarpetasCompartidasM']?></td>
                      <td><?php echo $rull1['CarpetasCompartidasV']?></td>
                      <td><?php echo $rull1['softwarebasi_f8']?></td>
                      <td><?php echo $rull1['sap_f8']?></td>
                      <td><?php echo $rull1['saplicen_f8']?></td>
                      <td><?php echo $rull1['ususap_f8']?></td>
                      <td><?php echo $rull1['requerimient_f8']?></td>
                      <td><?php echo $rull1['requeutili_f8']?></td>
                      <td><?php echo $rull1['usureque_f8']?></td>
                      <td><?php echo $rull1['orbis_f8']?></td>
                      <td><?php echo $rull1['utilidadorb_f8']?></td>
                      <td><?php echo $rull1['usuorb_f8']?></td>
                      <td><?php echo $rull1['apptam_f8']?></td>
                      <td><?php echo $rull1['utilidapptam_f8']?></td>
                      <td><?php echo $rull1['usuapptam_f8']?></td>
                      
          </tr>
           <?php endforeach;  ?>
                </tbody>
                </table>
      

