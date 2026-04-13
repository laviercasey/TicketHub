<?php
require('staff.inc.php');
if(!$thisuser->canManageKb() && !$thisuser->isadmin()) die('Access denied');

$page='';
$answer=null; //clean start.
if(($id=($_REQUEST['id'] ?? null)?$_REQUEST['id']:($_POST['id'] ?? null)) && is_numeric($id)) {
    $replyID=0;
    $resp=db_query('SELECT * FROM '.KB_PREMADE_TABLE.' WHERE premade_id='.db_input($id));
    if($resp && db_num_rows($resp))
        $answer=db_fetch_array($resp);
    else
        $errors['err']='Unknown ID#'.$id; //Sucker...invalid id
    
    if(!$errors && $answer['premade_id']==$id)
        $page='reply.inc.php';
}

if($_POST):
    $errors=array();
    if(!Misc::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['err']='Security error. Please try again.';
    }
    switch(strtolower($_POST['a'])):
    case 'update':
    case 'add':
        if(!$_POST['id'] && $_POST['a']=='update')
            $errors['err']='Missing or invalid group ID';

        if(!$_POST['title'])
            $errors['title']='Title/subject required';
                
        if(!$_POST['answer'])
            $errors['answer']='Reply required';

        if(!$errors){
            $sql=' SET updated=NOW(),isenabled='.db_input($_POST['isenabled']).
                 ', dept_id='.db_input($_POST['dept_id']).
                 ', title='.db_input(Format::striptags($_POST['title'])).
                 ', answer='.db_input(Format::striptags($_POST['answer']));
            if($_POST['a']=='add'){ //create
                $res=db_query('INSERT INTO '.KB_PREMADE_TABLE.' '.$sql.',created=NOW()');
                if(!$res or !($replyID=db_insert_id()))
                    $errors['err']='Unable to create the reply. Internal error';
                else
                    $msg='Premade reply created';
            }elseif($_POST['a']=='update'){ //update
                $res=db_query('UPDATE '.KB_PREMADE_TABLE.' '.$sql.' WHERE premade_id='.db_input($_POST['id']));
                if($res && db_affected_rows()){
                    $msg='Premade reply updated';
                    $answer=db_fetch_array(db_query('SELECT * FROM '.KB_PREMADE_TABLE.' WHERE premade_id='.db_input($id)));
                }
                else
                    $errors['err']='Internal update error occured. Try again';
            }
            if($errors['err'] && db_errno()==1062)
                $errors['title']='Title already exists!';
            
        }else{
            $errors['err']=$errors['err']?$errors['err']:'Error(s) occured. Try again';
        }
        break;
    case 'process':
        if(!$_POST['canned'] || !is_array($_POST['canned']))
            $errors['err']='You must select at least one item';
        else{
            $msg='';
            $ids=implode(',', array_map('intval', $_POST['canned']));
            $selected=count($_POST['canned']);
            if(isset($_POST['enable'])) {
                if(db_query('UPDATE '.KB_PREMADE_TABLE.' SET isenabled=1,updated=NOW() WHERE isenabled=0 AND premade_id IN('.$ids.')'))
                    $msg=db_affected_rows()." of  $selected selected replies enabled";
            }elseif(isset($_POST['disable'])) {
                if(db_query('UPDATE '.KB_PREMADE_TABLE.' SET isenabled=0, updated=NOW() WHERE isenabled=1 AND premade_id IN('.$ids.')'))
                    $msg=db_affected_rows()." of  $selected selected replies disabled";
            }elseif(isset($_POST['delete'])) {
                if(db_query('DELETE FROM '.KB_PREMADE_TABLE.' WHERE premade_id IN('.$ids.')'))
                    $msg=db_affected_rows()." of  $selected selected replies deleted";
            }

            if(!$msg)
                $errors['err']='Error occured. Try again';
        }
        break;
    default:
        $errors['err']='Unknown action';
    endswitch;
endif;

if(!$page && ($_REQUEST['a'] ?? '')==='add' && empty($replyID))
    $page='reply.inc.php';

    $inc=$page?$page:'premade.inc.php';

$nav->setTabActive('kbase');
$nav->addSubMenu(array('desc'=>'Готовые Ответы','href'=>'kb.php','iconclass'=>'premade'));
$nav->addSubMenu(array('desc'=>'Документация','href'=>'documents.php','iconclass'=>'documents'));
require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');

?>
