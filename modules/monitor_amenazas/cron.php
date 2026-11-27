<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit("Solo CLI\n");}
require_once __DIR__.'/../../includes/load.php';require_once __DIR__.'/funciones.php';ma_schema();
try{$result=ma_sync_all();echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE).PHP_EOL;exit(0);}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
