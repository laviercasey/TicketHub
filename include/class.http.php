<?php
class Http {
    
    static function header_code_verbose($code) {
        switch($code):
        case 200: return '200 OK';
        case 204: return '204 NoContent';
        case 401: return '401 Unauthorized';
        case 403: return '403 Forbidden';
        case 405: return '405 Method Not Allowed';
        case 416: return '416 Requested Range Not Satisfiable';
        default:  return '500 Internal Server Error';
        endswitch;
    }
    
    static function response($code,$content,$contentType='text/html',$charset='UTF-8') {
        header('HTTP/1.1 '.Http::header_code_verbose($code));
        header('Status: '.Http::header_code_verbose($code));
        header('Connection: Close');
        header("Content-Type: $contentType; charset=$charset");
        header('Content-Length: '.strlen($content));
        print $content;
        exit;
    }
	
    static function json($code, $data) {
        header('HTTP/1.1 '.Http::header_code_verbose($code));
        header('Status: '.Http::header_code_verbose($code));
        header('Connection: Close');
        header('Content-Type: application/json; charset=UTF-8');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        header('Content-Length: '.strlen($json));
        print $json;
        exit;
    }

	static function redirect($url,$delay=0,$msg='') {

        if(strstr($_SERVER['SERVER_SOFTWARE'], 'IIS')){
            header("Refresh: $delay; URL=$url");
        }else{
            header("Location: $url");
        }
        exit;
    }
}
?>
