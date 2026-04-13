<?php
class Dept {
    public $id;
    public $name;
    public $deptname;
    public $signature;

    public $tplId;

    public $emailId;
    public $email;

    public $autorespEmail;

    public $managerId;
    public $manager;

    public $row;

    public function __construct($id=0){
        $this->id=0;
        if($id && ($info=$this->getInfoById($id))){
            $this->row=$info;
            $this->id=$info['dept_id'];
            $this->tplId=$info['tpl_id'];
            $this->emailId=$info['email_id'];
            $this->managerId=$info['manager_id'];
            $this->deptname=$info['dept_name'];
            $this->signature=$info['dept_signature'];
            $this->getEmail();
        }
    }

    function getId(){
        return $this->id;
    }

    function getName(){
        return $this->deptname;
    }

    function getEmailId(){
        return $this->emailId;
    }

    function getEmail(){

        if(!$this->email && $this->emailId)
            $this->email= new Email($this->emailId);

        return $this->email;
    }

    function getTemplateId() {
         return $this->tplId;
    }

    function getAutoRespEmail() {

        if(!$this->autorespEmail && $this->row['autoresp_email_id'])
            $this->autorespEmail= new Email($this->row['autoresp_email_id']);
        else
            $this->autorespEmail= $this->getEmail();

        return $this->autorespEmail;
    }

    function getEmailAddress() {
        return $this->email?$this->email->getAddress():null;
    }

    function getSignature() {

        return $this->signature;
    }

    function canAppendSignature() {
        return ($this->signature && $this->row['can_append_signature'])?true:false;
    }

    function getManagerId(){
        return $this->managerId;
    }

    function getManager(){

        if(!$this->manager && $this->managerId)
            $this->manager= new Staff($this->managerId);

        return $this->manager;
    }

    function isPublic() {
         return $this->row['ispublic']?true:false;
    }

    function autoRespONNewTicket() {
        return $this->row['ticket_auto_response']?true:false;
    }

    function autoRespONNewMessage() {
        return $this->row['message_auto_response']?true:false;
    }

    function noreplyAutoResp(){
         return $this->row['noreply_autoresp']?true:false;
    }

    function getInfo() {
        return $this->row;
    }

    function update($vars,&$errors) {
        if($this->save($this->getId(),$vars,$errors)){
            return true;
        }
        return false;
    }

	static function getInfoById($id) {
		$sql='SELECT * FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id);
		if(($res=db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);

        return null;
	}

	static function getIdByName($name) {
        $id=0;
        $sql ='SELECT dept_id FROM '.DEPT_TABLE.' WHERE dept_name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    static function getIdByEmail($email) {
        $id=0;
        $sql ='SELECT dept_id FROM '.DEPT_TABLE.' WHERE dept_email='.db_input($email);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    static function getNameById($id) {
        $sql ='SELECT dept_name FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($name)=db_fetch_row($res);
        return $name;
    }

    static function getDefaultDeptName() {
        global $cfg;
        return Dept::getNameById($cfg->getDefaultDeptId());
    }

    static function create($vars,&$errors) {
        return Dept::save(0,$vars,$errors);
    }

    static function delete($id) {
        global $cfg;
        if($id==$cfg->getDefaultDeptId())
            return 0;

        $sql='DELETE FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id);
        if(db_query($sql) && ($num=db_affected_rows())){
            db_query('UPDATE '.TICKET_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            db_query('UPDATE '.STAFF_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            db_query('UPDATE '.TOPIC_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            return $num;
        }
        return 0;

    }

    static function save($id,$vars,&$errors) {
        global $cfg;

        if($id && $id!=($_POST['dept_id'] ?? ''))
            $errors['err']='Missing or invalid Dept ID';

        if(empty($_POST['email_id']) || !is_numeric($_POST['email_id']))
            $errors['email_id']='Dept email required';

        if(!is_numeric($_POST['tpl_id'] ?? ''))
            $errors['tpl_id']='Template required';

        if(empty($_POST['dept_name'])) {
            $errors['dept_name']='Dept name required';
        }elseif(strlen($_POST['dept_name'])<4) {
            $errors['dept_name']='Dept name must be at least 4 chars.';
        }else{
            $sql='SELECT dept_id FROM '.DEPT_TABLE.' WHERE dept_name='.db_input($_POST['dept_name']);
            if($id)
                $sql.=' AND dept_id!='.db_input($id);

            if(db_num_rows(db_query($sql)))
                $errors['dept_name']='Department already exist';
        }

        if(!empty($_POST['ispublic']) && empty($_POST['dept_signature']))
            $errors['dept_signature']='Signature required';

        if(empty($_POST['ispublic']) && (($_POST['dept_id'] ?? '')==$cfg->getDefaultDeptId()))
            $errors['ispublic']='Default department can not be private';

        if(!$errors){

            $sql=' SET updated=NOW() '.
                 ',ispublic='.db_input($_POST['ispublic'] ?? 0).
                 ',email_id='.db_input($_POST['email_id'] ?? 0).
                 ',tpl_id='.db_input($_POST['tpl_id'] ?? 0).
                 ',autoresp_email_id='.db_input($_POST['autoresp_email_id'] ?? 0).
                 ',manager_id='.db_input(!empty($_POST['manager_id']) ? $_POST['manager_id'] : 0).
                 ',dept_name='.db_input(Format::striptags($_POST['dept_name'] ?? '')).
                 ',dept_signature='.db_input(Format::striptags($_POST['dept_signature'] ?? '')).
                 ',ticket_auto_response='.db_input($_POST['ticket_auto_response'] ?? 0).
                 ',message_auto_response='.db_input($_POST['message_auto_response'] ?? 0).
                 ',can_append_signature='.db_input(isset($_POST['can_append_signature'])?1:0);

            if($id) {
                $sql='UPDATE '.DEPT_TABLE.' '.$sql.' WHERE dept_id='.db_input($id);
                if(!db_query($sql) || !db_affected_rows())
                    $errors['err']='Unable to update '.Format::input($_POST['dept_name']).' Dept. Error occured';
            }else{
                $sql='INSERT INTO '.DEPT_TABLE.' '.$sql.',created=NOW()';
                if(db_query($sql) && ($deptID=db_insert_id()))
                    return $deptID;

                $errors['err']='Unable to create department. Internal error';
            }
        }

        return $errors?false:true;
    }
}
?>
