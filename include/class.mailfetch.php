<?php
require_once(INCLUDE_DIR.'class.mailparse.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');

class MailFetcher {
    public $hostname;
    public $username;
    public $password;

    public $port;
    public $protocol;
    public $encryption;

    public $mbox;

    public $charset= 'UTF-8';

    public function __construct($username,$password,$hostname,$port,$protocol,$encryption='') {

        if(!strcasecmp($protocol,'pop'))
            $protocol='pop3';

        $this->hostname=$hostname;
        $this->username=$username;
        $this->password=$password;
        $this->protocol=strtolower($protocol);
        $this->port = $port;
        $this->encryption = $encryption;

        $this->serverstr=sprintf('{%s:%d/%s',$this->hostname,$this->port,strtolower($this->protocol));
        if(!strcasecmp($this->encryption,'SSL')){
            $this->serverstr.='/ssl';
        }
        $this->serverstr.='/novalidate-cert}INBOX';

        $this->charset='UTF-8';
        if(function_exists('imap_timeout'))
            imap_timeout(1,20);
    }

    function connect() {
        return $this->open()?true:false;
    }

    function open() {

        if($this->mbox && imap_ping($this->mbox))
            return $this->mbox;

        $this->mbox =@imap_open($this->serverstr,$this->username,$this->password);

        return $this->mbox;
    }

    function close() {
        imap_close($this->mbox,CL_EXPUNGE);
    }

    function mailcount(){
        return count(imap_headers($this->mbox));
    }


    function decode($encoding,$text) {

        switch($encoding) {
            case 1:
            $text=imap_8bit($text);
            break;
            case 2:
            $text=imap_binary($text);
            break;
            case 3:
            $text=imap_base64($text);
            break;
            case 4:
            $text=imap_qprint($text);
            break;
            case 5:
            default:
             $text=$text;
        }
        return $text;
    }

    function mime_encode($text,$charset=null,$enc='utf-8') {

        $encodings=array('UTF-8','WINDOWS-1251', 'ISO-8859-5', 'ISO-8859-1','KOI8-R');
        if(function_exists("iconv") and $text) {
            if($charset)
                return iconv($charset,$enc.'//IGNORE',$text);
            elseif(function_exists("mb_detect_encoding"))
                return iconv(mb_detect_encoding($text,$encodings),$enc,$text);
        }

        return mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }

    function mime_decode($text) {

        $a = imap_mime_header_decode($text);
        $str = '';
        foreach ($a as $k => $part)
            $str.= $part->text;

        return $str?$str:imap_utf8($text);
    }

    function getLastError(){
        return imap_last_error();
    }

    function getMimeType($struct) {
        $mimeType = array('TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER');
        if(!$struct || !$struct->subtype)
            return 'TEXT/PLAIN';

        return $mimeType[(int) $struct->type].'/'.$struct->subtype;
    }

    function getHeaderInfo($mid) {

        $headerinfo=imap_headerinfo($this->mbox,$mid);
        $sender=$headerinfo->from[0];


        $header=array(
                      'from'   =>array('name'  =>@$sender->personal,'email' =>strtolower($sender->mailbox).'@'.$sender->host),
                      'subject'=>@$headerinfo->subject,
                      'mid'    =>$headerinfo->message_id);
        return $header;
    }

    function getPart($mid,$mimeType,$encoding=false,$struct=null,$partNumber=false){

        if(!$struct && $mid)
            $struct=@imap_fetchstructure($this->mbox, $mid);
        if($struct && !$struct->ifdparameters && strcasecmp($mimeType,$this->getMimeType($struct))==0){
            $partNumber=$partNumber?$partNumber:1;
            if(($text=imap_fetchbody($this->mbox, $mid, $partNumber))){
                if($struct->encoding==3 or $struct->encoding==4)
                    $text=$this->decode($struct->encoding,$text);

                $charset=null;
                if($encoding) {
                    if($struct->ifparameters){
                        if(!strcasecmp($struct->parameters[0]->attribute,'CHARSET') && strcasecmp($struct->parameters[0]->value,'US-ASCII'))
                            $charset=trim($struct->parameters[0]->value);
                    }
                    $text=$this->mime_encode($text,$charset,$encoding);
                }
                return $text;
            }
        }
        $text='';
        if($struct && $struct->parts){
            foreach($struct->parts as $i => $substruct) {
                if($partNumber)
                    $prefix = $partNumber . '.';
                if(($result=$this->getPart($mid,$mimeType,$encoding,$substruct,$prefix.($i+1))))
                    $text.=$result;
            }
        }
        return $text;
    }

    function getHeader($mid){
        return imap_fetchheader($this->mbox, $mid,FT_PREFETCHTEXT);
    }


    function getPriority($mid){
        return Mail_Parse::parsePriority($this->getHeader($mid));
    }

    function getBody($mid) {

        $body ='';
        if(!($body = $this->getpart($mid,'TEXT/PLAIN',$this->charset))) {
            if(($body = $this->getPart($mid,'TEXT/HTML',$this->charset))) {
                $body=str_replace("</DIV><DIV>", "\n", $body);
                $body=str_replace(array("<br>", "<br />", "<BR>", "<BR />"), "\n", $body);
                $body=Format::striptags($body);
            }
        }
        return $body;
    }

    function createTicket($mid,$emailid=0){
        global $cfg;

        $mailinfo=$this->getHeaderInfo($mid);

        if($mailinfo['mid'] && ($id=Ticket::getIdByMessageId(trim($mailinfo['mid']),$mailinfo['from']['email']))){
            return false;
        }

        $var['name']=$this->mime_decode($mailinfo['from']['name']);
        $var['email']=$mailinfo['from']['email'];
        $var['subject']=$mailinfo['subject']?$this->mime_decode($mailinfo['subject']):'[No Subject]';
        $var['message']=Format::stripEmptyLines($this->getBody($mid));
        $var['header']=$this->getHeader($mid);
        $var['emailId']=$emailid?$emailid:$cfg->getDefaultEmailId();
        $var['name']=$var['name']?$var['name']:$var['email'];
        $var['mid']=$mailinfo['mid'];

        if($cfg->useEmailPriority())
            $var['pri']=$this->getPriority($mid);

        $ticket=null;
        $newticket=true;
        if(preg_match ("[[#][0-9]{1,10}]",$var['subject'],$regs)) {
            $extid=trim(preg_replace("/[^0-9]/", "", $regs[0]));
            $ticket= new Ticket(Ticket::getIdByExtId($extid));

            if(!$ticket || strcasecmp($ticket->getEmail(),$var['email']))
                $ticket=null;
        }

        $errors=array();
        if(!$ticket) {
            if(!($ticket=Ticket::create($var,$errors,'Email')) || $errors)
                return null;
            $msgid=$ticket->getLastMsgId();
        }else{
            $message=$var['message'];

            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()) && strpos($var['message'],$tag))
                list($message)=preg_split('/' . preg_quote($tag, '/') . '/',$var['message']);
            $msgid=$ticket->postMessage($message,'Email',$var['mid'],$var['header']);
        }
        if($msgid && $cfg->allowEmailAttachments()){
            if(($struct = imap_fetchstructure($this->mbox,$mid)) && $struct->parts) {
                if($ticket->getLastMsgId()!=$msgid)
                    $ticket->setLastMsgId($msgid);
                $this->saveAttachments($ticket,$mid,$struct);

            }
        }
        return $ticket;
    }

    function saveAttachments($ticket,$mid,$part,$index=0) {
        global $cfg;

        if($part && $part->ifdparameters && ($filename=$part->dparameters[0]->value)){
            $index=$index?$index:1;
            if($ticket && $cfg->canUploadFileType($filename) && $cfg->getMaxFileSize()>=$part->bytes) {
                $data=$this->decode($part->encoding, imap_fetchbody($this->mbox,$mid,$index));
                $ticket->saveAttachment($filename,$data,$ticket->getLastMsgId(),'M');
                return;
            }
        }

        if($part && $part->parts) {
            foreach($part->parts as $k=>$struct) {
                if($index) $prefix = $index.'.';
                $this->saveAttachments($ticket,$mid,$struct,$prefix.($k+1));
            }
        }

    }

    function fetchTickets($emailid,$max=20,$deletemsgs=false){

        $nummsgs=imap_num_msg($this->mbox);
        $msgs=$errors=0;
        for($i=$nummsgs; $i>0; $i--){
            if($this->createTicket($i,$emailid)){
                imap_setflag_full($this->mbox, imap_uid($this->mbox,$i), "\\Seen", ST_UID);
                if($deletemsgs)
                    imap_delete($this->mbox,$i);
                $msgs++;
                $errors=0;
            }else{
                $errors++;
            }
            if(($max && $msgs>=$max) || $errors>20)
                break;
        }
        @imap_expunge($this->mbox);

        return $msgs;
    }

    static function fetchMail(){
        global $cfg;

        if(!$cfg->canFetchMail())
            return;

        if(!function_exists('imap_open')) {
            $msg='PHP должен быть собран с IMAP расширением для того чтобы работало получение с IMAP/POP3!';
            Sys::log(LOG_WARN,'Ошибка получения почты',$msg);
            return;
        }

        $MAX_ERRORS=5;

        $sql=' SELECT email_id,mail_host,mail_port,mail_protocol,mail_encryption,mail_delete,mail_errors,userid,userpass FROM '.EMAIL_TABLE.
             ' WHERE mail_active=1 AND (mail_errors<='.$MAX_ERRORS.' OR (TIME_TO_SEC(TIMEDIFF(NOW(),mail_lasterror))>5*60) )'.
             ' AND (mail_lastfetch IS NULL OR TIME_TO_SEC(TIMEDIFF(NOW(),mail_lastfetch))>mail_fetchfreq*60) ';
        if(!($accounts=db_query($sql)) || !db_num_rows($accounts))
            return;

        while($row=db_fetch_array($accounts)) {
            $fetcher = new MailFetcher($row['userid'],Misc::decrypt($row['userpass'],SECRET_SALT),
                                       $row['mail_host'],$row['mail_port'],$row['mail_protocol'],$row['mail_encryption']);
            if($fetcher->connect()){
                $fetcher->fetchTickets($row['email_id'],$row['mail_fetchmax'],$row['mail_delete']?true:false);
                $fetcher->close();
                db_query('UPDATE '.EMAIL_TABLE.' SET mail_errors=0, mail_lastfetch=NOW() WHERE email_id='.db_input($row['email_id']));
            }else{
                $errors=$row['mail_errors']+1;
                db_query('UPDATE '.EMAIL_TABLE.' SET mail_errors=mail_errors+1, mail_lasterror=NOW() WHERE email_id='.db_input($row['email_id']));
                if($errors>=$MAX_ERRORS){
                    $msg="\nThe system is having trouble fetching emails from the following mail account: \n".
                        "\nUser: ".$row['userid'].
                        "\nHost: ".$row['mail_host'].
                        "\nError: ".$fetcher->getLastError().
                        "\n\n ".$errors.' consecutive errors. Maximum of '.$MAX_ERRORS. ' allowed'.
                        "\n\n This could be connection issues related to the host. Next delayed login attempt in aprox. 10 minutes";
                    Sys::alertAdmin('Mail Fetch Failure Alert',$msg,true);
                }
            }
        }
    }
}
?>
