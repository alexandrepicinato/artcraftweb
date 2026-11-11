<?php
declare(strict_types=1);
/** Dados persistentes ficam preferencialmente FORA do DOCUMENT_ROOT. */
$documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__)) ?: __DIR__;
$outside = dirname($documentRoot) . '/.craftsuite_' . substr(hash('sha256', realpath(__DIR__) ?: __DIR__), 0, 12);
if (!is_dir($outside) && is_writable(dirname($documentRoot))) @mkdir($outside,0700,true);
define('PRIVATE_ROOT', is_dir($outside) && is_writable($outside) ? $outside : __DIR__ . '/private');
define('MODULE_ROOT', __DIR__ . '/modules');
ini_set('display_errors','0');
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('crafthub_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'),'/\\').'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header('Content-Security-Policy: frame-ancestors \'self\'');
header('Cache-Control: private, no-store');
function catalog(): array { return require __DIR__.'/catalog.php'; }
function esc(string $s):string { return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function config_file():string { return PRIVATE_ROOT.'/config.json'; }
function config():array { $f=config_file(); return is_file($f) ? (json_decode((string)file_get_contents($f),true) ?: []) : []; }
function configured():bool { return isset(config()['password_hash']); }
function is_admin():bool { return !empty($_SESSION['admin']) && configured(); }
function token():string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function validate_token():void { if (!hash_equals(token(),(string)($_POST['csrf']??''))) throw new RuntimeException('Sessão expirada. Atualize a página e tente novamente.'); }
function require_admin():void { if (!is_admin()) { http_response_code(403); throw new RuntimeException('Acesso administrativo necessário.'); } }
function save_config(array $conf):void {
    if (!is_dir(PRIVATE_ROOT) && !mkdir(PRIVATE_ROOT,0700,true)) throw new RuntimeException('Sem permissão para criar pasta private.');
    $temp=PRIVATE_ROOT.'/config.'.bin2hex(random_bytes(6)).'.tmp';
    if (file_put_contents($temp,json_encode($conf,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false) throw new RuntimeException('Não foi possível gravar a configuração.');
    chmod($temp,0600);
    if (!rename($temp,config_file())) { @unlink($temp); throw new RuntimeException('Erro ao salvar configurações.'); }
}
function module_installed(string $id):bool {
    return is_file(MODULE_ROOT.'/'.$id.'/index.html');
}
