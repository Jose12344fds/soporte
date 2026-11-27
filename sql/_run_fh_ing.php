<?php
require_once dirname(__DIR__) . '/includes/config.php';
$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if(!$con){ die('Connect error: '.mysqli_connect_error()); }
$sql = "CREATE TABLE IF NOT EXISTS fh_ing_ot_detalle (
  id INT NOT NULL AUTO_INCREMENT,
  ot VARCHAR(30) NOT NULL,
  proyecto VARCHAR(255) NOT NULL DEFAULT '',
  elaborado_por VARCHAR(150) NOT NULL DEFAULT '',
  revisado_por VARCHAR(150) NOT NULL DEFAULT '',
  aprobado_por VARCHAR(150) NOT NULL DEFAULT '',
  actualizado_al DATE NULL DEFAULT NULL,
  created_by INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_ot (ot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci";
if(mysqli_query($con,$sql)) echo 'OK'; else echo 'Error: '.mysqli_error($con);
mysqli_close($con);
