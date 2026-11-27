<?php
function fh_obs_modulo_schema(){global $db;$c=$db->query("SHOW COLUMNS FROM fh_ing_plano_obs LIKE 'modulo'");if(!$db->num_rows($c))$db->query("ALTER TABLE fh_ing_plano_obs ADD COLUMN modulo TINYINT NOT NULL DEFAULT 1");}
