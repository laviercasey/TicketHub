<?php
class Misc {

	static function randCode($len=8) {
		return substr(strtoupper(bin2hex(random_bytes($len))),0,$len);
	}

    static function randNumber($len=6,$start=false,$end=false) {

        $start=(!$len && $start)?$start:(int)str_pad(1,$len,"0",STR_PAD_RIGHT);
        $end=(!$len && $end)?$end:(int)str_pad(9,$len,"9",STR_PAD_RIGHT);

        return random_int($start,$end);
    }

    static function encrypt(string $text, string $salt): string {

        if(function_exists('sodium_crypto_secretbox')) {
            $key = sodium_crypto_generichash($salt, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $ciphertext = sodium_crypto_secretbox($text, $nonce, $key);
            $result = 'nacl:' . base64_encode($nonce . $ciphertext);
            sodium_memzero($key);
            return $result;
        }

        if(!function_exists('openssl_encrypt')) {
            Sys::log(LOG_WARN,'crypto missing','Neither sodium nor openssl available. Cannot encrypt credentials.');
            return '';
        }

        $key = substr(hash('sha256', $salt, true), 0, 32);
        $iv  = random_bytes(16);
        $ciphertext = openssl_encrypt($text, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ciphertext);
    }

    static function decrypt(string $text, string $salt): string {

        if($text === '') return '';

        // Sodium (new format, prefixed with "nacl:")
        if(str_starts_with($text, 'nacl:') && function_exists('sodium_crypto_secretbox_open')) {
            $key = sodium_crypto_generichash($salt, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $raw = base64_decode(substr($text, 5));
            if($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
                sodium_memzero($key);
                return '';
            }
            $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $ciphertext = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
            sodium_memzero($key);
            return $plaintext !== false ? $plaintext : '';
        }

        // Legacy AES-256-CBC (backward compat for existing DB records)
        if(!function_exists('openssl_decrypt'))
            return '';

        $key = substr(hash('sha256', $salt, true), 0, 32);
        $raw  = base64_decode($text);
        if(strlen($raw) < 17) return '';
        $iv   = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $result = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $result !== false ? trim($result) : '';
    }

    static function db2gmtime($var){
        global $cfg;
        if(!$var) return;

        $dbtime=is_int($var)?$var:strtotime($var);
        return $dbtime-($cfg->getMysqlTZoffset()*3600);
    }

    static function dbtime($var=null){
         global $cfg;

        if(is_null($var) || !$var)
            $time=Misc::gmtime();
        else{
            $time=is_int($var)?$var:strtotime($var);
            $offset=$_SESSION['TZ_OFFSET']+($_SESSION['daylight']?date('I',$time):0);
            $time=$time-($offset*3600);
        }
        return $time+($cfg->getMysqlTZoffset()*3600);
    }

    static function gmtime() {
        return time()-date('Z');
    }

    static function currentURL() {

        $str = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $str .='s';
        }
        $str .= '://';
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],1 );
            if (isset($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING'];
            }
        }
        if ($_SERVER['SERVER_PORT']!=80) {
            $str .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
        } else {
            $str .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }

        return $str;
    }

    static function timeDropdown($hr=null, $min =null,$name='time') {
        $hr =is_null($hr)?0:$hr;
        $min =is_null($min)?0:$min;

        if($hr>=24)
            $hr=$hr%24;
        elseif($hr<0)
            $hr=0;

        if($min>=45)
            $min=45;
        elseif($min>=30)
            $min=30;
        elseif($min>=15)
            $min=15;
        else
            $min=0;

        ob_start();
        echo sprintf('<select name="%s" id="%s">',$name,$name);
        echo '<option value="" selected>Time</option>';
        for($i=23; $i>=0; $i--) {
            for($minute=45; $minute>=0; $minute-=15) {
                $sel=($hr==$i && $min==$minute)?'selected="selected"':'';
                $_minute=str_pad($minute, 2, '0',STR_PAD_LEFT);
                $_hour=str_pad($i, 2, '0',STR_PAD_LEFT);
                echo sprintf('<option value="%s:%s" %s>%s:%s</option>',$_hour,$_minute,$sel,$_hour,$_minute);
            }
        }
        echo '</select>';
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }


    static function generateCSRFToken() {
        if(empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::generateCSRFToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    static function rotateCSRFToken() {
        unset($_SESSION['csrf_token']);
        return self::generateCSRFToken();
    }
}
?>
