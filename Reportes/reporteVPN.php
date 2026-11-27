
<?php
require_once __DIR__ . '/../config/settings.php'; 
include_once '../public/ajax/conexion.php';
include_once '../includes/functions.php';


$objeto = new Conexion();
$conexion2 = $objeto->Conectar();

$consulta = "SELECT *, concat(Fecha,' ',Hora) as dateff FROM vpn";


$resultado = $conexion2->prepare($consulta);


$resultado->execute();


$data1=$resultado->fetchAll(PDO::FETCH_ASSOC);


?>
<?php
header("Pragma: public");
header("Expires: 0");
$filename = "Reporte_T-TI-F-21_REGISTRO_DE_ACCESO_A_LA_VPN.xls";
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

        <thead class="panel-default" style="color: #002752 !important; border: 2">
                   <tr style="border-color: #002752 !important;">
              
          <th class="text-center" colspan="2" rowspan="5" style="border-color: #002752 !important;"> <center><img src="<?php echo htmlspecialchars(REPORT_LOGO_URL, ENT_QUOTES, 'UTF-8'); ?>" WIDTH="120" HEIGHT="50"></center></th>
     <th class="text-center" colspan="6" rowspan="2" style="border-color: #002752 !important;"> SISTEMA INTEGR  ADO DE GESTIÓN</th>
     <th class="text-center" colspan="3"  style="border-color: #002752 !important;"> T-TI-F-21</th>
             
       
          </tr>
           <tr>
              <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Fecha </th>
                 <th class="text-center" colspan="2" style="border-color: #002752 !important;"> 02/03/2020 </th>           
                      
  
          </tr>
          
             <tr>
              <th class="text-center" colspan="6" rowspan="3" style="border-color: #002752 !important;"> REGISTRO DE ACCESO A LA VPN </th>
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Versión  </th> 
               <th class="text-center" colspan="2" style="border-color: #002752 !important;"> 01 </th>    
                      
  
          </tr>
            <tr>
          
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Elaborado  </th> 
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Revisado </th>    
                 <th class="text-center" colspan="1" style="border-color: #002752 !important;"> Aprobado  </th>         
  
          </tr>
            <tr>
          
                 <th class="text-center"  colspan="1" style="border-color: #002752 !important;"> TI </th> 
               <th class="text-center" colspan="1" style="border-color: #002752 !important;"> GG </th> 
               <th class="text-center" colspan="1" style="border-color: #002752 !important;"> GG </th>         
  
          </tr>
          <tr>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">ID</small></th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff"> SOLICITANTE DE ACCESO A LA VPN</small> </th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff"> EMPRESA</small> </th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff"> MOTIVO</small> </th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff"> FECHA / HORA DE SOLICITUD</small></th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff"> NIVEL DE TIEMPO DE USO</small></th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">TIEMPO DE USO (horas)</small></th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">CULMINADO</small> </th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">TOKEN</small> </th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">PERMISO</small> </th>
               <th class="text-center"  style="background-color:#0069d9 ;border-color: #002752 !important;"><small style="color: #ffffff">RESPONSABLE </small></th>
           
                    </tr>
            </thead>
             
                 
                 
               
            
 <tbody>
     
       <?php foreach($data1 as $rull1):?>
  

          <tr> 
              
        <td><?php echo count_id();?></td>
         <td><?php echo $rull1['Solicitante']?></td>
          <td><?php echo $rull1['Empresa']?></td>
           <td><?php echo $rull1['Motivo'] ?></td>     
           <td><?php echo $rull1['dateff']?></td>
                <td><?php echo $rull1['Nivel_Usu']?></td>
                     <td><?php echo $rull1['Tiempo']?></td>
                      <td><?php echo $rull1['Culminado']?></td>
                      <td><?php echo $rull1['Token']?></td>
                      <td><?php echo $rull1['Permiso']?></td>
                      <td><?php echo $rull1['Responsable']?></td>
                    
              
  
          </tr>
           <?php endforeach;  ?>
                </tbody>
                </table>
  
         
           
