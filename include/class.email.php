<?php
include_once(INCLUDE_DIR.'class.dept.php');

use TicketHub\Mail\SymfonyMailTransport;
use TicketHub\Mail\SymfonyMimeBuilder;
use TicketHub\Mail\MailTransportException;

class Email {
    public $id;
    public $email;
    public $address;
    public $name;

    public $autoresp;
    public $deptId;
    public $priorityId;

    public $dept;
    public $info;

    public function __construct($id,$fetch=true){
        $this->id=$id;
        if($fetch)
            $this->load();
    }

    function load() {

        if(!$this->id)
            return false;

        $sql='SELECT * FROM '.EMAIL_TABLE.' WHERE email_id='.db_input($this->id);
        if(($res=db_query($sql)) && db_num_rows($res)) {
            $info=db_fetch_array($res);
            $this->id=$info['email_id'];
            $this->email=$info['email'];
            $this->name=$info['name'];
            $this->address=$info['name']?($info['name'].'<'.$info['email'].'>'):$info['email'];
            $this->deptId=$info['dept_id'];
            $this->priorityId=$info['priority_id'];
            $this->autoresp=$info['noautoresp']?false:true;
            $this->info=$info;
            return true;
        }
        $this->id=0;

        return false;
    }

    function reload() {
        return $this->load();
    }

    function getId(){
        return $this->id;
    }

    function getEmail(){
        return $this->email;
    }

    function getAddress() {
        return $this->address;
    }

    function getName(){
        return $this->name;
    }

    function getPriorityId() {
        return $this->priorityId;
    }

    function getDeptId() {
        return $this->deptId;
    }

    function getDept() {

        if(!$this->dept && $this->deptId)
            $this->dept= new Dept($this->deptId);

        return $this->dept;
    }

    function autoRespond() {
          return $this->autoresp;
    }

    function getInfo() {
        return $this->info;
    }

    function isSMTPEnabled() {
         return $this->info['smtp_active'];

    }

    function getSMTPInfo($active=true){
        $info=array();
        if(!$active || ($active && $this->isSMTPEnabled())){

            $info = array ('host' => $this->info['smtp_host'],
                           'port' => $this->info['smtp_port'],
                           'auth' => $this->info['smtp_auth'],
                           'username' => $this->info['userid'],
                           'password' =>Misc::decrypt($this->info['userpass'],SECRET_SALT)
                           );
        }

        return $info;
    }

    function update($vars,&$errors) {
        if($this->save($this->getId(),$vars,$errors)){
            $this->reload();
            return true;
        }

        return false;
    }



    function send($to,$subject,$message,$attachment=null) {
        global $cfg;

        $smtp=array();
        if($this->isSMTPEnabled() && ($info=$this->getSMTPInfo())){
            $smtp=$info;
        }elseif($cfg && ($email=$cfg->getDefaultSMTPEmail()) && $email->isSMTPEnabled()){
            if($cfg->allowSMTPSpoofing() && ($info=$email->getSMTPInfo())){
                $smtp=$info;
            }elseif($email->getId()!=$this->getId()){
                return $email->send($to,$subject,$message,$attachment);
            }
        }

        $to=preg_replace("/(\r\n|\r|\n)/s",'', trim($to));
        $subject=stripslashes(preg_replace("/(\r\n|\r|\n)/s",'', trim($subject)));
        $body = stripslashes(preg_replace("/(\r\n|\r)/s", "\n", trim($message)));
        $fromname=$this->getName();
        $from =sprintf('"%s"<%s>',($fromname?$fromname:$this->getEmail()),$this->getEmail());
        $headers = array ('From' => $from,
                          'To' => $to,
                          'Subject' => $subject,
                          'Date'=>date("D , d M Y H:i:s O"),
                          'Message-ID' =>'<'.Misc::randCode(6).''.time().'-'.$this->getEmail().'>',
                          'X-Mailer' =>'TicketHub v 1.0',
                          );

        $mime = new SymfonyMimeBuilder();
        $mime->setTextBody($body);
        if($attachment && $attachment['file'] && is_readable($attachment['file'])) {
            $mime->addAttachment($attachment['file'],$attachment['type'],$attachment['name']);
        }
        $options=array('text_encoding' => 'quoted-printable',
                       'text_charset'  => 'utf-8');
        $body = $mime->getBody($options);
        $headers = $mime->getHeaders($headers);

        if($smtp){
            try {
                $transport = new SymfonyMailTransport(array(
                    'host' => $smtp['host'],
                    'port' => (int)$smtp['port'],
                    'auth' => $smtp['auth'] ? true : false,
                    'username' => $smtp['username'],
                    'password' => $smtp['password'],
                    'timeout' => 20,
                ));
                $transport->send($to, $headers, $body);
                if(function_exists('sodium_memzero')) sodium_memzero($smtp['password']);
                return true;
            } catch (MailTransportException $e) {
                if(function_exists('sodium_memzero')) sodium_memzero($smtp['password']);
                $alert=sprintf("Unable to email via %s:%d (user: %s) — SMTP error code %d",
                    $smtp['host'],$smtp['port'],$smtp['username'],$e->getCode());
                Sys::log(LOG_ALERT,'SMTP Error',$alert,false);
            }
        }

        try {
            $transport = new SymfonyMailTransport();
            return $transport->send($to, $headers, $body);
        } catch (MailTransportException) {
            return false;
        }
    }

    static function sendmail($to,$subject,$message,$from) {

        $to=preg_replace("/(\r\n|\r|\n)/s",'', trim($to));
        $subject=stripslashes(preg_replace("/(\r\n|\r|\n)/s",'', trim($subject)));
        $body = stripslashes(preg_replace("/(\r\n|\r)/s", "\n", trim($message)));
        $headers = array ('From' =>$from,
                          'To' => $to,
                          'Subject' => $subject,
                          'Message-ID' =>'<'.Misc::randCode(10).''.time().'@TicketHub>',
                          'X-Mailer' =>'TicketHub v 1.0',
                          );

        $mime = new SymfonyMimeBuilder();
        $mime->setTextBody($body);
        $options=array('text_encoding' => 'quoted-printable',
                       'text_charset'  => 'utf-8');
        $body = $mime->getBody($options);
        $headers = $mime->getHeaders($headers);

        try {
            $transport = new SymfonyMailTransport();
            return $transport->send($to, $headers, $body);
        } catch (MailTransportException) {
            return false;
        }
    }


    static function getIdByEmail($email) {

        $resp=db_query('SELECT email_id FROM '.EMAIL_TABLE.' WHERE email='.db_input($email));
        if($resp && db_num_rows($resp))
            list($id)=db_fetch_row($resp);

        return $id;
    }

    static function create($vars,&$errors) {
        return Email::save(0,$vars,$errors);
    }


    static function save($id,$vars,&$errors) {
        global $cfg;

        if($id && $id!=$vars['email_id'])
            $errors['err']='Внутренняя ошибка.';

        if(!$vars['email'] || !Validator::is_email($vars['email'])){
            $errors['email']='Введите правильный email';
        }elseif(($eid=Email::getIdByEmail($vars['email'])) && $eid!=$id){
            $errors['email']='Email уже существует';
        }elseif(!strcasecmp($cfg->getAdminEmail(),$vars['email'])){
            $errors['email']='Email уже существет как email администратора!';
        }else{
            $sql='SELECT staff_id FROM '.STAFF_TABLE.' WHERE email='.db_input($vars['email']);
            if(($res=db_query($sql)) && db_num_rows($res))
                $errors['email']='Email используется менеджером';
        }


        if(!$vars['dept_id'] || !is_numeric($vars['dept_id']))
            $errors['dept_id']='Вы должны выбрать отдел';

        if(!$vars['priority_id'])
            $errors['priority_id']='Вы должны выбрать приоритет';

        if($vars['mail_active'] || ($vars['smtp_active'] && $vars['smtp_auth'])) {
            if(!$vars['userid'])
                $errors['userid']='Введите логин';

            if(!$vars['userpass'])
                $errors['userpass']='Введите пароль';
        }

        if($vars['mail_active']) {
            if(!function_exists('imap_open'))
                $errors['mail_active']= 'IMAP не существует. PHP должен быть собран с IMAP модулем.';
            if(!$vars['mail_host'])
                $errors['mail_host']='Введите адрес хоста';
            if(!$vars['mail_port'])
                $errors['mail_port']='Введите порт';
            if(!$vars['mail_protocol'])
                $errors['mail_protocol']='Выберите протокол';
            if(!$vars['mail_fetchfreq'] || !is_numeric($vars['mail_fetchfreq']))
                $errors['mail_fetchfreq']='Введите интервал получения';
            if(!$vars['mail_fetchmax'] || !is_numeric($vars['mail_fetchmax']))
                $errors['mail_fetchmax']='Введите максимум писем';

        }

        if($vars['smtp_active']) {
            if(!$vars['smtp_host'])
                $errors['smtp_host']='Host name required';
            if(!$vars['smtp_port'])
                $errors['smtp_port']='Port required';
        }

        if(!$errors && ($vars['mail_host'] && $vars['userid'])){
            $sql='SELECT email_id FROM '.EMAIL_TABLE.' WHERE mail_host='.db_input($vars['mail_host']).' AND userid='.db_input($vars['userid']);
            if($id)
                $sql.=' AND email_id!='.db_input($id);

            if(db_num_rows(db_query($sql)))
                $errors['userid']=$errors['host']='Another department using host/username combination.';
        }

        if(!$errors && $vars['mail_active']) {
            $fetcher = new MailFetcher($vars['userid'],$vars['userpass'],$vars['mail_host'],$vars['mail_port'],
                                            $vars['mail_protocol'],$vars['mail_encryption']);
            if(!$fetcher->connect()) {
                $errors['userpass']='<br>Invalid login. Check '.$vars['mail_protocol'].' settings';
                $errors['mail']='<br>'.$fetcher->getLastError();
            }
        }

        if(!$errors && $vars['smtp_active']) {
            try {
                $smtpConfig = array(
                    'host' => $vars['smtp_host'],
                    'port' => (int)$vars['smtp_port'],
                    'auth' => $vars['smtp_auth'] ? true : false,
                    'username' => $vars['userid'],
                    'password' => $vars['userpass'],
                    'timeout' => 20,
                );
                $transport = new SymfonyMailTransport($smtpConfig);
                $transport->testSmtpConnection($smtpConfig);
            } catch (MailTransportException $e) {
                $errors['userpass']='<br>Unable to login. Check SMTP settings.';
                $errors['smtp']='<br>'.$e->getMessage();
            }
        }

        if(!$errors) {
            $sql='updated=NOW(),mail_errors=0, mail_lastfetch=NULL'.
                ',email='.db_input($vars['email']).
                ',name='.db_input(Format::striptags($vars['name'])).
                ',dept_id='.db_input($vars['dept_id']).
                ',priority_id='.db_input($vars['priority_id']).
                ',noautoresp='.db_input(isset($vars['noautoresp'])?1:0).
                ',userid='.db_input($vars['userid']).
                ',userpass='.db_input(Misc::encrypt($vars['userpass'],SECRET_SALT)).
                ',mail_active='.db_input($vars['mail_active']).
                ',mail_host='.db_input($vars['mail_host']).
                ',mail_protocol='.db_input($vars['mail_protocol']?$vars['mail_protocol']:'POP').
                ',mail_encryption='.db_input($vars['mail_encryption']).
                ',mail_port='.db_input($vars['mail_port']?$vars['mail_port']:0).
                ',mail_fetchfreq='.db_input($vars['mail_fetchfreq']?$vars['mail_fetchfreq']:0).
                ',mail_fetchmax='.db_input($vars['mail_fetchmax']?$vars['mail_fetchmax']:0).
                ',mail_delete='.db_input(isset($vars['mail_delete'])?$vars['mail_delete']:0).
                ',smtp_active='.db_input($vars['smtp_active']).
                ',smtp_host='.db_input($vars['smtp_host']).
                ',smtp_port='.db_input($vars['smtp_port']?$vars['smtp_port']:0).
                ',smtp_auth='.db_input($vars['smtp_auth']);

            if($id){
                $sql='UPDATE '.EMAIL_TABLE.' SET '.$sql.' WHERE email_id='.db_input($id);
                if(!db_query($sql) || !db_affected_rows())
                    $errors['err']='Unable to update email. Internal error occured';
            }else {
                $sql='INSERT INTO '.EMAIL_TABLE.' SET '.$sql.',created=NOW()';
                if(!db_query($sql) or !($emailID=db_insert_id()))
                    $errors['err']='Unable to add email. Internal error';
                else
                    return $emailID;
            }

        }else{
            $errors['err']='Error(s) Occured. Try again';
        }

        return $errors?FALSE:TRUE;
    }

    static function deleteEmail($id) {
        global $cfg;
        if($id==$cfg->getDefaultEmailId() || $id==$cfg->getAlertEmailId())
            return 0;

        $sql='DELETE FROM '.EMAIL_TABLE.' WHERE email_id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())){
            db_query('UPDATE '.DEPT_TABLE.' SET email_id='.db_input($cfg->getDefaultEmailId()).' WHERE email_id='.db_input($id));
            db_query('UPDATE '.DEPT_TABLE.' SET autoresp_email_id=0 WHERE email_id='.db_input($id));
            return $num;
        }
        return 0;
    }



}
?>
