<?php
include_once(INCLUDE_DIR.'class.client.php');
include_once(INCLUDE_DIR.'class.staff.php');


class UserSession {
   public $session_id = '';
   public $userID='';
   public $browser = '';
   public $ip = '';
   public $validated=FALSE;

   public function __construct($userid){

      $this->browser=(!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : $_ENV['HTTP_USER_AGENT'];
      $this->ip=(!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : getenv('REMOTE_ADDR');
      $this->session_id=session_id();
      $this->userID=$userid;
   }

   function isStaff(){
       return FALSE;
   }

   function isClient() {
       return FALSE;
   }


   function getSessionId(){
       return $this->session_id;
   }

   function getIP(){
        return  $this->ip;
   }

   function getBrowser(){
       return $this->browser;
   }
   function refreshSession(){
   }

   function sessionToken(){

      $time  = time();
      $hash  = hash('sha256', $time.SESSION_SECRET.$this->userID);
      $token = "$hash:$time:".hash('sha256', $this->ip);

      return($token);
   }

   function isvalidSession($htoken,$maxidletime=0,$checkip=false){
        global $cfg;
       
        $token = rawurldecode($htoken);
        
        if($token && !strstr($token,":"))
            return FALSE;

        list($hash,$expire,$ip)=explode(":",$token);

        if(!hash_equals(hash('sha256', $expire . SESSION_SECRET . $this->userID), $hash)){
            return FALSE;
        }

        if($maxidletime && ((time()-$expire)>$maxidletime)){
            return FALSE;
        }

        if($checkip && strcmp($ip, hash('sha256', $this->ip)))
            return FALSE;

        $this->validated=TRUE;

        return TRUE;
   }

   function isValid() {
        return FALSE;
   }

}

class ClientSession extends Client {
    
    public $session;

    public function __construct($email,$id){
        parent::__construct($email,$id);
        $this->session= new UserSession($email);
    }

    function isValid(){
        global $_SESSION,$cfg;

        if(!$this->getId() || $this->session->getSessionId()!=session_id())
            return false;
        
        return $this->session->isvalidSession($_SESSION['_client']['token'],$cfg->getClientTimeout(),false)?true:false;
    }

    function refreshSession(){
        global $_SESSION;
        $_SESSION['_client']['token']=$this->getSessionToken();
    }

    function getSession() {
        return $this->session;
    }

    function getSessionToken() {
        return $this->session->sessionToken();
    }
    
    function getIP(){
        return $this->session->getIP();
    }    
}


class StaffSession extends Staff {
    
    public $session;
    
    public function __construct($var){
        parent::__construct($var);
        $this->session= new UserSession($var);
    }

    function isValid(){
        global $_SESSION,$cfg;

        if(!$this->getId() || $this->session->getSessionId()!=session_id())
            return false;
        
        return $this->session->isvalidSession($_SESSION['_staff']['token'],$cfg->getStaffTimeout(),$cfg->enableStaffIPBinding())?true:false;
    }

    function refreshSession(){
        global $_SESSION;
        $_SESSION['_staff']['token']=$this->getSessionToken();
    }
    
    function getSession() {
        return $this->session;
    }

    function getSessionToken() {
        return $this->session->sessionToken();
    }
    
    function getIP(){
        return $this->session->getIP();
    }
    
}

?>
