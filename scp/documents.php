<?php
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.document.php');
require_once(INCLUDE_DIR.'class.dept.php');

if(!$thisuser->canManageKb() && !$thisuser->isadmin()) die('Доступ запрещён');

$page='';
$document=null;

if(($id=($_REQUEST['id'] ?? null)?$_REQUEST['id']:($_POST['id'] ?? null)) && is_numeric($id)) {
    $document = Document::lookup($id);
    if(!$document)
        $errors['err']='Документ не найден #'.$id;
    elseif(($_REQUEST['a'] ?? '')!='add')
        $page='document.inc.php';
}

if($_POST):
    $errors=array();
    if (!Misc::validateCSRFToken($_POST['csrf_token'])) {
        $errors['err'] = 'Ошибка проверки безопасности. Попробуйте снова.';
    } else {
    switch(strtolower($_POST['a'])):
    case 'update':
    case 'add':
        if(!$_POST['id'] && $_POST['a']=='update')
            $errors['err']='Отсутствует ID документа';

        $data = array(
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'doc_type' => $_POST['doc_type'],
            'external_url' => $_POST['external_url'],
            'audience' => $_POST['audience'],
            'dept_id' => $_POST['dept_id'],
            'isenabled' => $_POST['isenabled'],
            'staff_id' => $thisuser->getId()
        );

        if($_POST['doc_type']=='file' && isset($_FILES['doc_file']) && $_FILES['doc_file']['tmp_name']) {
            $data['file'] = $_FILES['doc_file'];
        }

        if(!$errors){
            if($_POST['a']=='add'){
                if($_POST['doc_type']=='file' && (!isset($_FILES['doc_file']) || !$_FILES['doc_file']['tmp_name'])) {
                    $data['file'] = null;
                }
                $docId = Document::create($data, $errors);
                if($docId)
                    $msg='Документ успешно создан';
            }elseif($_POST['a']=='update'){
                if(Document::update($_POST['id'], $data, $errors)){
                    $msg='Документ успешно обновлен';
                    $document = Document::lookup($_POST['id']);
                }
            }
        }

        if($errors && !$errors['err'])
            $errors['err']='Исправьте ошибки и попробуйте снова';

        break;
    case 'process':
        if(!$_POST['docs'] || !is_array($_POST['docs']))
            $errors['err']='Выберите хотя бы один документ';
        else{
            $msg='';
            $ids=implode(',', array_map('intval', $_POST['docs']));
            $selected=count($_POST['docs']);
            if(isset($_POST['enable'])) {
                if(db_query('UPDATE '.KB_DOCUMENTS_TABLE.' SET isenabled=1, updated=NOW() WHERE isenabled=0 AND doc_id IN('.$ids.')'))
                    $msg=db_affected_rows()." из $selected документов включено";
            }elseif(isset($_POST['disable'])) {
                if(db_query('UPDATE '.KB_DOCUMENTS_TABLE.' SET isenabled=0, updated=NOW() WHERE isenabled=1 AND doc_id IN('.$ids.')'))
                    $msg=db_affected_rows()." из $selected документов отключено";
            }elseif(isset($_POST['delete'])) {
                $count=0;
                foreach($_POST['docs'] as $docId) {
                    if(Document::delete(intval($docId)))
                        $count++;
                }
                $msg="$count из $selected документов удалено";
            }

            if(!$msg)
                $errors['err']='Ошибка выполнения. Попробуйте снова.';
        }
        break;
    default:
        $errors['err']='Неизвестное действие';
    endswitch;
    }
endif;

if(!$page && ($_REQUEST['a'] ?? '')==='add' && !isset($docId))
    $page='document.inc.php';

$inc=$page?$page:'documents.inc.php';

$nav->setTabActive('kbase');
$nav->addSubMenu(array('desc'=>'Готовые Ответы','href'=>'kb.php','iconclass'=>'premade'));
$nav->addSubMenu(array('desc'=>'Документация','href'=>'documents.php','iconclass'=>'documents'));
require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');

?>
