<?php
class Topic {
    public $id;
    public $topic;
    public $dept_id;
    public $priority_id;
    public $autoresp;

    public $info;

    public function __construct($id,$fetch=true){
        $this->id=$id;
        if($fetch)
            $this->load();
    }

    function load() {

        if(!$this->id)
            return false;

        $sql='SELECT * FROM '.TOPIC_TABLE.' WHERE topic_id='.db_input($this->id);
        if(($res=db_query($sql)) && db_num_rows($res)) {
            $info=db_fetch_array($res);
            $this->id=$info['topic_id'];
            $this->topic=$info['topic'];
            $this->dept_id=$info['dept_id'];
            $this->priority_id=$info['priority_id'];
            $this->active=$info['isactive'];
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

    function getName(){
        return $this->topic;
    }

    function getDeptId() {
        return $this->dept_id;
    }

    function getPriorityId() {
        return $this->priority_id;
    }

    function autoRespond() {
        return $this->autoresp;
    }

    function isEnabled() {
         return $this->active?true:false;
    }

    function isActive(){
        return $this->isEnabled();
    }

    function getInfo() {
        return $this->info;
    }

    function update($vars,&$errors) {
        if($this->save($this->getId(),$vars,$errors)){
            $this->reload();
            return true;
        }
        return false;
    }

    static function create($vars,&$errors) {
        return Topic::save(0,$vars,$errors);
    }

    static function save($id,$vars,&$errors) {

        if($id && $id!=$vars['topic_id'])
            $errors['err']='Internal error. Try again';

        if(!$vars['topic'])
            $errors['topic']='Help topic required';
        elseif(strlen($vars['topic'])<5)
            $errors['topic']='Topic is too short. 5 chars minimum';

        if(!$vars['dept_id'])
            $errors['dept_id']='You must select a department';

        if(!$vars['priority_id'])
            $errors['priority_id']='You must select a priority';

        if(!$errors) {
            $sql='updated=NOW(),topic='.db_input(Format::striptags($vars['topic'])).
                 ',dept_id='.db_input($vars['dept_id']).
                 ',priority_id='.db_input($vars['priority_id']).
                 ',isactive='.db_input($vars['isactive']).
                 ',noautoresp='.db_input(isset($vars['noautoresp'])?1:0);
            if($id) {
                $sql='UPDATE '.TOPIC_TABLE.' SET '.$sql.' WHERE topic_id='.db_input($id);
                if(!db_query($sql) || !db_affected_rows())
                    $errors['err']='Unable to update topic. Internal error occured';
            }else{
                $sql='INSERT INTO '.TOPIC_TABLE.' SET '.$sql.',created=NOW()';
                if(!db_query($sql) or !($topicID=db_insert_id()))
                    $errors['err']='Unable to create the topic. Internal error';
                else
                    return $topicID;
            }
        }

        return $errors?false:true;
    }
}
?>
