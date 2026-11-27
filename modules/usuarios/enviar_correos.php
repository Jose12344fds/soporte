<?php
declare(strict_types=1);

require_once __DIR__.'/../../includes/load.php';
require_once __DIR__.'/../../config/app.php';
require_once __DIR__.'/../incidencias/notificaciones.php';

header('Content-Type: application/json; charset=UTF-8');
require_permiso('usuarios', 'editar');
csrf_validate_or_fail();

function users_mail_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function users_mail_clean_html(string $html): string
{
    $html = trim($html);
    if ($html === '') return '';
    $document = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div id="mail-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $allowed = ['div','p','br','strong','b','em','i','u','ul','ol','li','a'];
    $nodes = [];
    foreach ($document->getElementsByTagName('*') as $node) $nodes[] = $node;
    foreach ($nodes as $node) {
        if ($node->getAttribute('id') === 'mail-root') continue;
        if (!in_array(strtolower($node->nodeName), $allowed, true)) {
            $node->parentNode?->replaceChild($document->createTextNode($node->textContent), $node);
            continue;
        }
        $attributes = [];
        foreach ($node->attributes ?? [] as $attribute) $attributes[] = $attribute->name;
        foreach ($attributes as $attribute) {
            if ($node->nodeName !== 'a' || $attribute !== 'href') $node->removeAttribute($attribute);
        }
        if ($node->nodeName === 'a') {
            $href = trim($node->getAttribute('href'));
            if (!preg_match('~^(https?://|mailto:)~i', $href)) $node->removeAttribute('href');
        }
    }
    $root = $document->getElementById('mail-root');
    if (!$root) return '';
    $clean = '';
    foreach ($root->childNodes as $child) $clean .= $document->saveHTML($child);
    return trim($clean);
}

function users_mail_template(string $subject, string $message): string
{
    $logo = 'cid:tama-logo';
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    return '<!doctype html><html><body style="margin:0;background:#eef3f7;font-family:Arial,Helvetica,sans-serif;color:#263946">'
        .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center" style="padding:28px 12px">'
        .'<table role="presentation" width="680" cellspacing="0" cellpadding="0" style="width:100%;max-width:680px;background:#fff;border:1px solid #dce7ed;border-radius:12px;overflow:hidden">'
        .'<tr><td style="padding:18px 26px;background:linear-gradient(135deg,#0f4560,#147f96)"><table width="100%"><tr><td><img src="'.htmlspecialchars($logo, ENT_QUOTES, 'UTF-8').'" width="125" alt="TAMA Ingenieros" style="display:block;background:#fff;padding:7px;border-radius:6px"></td><td align="right" style="color:#fff"><b>COMUNICACIÓN INTERNA</b><div style="font-size:12px;margin-top:5px;color:#c9edf2">Tecnología de Información</div></td></tr></table></td></tr>'
        .'<tr><td style="padding:28px"><h2 style="margin:0 0 20px;color:#173f52;font-size:22px">'.$safeSubject.'</h2><div style="font-size:15px;line-height:1.65">'.$message.'</div></td></tr>'
        .'<tr><td style="padding:14px 24px;background:#f4f7f9;text-align:center;color:#71838d;font-size:11px">Sistema Integrado de Gestión · TAMA Ingenieros S.A.C.</td></tr>'
        .'</table></td></tr></table></body></html>';
}

function users_mail_template_v2(string $subject, string $message, string $recipientName): string
{
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars(trim($recipientName) ?: 'colaborador', ENT_QUOTES, 'UTF-8');
    $sentAt = date('d/m/Y H:i');
    return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        .'<body style="margin:0;padding:0;background-color:#edf3f6;font-family:Arial,Helvetica,sans-serif;color:#243b47">'
        .'<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">Comunicado de Tecnología de Información: '.$safeSubject.'</div>'
        .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#edf3f6"><tr><td align="center" style="padding:34px 12px">'
        .'<table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:680px;background-color:#ffffff;border:1px solid #d9e5ea;border-radius:14px;overflow:hidden;box-shadow:0 8px 28px rgba(26,61,78,.10)">'
        .'<tr><td bgcolor="#ffffff" style="padding:24px 30px;border-bottom:4px solid #12879a"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>'
        .'<td valign="middle"><img src="cid:tama-logo" width="155" alt="TAMA Ingenieros S.A.C." style="display:block;width:155px;max-width:100%;height:auto;border:0"></td>'
        .'<td valign="middle" align="right"><span style="display:inline-block;padding:7px 12px;background-color:#e8f6f8;border-radius:16px;color:#11798a;font-size:11px;font-weight:bold;letter-spacing:.7px">COMUNICADO TI</span><div style="margin-top:8px;color:#70858f;font-size:11px">'.$sentAt.'</div></td>'
        .'</tr></table></td></tr>'
        .'<tr><td bgcolor="#123f55" style="padding:30px 34px;background-color:#123f55"><div style="font-size:12px;font-weight:bold;letter-spacing:1.2px;color:#83d5df;text-transform:uppercase">Tecnología de Información</div><h1 style="margin:9px 0 0;font-size:25px;line-height:1.28;color:#ffffff;font-weight:700">'.$safeSubject.'</h1></td></tr>'
        .'<tr><td style="padding:32px 34px 12px"><p style="margin:0;font-size:16px;line-height:1.55;color:#294653">Estimado(a) <strong>'.$safeName.'</strong>:</p></td></tr>'
        .'<tr><td style="padding:8px 34px 28px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td bgcolor="#f7fafb" style="padding:22px 24px;background-color:#f7fafb;border:1px solid #e1eaee;border-left:4px solid #18a0b3;border-radius:8px"><div style="font-size:15px;line-height:1.72;color:#283f4b">'.$message.'</div></td></tr></table></td></tr>'
        .'<tr><td style="padding:0 34px 30px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td bgcolor="#edf7f9" style="padding:14px 16px;background-color:#edf7f9;border-radius:7px;color:#54717d;font-size:12px;line-height:1.5"><strong style="color:#137c8d">Información importante</strong><br>Este comunicado fue enviado por el área de Tecnología de Información a través del Sistema de Soporte TI.</td></tr></table></td></tr>'
        .'<tr><td bgcolor="#f2f6f8" align="center" style="padding:18px 24px;background-color:#f2f6f8;border-top:1px solid #e0e8ec"><div style="font-size:12px;font-weight:bold;color:#385765">Equipo de Tecnología de Información</div><div style="margin-top:5px;font-size:10px;color:#81929a">Sistema Integrado de Gestión · TAMA Ingenieros S.A.C.</div></td></tr>'
        .'</table><div style="padding:14px 8px 0;color:#94a2a9;font-size:10px;text-align:center">Comunicación interna dirigida exclusivamente a sus destinatarios.</div>'
        .'</td></tr></table></body></html>';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') users_mail_json(['success'=>false,'error'=>'Método no permitido.'], 405);
set_time_limit(300);

$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['destinatarios'] ?? [])), static fn(int $id): bool => $id > 0)));
$subject = trim((string)($_POST['asunto'] ?? ''));
$message = users_mail_clean_html((string)($_POST['mensaje'] ?? ''));
if (!$ids) users_mail_json(['success'=>false,'error'=>'Seleccione al menos un usuario.'], 422);
if (count($ids) > 100) users_mail_json(['success'=>false,'error'=>'Puede enviar como máximo a 100 usuarios por operación.'], 422);
if (mb_strlen($subject) < 3 || mb_strlen($subject) > 180) users_mail_json(['success'=>false,'error'=>'El asunto debe tener entre 3 y 180 caracteres.'], 422);
if (mb_strlen(trim(strip_tags($message))) < 3) users_mail_json(['success'=>false,'error'=>'Redacte el mensaje del correo.'], 422);

$attachment = null;
if (!empty($_FILES['adjunto']['name']) && (int)($_FILES['adjunto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ((int)$_FILES['adjunto']['error'] !== UPLOAD_ERR_OK) users_mail_json(['success'=>false,'error'=>'No se pudo recibir el archivo adjunto.'], 422);
    if ((int)$_FILES['adjunto']['size'] > 10 * 1024 * 1024) users_mail_json(['success'=>false,'error'=>'El archivo adjunto no puede superar 10 MB.'], 422);
    $allowedExtensions = ['pdf','doc','docx','xls','xlsx','csv','txt','png','jpg','jpeg','zip'];
    $extension = strtolower(pathinfo((string)$_FILES['adjunto']['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) users_mail_json(['success'=>false,'error'=>'Tipo de archivo adjunto no permitido.'], 422);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $attachment = ['path'=>(string)$_FILES['adjunto']['tmp_name'],'name'=>(string)$_FILES['adjunto']['name'],'type'=>$finfo->file((string)$_FILES['adjunto']['tmp_name']) ?: 'application/octet-stream'];
}

$idList = implode(',', $ids);
$recipients = find_by_sql("SELECT id,name,correo FROM users WHERE id IN ({$idList}) AND status=1 AND TRIM(COALESCE(correo,''))<>'' ORDER BY name");
if (!$recipients) users_mail_json(['success'=>false,'error'=>'Los usuarios seleccionados no tienen correos activos válidos.'], 422);

$smtp = inc_mail_config();
if (!$smtp) users_mail_json(['success'=>false,'error'=>'No existe una configuración SMTP activa.'], 503);
$logoPath = dirname(__DIR__, 2).'/assets/img/logo.png';
$inlineImages = is_file($logoPath) ? [['path'=>$logoPath,'name'=>'logo-tama.png','type'=>'image/png','cid'=>'tama-logo']] : [];
$sent = 0; $failed = [];
foreach ($recipients as $recipient) {
    $email = trim((string)$recipient['correo']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $failed[] = (string)$recipient['name']; continue; }
    try {
        $html = users_mail_template_v2($subject, $message, (string)$recipient['name']);
        inc_mail_send($smtp, $email, (string)$recipient['name'], $subject, $html, $attachment, $inlineImages);
        $sent++;
    } catch (Throwable $error) {
        error_log('Usuarios correo masivo a '.$email.': '.$error->getMessage());
        $failed[] = (string)$recipient['name'];
    }
}

users_mail_json([
    'success'=>$sent > 0,
    'sent'=>$sent,
    'failed'=>count($failed),
    'failed_names'=>$failed,
    'message'=>$sent.' correo(s) enviado(s) correctamente'.($failed ? '; '.count($failed).' no se pudieron enviar.' : '.'),
], $sent > 0 ? 200 : 502);
