<?php
define('EX_DATAERR', 65);
define('EX_NOINPUT', 66);
define('EX_UNAVAILABLE', 69);
define('EX_IOERR', 74);
define('EX_TEMPFAIL',75);
define('EX_NOPERM',  77);
define('EX_CONFIG',  78);

define('EX_SUCCESS',0);

if(!file_exists('../main.inc.php')) exit(EX_CONFIG);
require_once('../main.inc.php');
if(!defined('INCLUDE_DIR')) exit(EX_CONFIG);

require_once(INCLUDE_DIR.'class.http.php');

define('THAPIINC',TRUE);

$remotehost=(isset($_SERVER['HTTP_HOST']) || isset($_SERVER['REMOTE_ADDR']))?TRUE:FALSE;

function api_exit($code,$msg='') {
    global $remotehost,$cfg;

    if($code!=EX_SUCCESS) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        api_record_error($ip);
        Sys::log(LOG_WARNING,"API error - code #$code",$msg);
    }
    if($remotehost){
        switch($code) {
        case EX_SUCCESS:
            Http::response(200,$code,'text/plain');
            break;
        case EX_UNAVAILABLE:
            Http::response(405,$code,'text/plain');
            break;
        case EX_NOPERM:
            Http::response(403,$code,'text/plain');
            break;
        case EX_DATAERR:
        case EX_NOINPUT:
        default:
            Http::response(416,$code,'text/plain');
        }
    }
    exit($code);
}

function api_record_error(string $ip): void {
    $cacheFile = sys_get_temp_dir() . '/th_api_ratelimit_' . md5($ip);
    $data = ['errors' => 0, 'time' => 0];
    if(file_exists($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if($raw !== false) {
            $parsed = @json_decode($raw, true);
            if(is_array($parsed)) $data = $parsed;
        }
    }
    $data['errors'] = ($data['errors'] ?? 0) + 1;
    $data['time'] = time();
    @file_put_contents($cacheFile, json_encode($data), LOCK_EX);
}

function api_check_rate_limit(string $ip): bool {
    $cacheFile = sys_get_temp_dir() . '/th_api_ratelimit_' . md5($ip);
    if(!file_exists($cacheFile)) return true;
    $raw = @file_get_contents($cacheFile);
    if($raw === false) return true;
    $data = @json_decode($raw, true);
    if(!is_array($data)) return true;

    // Reset after 5 minutes
    if((time() - ($data['time'] ?? 0)) > 300) {
        @unlink($cacheFile);
        return true;
    }

    return ($data['errors'] ?? 0) <= 10;
}

$pipeToken = $cfg->getPipeToken();
if(!$pipeToken)
    api_exit(EX_CONFIG, 'Pipe token not configured');

$token = '';
if($remotehost) {
    $ip=$_SERVER['REMOTE_ADDR'];
    if(!api_check_rate_limit($ip)) {
        api_exit(EX_NOPERM,"Remote host [$ip] rate-limited");
    }

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if($authHeader && stripos($authHeader, 'Bearer ') === 0) {
        $token = trim(substr($authHeader, 7));
    } elseif(!empty($_SERVER['HTTP_X_PIPE_TOKEN'])) {
        $token = trim($_SERVER['HTTP_X_PIPE_TOKEN']);
    }

    if(!$token || !hash_equals($pipeToken, $token)) {
        api_exit(EX_NOPERM, 'Invalid or missing pipe token from ['.$ip.']');
    }
} else {
    global $argv;
    $token = isset($argv[1]) ? trim($argv[1]) : '';
    if(!$token || !hash_equals($pipeToken, $token)) {
        api_exit(EX_NOPERM, 'Invalid or missing pipe token (CLI)');
    }
}
?>
