<?php
require_once __DIR__.'/ConexionHana.php';
class ConsultasPAVREAL {
 private $conexion;
 public function __construct(){ $this->conexion=Conexion1::Conectar1(); }
 public function actualizarAvanceRealProyecto($codProyecto,$codPdre,$avance){
  try{
   $codProyecto=trim((string)$codProyecto);$codPdre=trim((string)$codPdre);$avance=(float)$avance;if($codProyecto===''||$codPdre===''||!is_finite($avance)||$avance<0||$avance>100)throw new RuntimeException('Claves o avance SAP inválidos.');
   $params=[':codProyecto'=>$codProyecto,':codPdre'=>$codPdre];
   $select='SELECT "U_TQC_POAV" AS "Avance" FROM "SBO_TAMAING_PRODUC"."@TQC_LIOT" WHERE "Code" = :codProyecto AND "U_TQC_ITEM" = :codPdre';
   $check=$this->conexion->prepare($select);$check->execute($params);$before=$check->fetch(PDO::FETCH_NUM);$check->closeCursor();if($before===false)throw new RuntimeException('No existe la combinación de proyecto e ítem en SAP.');
   $stmt=$this->conexion->prepare('UPDATE "SBO_TAMAING_PRODUC"."@TQC_LIOT" SET "U_TQC_POAV" = :avance WHERE "Code" = :codProyecto AND "U_TQC_ITEM" = :codPdre');if(!$stmt->execute([':avance'=>$avance]+$params))throw new RuntimeException('SAP rechazó la actualización.');$rows=$stmt->rowCount();
   $check->execute($params);$found=false;for($i=0;$i<1000;$i++){$row=$check->fetch(PDO::FETCH_NUM);if($row===false)break;$found=true;if($row[0]===null||!is_numeric($row[0])||abs((float)$row[0]-$avance)>0.011)throw new RuntimeException('El valor leído de SAP no coincide con el avance enviado.');}if(!$found||$i===1000)throw new RuntimeException('No se pudo verificar el conjunto de registros en SAP.');$check->closeCursor();
   return ['success'=>true,'rowsAffected'=>$rows,'verified'=>true,'avanceConfirmado'=>$avance,'error'=>null];
  }catch(Throwable $e){return ['success'=>false,'rowsAffected'=>0,'verified'=>false,'error'=>$e->getMessage()];}
 }
}
