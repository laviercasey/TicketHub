<?php

use TicketHub\Mail\SymfonyMimeParser;
use TicketHub\Mail\MailParseException;

class Mail_Parse {

    public $mime_message;
    public $include_bodies;
    public $decode_headers;
    public $decode_bodies;

    public $struct;

    public $header;

    private ?SymfonyMimeParser $parser = null;
    private string $lastError = '';

    public function __construct(string $mimeMessage='', bool $includeBodies=true, bool $decodeHeaders=true, bool $decodeBodies=true){
        $this->mime_message=$mimeMessage;
        $this->include_bodies=$includeBodies;
        $this->decode_headers=$decodeHeaders;
        $this->decode_bodies=$decodeBodies;
    }

    function decode() {

        $this->parser = new SymfonyMimeParser();
        $this->lastError = '';
        $this->splitBodyHeader();

        $result = $this->parser->decode(
            $this->mime_message,
            $this->include_bodies,
            $this->decode_headers,
            $this->decode_bodies,
        );

        if ($result === false) {
            $this->lastError = 'Failed to decode MIME message';
            $this->struct = false;
            return false;
        }

        $this->struct = $result;

        return (is_object($this->struct) && isset($this->struct->headers) && count($this->struct->headers) > 1) ? true : false;
    }

    function splitBodyHeader() {

        if (preg_match("/^(.*?)\r?\n\r?\n(.*)/s",$this->mime_message, $match)) {
            $this->header=$match[1];
        }
    }

    function getStruct(){
        return $this->struct;
    }

    function getHeader() {
        if(!$this->header) $this->splitBodyHeader();

        return $this->header;
    }

    function getError(){
        return $this->lastError;
    }

    function getFromAddressList(){
        if(!$this->struct || !isset($this->struct->headers)) return false;
        return Mail_Parse::parseAddressList($this->struct->headers['from']);
    }

    function getToAddressList(){
        if(!$this->struct || !isset($this->struct->headers)) return false;
       return Mail_Parse::parseAddressList($this->struct->headers['to']?$this->struct->headers['to']:$this->struct->headers['delivered-to']);
    }

    function getCcAddressList(){
        if(!$this->struct || !isset($this->struct->headers)) return null;
        return $this->struct->headers['cc']?Mail_Parse::parseAddressList($this->struct->headers['cc']):null;
    }

    function getMessageId(){
        if(!$this->struct || !isset($this->struct->headers)) return null;
        return $this->struct->headers['message-id'];
    }

    function getSubject(){
        if(!$this->struct || !isset($this->struct->headers)) return null;
        return $this->struct->headers['subject'];
    }

    function getBody(){

        $body='';
        if(!($body=$this->getPart($this->struct,'text/plain'))) {
            if(($body=$this->getPart($this->struct,'text/html'))) {
                $body=str_replace("</DIV><DIV>", "\n", $body);
                $body=str_replace(array("<br>", "<br />", "<BR>", "<BR />"), "\n", $body);
                $body=Format::striptags($body);
            }
        }
        return $body;
    }

    function getPart($struct,$ctypepart) {

        if($struct && !$struct->parts) {
            $ctype = @strtolower($struct->ctype_primary.'/'.$struct->ctype_secondary);
            if($ctype && strcasecmp($ctype,$ctypepart)==0)
                return $struct->body;
        }

        $data='';
        if($struct && $struct->parts) {
            foreach($struct->parts as $i=>$part) {
                if($part && !$part->disposition && ($text=$this->getPart($part,$ctypepart)))
                    $data.=$text;
            }
        }
        return $data;
    }

    function getAttachments($part=null){

        if($part==null)
            $part=$this->getStruct();

        if($part && $part->disposition
                && (!strcasecmp($part->disposition,'attachment')
                    || !strcasecmp($part->disposition,'inline')
                    || !strcasecmp($part->ctype_primary,'image'))){
            if(!($filename=$part->d_parameters['filename']) && $part->d_parameters['filename*'])
                $filename=$part->d_parameters['filename*'];

            return array(array('filename'=>$filename,'body'=>$part->body));
        }

        $files=array();
        if($part->parts){
            foreach($part->parts as $k=>$p){
                if($p && ($result=$this->getAttachments($p))) {
                    $files=array_merge($files,$result);
                }
            }
        }

        return $files;
    }

    function getPriority(){
        return Mail_Parse::parsePriority($this->getHeader());
    }

    function parsePriority($header=null){

        $priority=0;
        if($header && ($begin=strpos($header,'X-Priority:'))!==false){
            $begin+=strlen('X-Priority:');
            $xpriority=preg_replace("/[^0-9]/", "",substr($header, $begin, strpos($header,"\n",$begin) - $begin));
            if(!is_numeric($xpriority))
                $priority=0;
            elseif($xpriority>4)
                $priority=1;
            elseif($xpriority>=3)
                $priority=2;
            elseif($xpriority>0)
                $priority=3;
        }
        return $priority;
    }

    function parseAddressList($address){
        $parser = new SymfonyMimeParser();
        try {
            return $parser->parseAddressList($address);
        } catch (MailParseException) {
            return false;
        }
    }
}
