#!/usr/bin/php -q
<?php
@chdir(realpath(dirname(__FILE__)).'/');
ini_set('memory_limit', '256M');
require('api.inc.php');
require_once(INCLUDE_DIR.'class.mailparse.php');
require_once(INCLUDE_DIR.'class.email.php');

if(!$cfg->enableEmailPiping())
    api_exit(EX_UNAVAILABLE,'Email piping not enabled - check MTA settings.');

$data=isset($_SERVER['HTTP_HOST'])?file_get_contents('php://input'):file_get_contents('php://stdin');
if(empty($data)){
    api_exit(EX_NOINPUT,'No data');
}

$parser= new Mail_Parse($data);
if(!$parser->decode()){
    api_exit(EX_DATAERR,'Email parse failed ['.$parser->getError().']');
}



$fromlist = $parser->getFromAddressList();
if(!$fromlist || $fromlist === false){
    api_exit(EX_DATAERR,'Invalid FROM address');
}

$from=$fromlist[0];
foreach($fromlist as $fromobj){
    if(!Validator::is_email($fromobj->mailbox.'@'.$fromobj->host))
        continue;
    $from=$fromobj;
    break;
}

$tolist = $parser->getToAddressList() ?: [];
foreach ($tolist as $toaddr){
    if(($emailId=Email::getIdByEmail($toaddr->mailbox.'@'.$toaddr->host))){
        break;
    }
}
if(!$emailId && ($cclist=$parser->getCcAddressList())) {
    foreach ($cclist as $ccaddr){
        if(($emailId=Email::getIdByEmail($ccaddr->mailbox.'@'.$ccaddr->host))){
            break;
        }
    }
}

require_once(INCLUDE_DIR.'class.ticket.php');

$var=array();
$deptId=0;
$name=trim($from->personal,'"');
if($from->comment && $from->comment[0])
    $name.=' ('.$from->comment[0].')';
$subj=mb_convert_encoding($parser->getSubject(), 'UTF-8', 'ISO-8859-1');
if(!($body=Format::stripEmptyLines($parser->getBody())) && $subj)
    $body=$subj;

$var['mid']=$parser->getMessageId();
$var['email']=$from->mailbox.'@'.$from->host;
$var['name']=$name?mb_convert_encoding($name, 'UTF-8', 'ISO-8859-1'):$var['email'];
$var['emailId']=$emailId?$emailId:$cfg->getDefaultEmailId();
$var['subject']=$subj?$subj:'[No Subject]';
$var['message']=mb_convert_encoding(Format::stripEmptyLines($body), 'UTF-8', 'ISO-8859-1');
$var['header']=$parser->getHeader();
$var['pri']=$cfg->useEmailPriority()?$parser->getPriority():0;

$ticket=null;
if(preg_match ("[[#][0-9]{1,10}]",$var['subject'],$regs)) {
    $extid=trim(preg_replace("/[^0-9]/", "", $regs[0]));
    $ticket= new Ticket(Ticket::getIdByExtId($extid));
    if(!is_object($ticket) || strcasecmp($ticket->getEmail(),$var['email']))
        $ticket=null;
}
$errors=array();
$msgid=0;
if(!$ticket){
    $ticket=Ticket::create($var,$errors,'email');
    if(!is_object($ticket) || $errors){
        api_exit(EX_DATAERR,'Ticket create Failed '.implode("\n",$errors)."\n\n");
    }
    $msgid=$ticket->getLastMsgId();
}else{
    $message=$var['message'];
    if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()) && strpos($var['message'],$tag))
        list($message)=preg_split('/' . preg_quote($tag, '/') . '/',$var['message']);
    if(!($msgid=$ticket->postMessage($message,'Email',$var['mid'],$var['header']))) {
        api_exit(EX_DATAERR,"Unable to post message \n\n $message\n");
    }
}

if($cfg->allowEmailAttachments()) {
    if($attachments=$parser->getAttachments()){
        foreach($attachments as $k=>$attachment){
            if($attachment['filename'] && $cfg->canUploadFileType($attachment['filename'])) {
                $ticket->saveAttachment($attachment['filename'],$attachment['body'],$msgid,'M');
            }
        }
    }
}
api_exit(EX_SUCCESS);
?>
