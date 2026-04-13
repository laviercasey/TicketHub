<?php
class Validator {

    public $input=array();
    public $fields=array();
    public $errors=array();

    public function __construct($fields=null) {
        $this->setFields($fields);
    }
    function setFields(&$fields){

        if($fields && is_array($fields)):
            $this->fields=$fields;
            return (true);
        endif;

        return (false);
    }

    function validate($source,$userinput=true){

        $this->errors=array();
        if(!$source || !is_array($source))
            $this->errors['err']='Invalid input';
        elseif(!$this->fields || !is_array($this->fields))
            $this->errors['err']='No fields setup';
        if($this->errors)
            return false;

        $this->input=$source;

        foreach($this->fields as $k=>$field){
            if(!$field['required'] && !$this->input[$k])
                continue;

            if($field['required'] && !isset($this->input[$k]) || (!$this->input[$k] && $field['type']!='int')){
                $this->errors[$k]=$field['error'];
                continue;
            }
            switch(strtolower($field['type'])):
            case 'integer':
            case 'int':
                if(!is_numeric($this->input[$k]))
                     $this->errors[$k]=$field['error'];
                break;
            case 'double':
                if(!is_numeric($this->input[$k]))
                    $this->errors[$k]=$field['error'];
            break;
            case 'text':
            case 'string':
                break;
            case 'array':
                if(!$this->input[$k] || !is_array($this->input[$k]))
                    $this->errors[$k]=$field['error'];
                break;
            case 'radio':
            if(!isset($this->input[$k]))
               $this->errors[$k]=$field['error'];
            break;
            case 'date':
                if(strtotime($this->input[$k])===false)
                    $this->errors[$k]=$field['error'];
                break;
            case 'time':
                break;
            case 'phone':
            case 'fax':
                if(!$this->is_phone($this->input[$k]))
                    $this->errors[$k]=$field['error'];
                break;
            case 'email':
                if(!$this->is_email($this->input[$k]))
                    $this->errors[$k]=$field['error'];
                break;
            case 'url':
                if(!$this->is_url($this->input[$k]))
                    $this->errors[$k]=$field['error'];
                break;
            case 'password':
                if(strlen($this->input[$k])<12)
                    $this->errors[$k]=$field['error'].' (12 chars min)';
                break;
            case 'username':
                if(strlen($this->input[$k])<3)
                    $this->errors[$k]=$field['error'].' (3 chars min)';
                break;
            case 'zipcode':
                if(!is_numeric($this->input[$k]) || (strlen($this->input[$k])!=5))
                    $this->errors[$k]=$field['error'];
                break;
            default:
                $this->errors[$k]=$field['error'].' (type not set)';
            endswitch;
        }
        return ($this->errors)?(FALSE):(TRUE);
    }

    function iserror(){
        return $this->errors?true:false;
    }

    function errors(){
        return $this->errors;
    }

    static function is_email($email) {
        return (preg_match('/^([*+!.&#$|\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i',trim(stripslashes($email))));
    }
    static function is_phone($phone) {
        $stripped=preg_replace("(\(|\)|\-|\+|[  ]+)","",$phone);
        return (!is_numeric($stripped) || ((strlen($stripped)<7) || (strlen($stripped)>16)))?false:true;
    }

    static function is_url($url) {

        $urlregex = "^(https?)\:\/\/";
        $urlregex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
        $urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*";
        $urlregex .= "(\:[0-9]{2,5})?";
        $urlregex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?";
        $urlregex .= "(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?";
        $urlregex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?\$";

        return preg_match('#' . $urlregex . '#i', $url)?true:false;
    }

    static function is_ip($ip) {

        if(!$ip or empty($ip))
            return false;

        $ip=trim($ip);
        if(preg_match("/^[0-9]{1,3}(.[0-9]{1,3}){3}$/",$ip)) {
            foreach(explode(".", $ip) as $block)
                if($block<0 || $block>255 )
                    return false;
            return true;
        }
        return false;
    }
}
?>
