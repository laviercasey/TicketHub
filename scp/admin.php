<?php
require('staff.inc.php');
if(!$thisuser or !$thisuser->isadmin()){
    header('Location: index.php');
    require('index.php');
    exit;
}



if(defined('THIS_VERSION') && strcasecmp($cfg->getVersion(),THIS_VERSION)) {
    $sysnotice=sprintf('Версия скрипта %s, а версия базы данных %s.',THIS_VERSION,$cfg->getVersion());
    $errors['err']=$sysnotice;
}elseif(!$cfg->isHelpDeskOffline()) {

}


define('OSTADMININC',TRUE);
define('ADMINPAGE',TRUE);

require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.email.php');
require_once(INCLUDE_DIR.'class.mailfetch.php');

if($_POST && !empty($_POST['t']) && !$errors):
    $errors=array();

    if(!Misc::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['err']='Неверная отправка формы. Попробуйте снова.';
    } else {

    switch(strtolower($_POST['t'])):
        case 'pref':
            if($cfg->updatePref($_POST,$errors)){
                $msg='Настройки Успешно Обновлены';
                $cfg->reload();
            }else{
                $errors['err']=!empty($errors['err'])?$errors['err']:'Внутренняя ошибка';
            }
            break;
        case 'attach':
            if($_POST['allow_attachments'] or $_POST['upload_dir']) {

                if($_POST['upload_dir'])
                    $_POST['upload_dir'] = realpath($_POST['upload_dir']);

                if(!$_POST['upload_dir'] or !is_writable($_POST['upload_dir'])) {
                    $errors['upload_dir']='Каталог должен быть действительным и доступным для записи';
                    if($_POST['allow_attachments'])
                        $errors['allow_attachments']='Неверный каталог загрузки';
                }elseif(!ini_get('file_uploads')) {
                    $errors['allow_attachments']='Директива \'file_uploads\' отключена в php.ini';
                }

                if(!is_numeric($_POST['max_file_size']))
                    $errors['max_file_size']='Необходимо указать максимальный размер файла';

                if(!$_POST['allowed_filetypes'])
                    $errors['allowed_filetypes']='Необходимо указать допустимые расширения файлов';
            }
            if(!$errors) {
               $sql= 'UPDATE '.CONFIG_TABLE.' SET allow_attachments='.db_input(isset($_POST['allow_attachments'])?1:0).
                    ',upload_dir='.db_input($_POST['upload_dir']).
                    ',max_file_size='.db_input($_POST['max_file_size']).
                    ',allowed_filetypes='.db_input(strtolower(preg_replace("/\n\r|\r\n|\n|\r/", '',trim($_POST['allowed_filetypes'])))).
                    ',email_attachments='.db_input(isset($_POST['email_attachments'])?1:0).
                    ',allow_email_attachments='.db_input(isset($_POST['allow_email_attachments'])?1:0).
                    ',allow_online_attachments='.db_input(isset($_POST['allow_online_attachments'])?1:0).
                    ',allow_online_attachments_onlogin='.db_input(isset($_POST['allow_online_attachments_onlogin'])?1:0).
                    ' WHERE id='.$cfg->getId();
               if(db_query($sql)) {
                   $cfg->reload();
                   $msg='Настройки вложений обновлены';
               }else{
                    $errors['err']='Ошибка обновления!';
               }
            }else {
                $errors['err']='Произошла ошибка. Смотрите сообщения ниже.';

            }
            break;
        case 'api':
            $api_do = strtolower($_POST['do'] ?? '');
            switch($api_do) {
                case 'create_token':
                    require_once(INCLUDE_DIR.'class.apitoken.php');
                    $tk_name = trim($_POST['name'] ?? '');
                    $tk_desc = trim($_POST['description'] ?? '');
                    $tk_type = !empty($_POST['token_type']) ? $_POST['token_type'] : 'permanent';
                    $tk_rate = (int)($_POST['rate_limit'] ?? 1000);
                    if ($tk_rate <= 0) $tk_rate = 1000;
                    $tk_perms = isset($_POST['permissions']) ? $_POST['permissions'] : array();
                    $tk_expires = (int)($_POST['expires_days'] ?? 30);

                    if (!$tk_name) $errors['name'] = 'Необходимо указать имя токена';
                    if (empty($tk_perms)) $errors['permissions'] = 'Выберите хотя бы одно разрешение';

                    if (!$errors) {
                        $tk_value = bin2hex(random_bytes(32));
                        $tk_hash = hash('sha256', $tk_value);
                        $tk_exp_sql = 'NULL';
                        if ($tk_type == 'temporary' && $tk_expires > 0)
                            $tk_exp_sql = "DATE_ADD(NOW(), INTERVAL ".(int)$tk_expires." DAY)";

                        $sql = sprintf(
                            "INSERT INTO %s (token, name, description, staff_id, token_type, permissions, rate_limit, is_active, expires_at, created_at, updated_at)
                             VALUES (%s, %s, %s, %d, %s, %s, %d, 1, %s, NOW(), NOW())",
                            API_TOKEN_TABLE, db_input($tk_hash), db_input($tk_name), db_input($tk_desc),
                            db_input($thisuser->getId()), db_input($tk_type), db_input(json_encode($tk_perms)),
                            db_input($tk_rate), $tk_exp_sql
                        );
                        if (db_query($sql)) {
                            $_SESSION['new_token_value'] = $tk_value;
                            $_SESSION['api_msg'] = 'Токен успешно создан. Скопируйте его сейчас — он больше не будет показан!';
                            header('Location: admin.php?t=api');
                            exit;
                        } else {
                            $errors['err'] = 'Не удалось создать токен: ' . db_error();
                        }
                    }
                    break;
                case 'toggle_token':
                    require_once(INCLUDE_DIR.'class.apitoken.php');
                    $tk_id = (int)$_POST['token_id'];
                    $tk_status = (int)$_POST['new_status'];
                    db_query(sprintf("UPDATE %s SET is_active=%d, updated_at=NOW() WHERE token_id=%d",
                        API_TOKEN_TABLE, $tk_status ? 1 : 0, db_input($tk_id)));
                    $_SESSION['api_msg'] = $tk_status ? 'Токен активирован' : 'Токен деактивирован';
                    header('Location: admin.php?t=api');
                    exit;
                case 'delete_token':
                    require_once(INCLUDE_DIR.'class.apitoken.php');
                    $tk_id = (int)$_POST['token_id'];
                    db_query(sprintf("DELETE FROM %s WHERE token_id=%d", API_TOKEN_TABLE, db_input($tk_id)));
                    $_SESSION['api_msg'] = 'Токен удалён';
                    header('Location: admin.php?t=api');
                    exit;
                case 'mass_process':
                    $ids_arr = $_POST['ids'] ?? null;
                    if(!$ids_arr || !is_array($ids_arr)) {
                        $errors['err']='Необходимо выбрать хотя бы одну запись для обработки';
                    }else{
                        require_once(INCLUDE_DIR.'class.apitoken.php');
                        $ids=implode(',', array_map('intval', $ids_arr));
                        if(!empty($_POST['enable'])) {
                            db_query("UPDATE ".API_TOKEN_TABLE." SET is_active=1, updated_at=NOW() WHERE token_id IN ($ids)");
                            $_SESSION['api_msg'] = count($ids_arr).' токен(ов) включено';
                        } elseif(!empty($_POST['disable'])) {
                            db_query("UPDATE ".API_TOKEN_TABLE." SET is_active=0, updated_at=NOW() WHERE token_id IN ($ids)");
                            $_SESSION['api_msg'] = count($ids_arr).' токен(ов) отключено';
                        } elseif(!empty($_POST['delete'])) {
                            db_query("DELETE FROM ".API_TOKEN_TABLE." WHERE token_id IN ($ids)");
                            $_SESSION['api_msg'] = count($ids_arr).' токен(ов) удалено';
                        }
                        header('Location: admin.php?t=api');
                        exit;
                    }
                    break;
                default:
                    $errors['err']='Неизвестное действие '.Format::htmlchars($_POST['do'] ?? '');
            }
            break;
        case 'banlist':
            require_once(INCLUDE_DIR.'class.banlist.php');
            switch(strtolower($_POST['a'])) {
                case 'add':
                    if(!$_POST['email'] || !Validator::is_email($_POST['email']))
                        $errors['err']='Введите корректный email.';
                    elseif(BanList::isbanned($_POST['email']))
                        $errors['err']='Email уже заблокирован';
                    else{
                        if(BanList::add($_POST['email'],$thisuser->getName()))
                            $msg='Email добавлен в банлист';
                        else
                            $errors['err']='Невозможно добавить email в чёрный список. Попробуйте снова';
                    }
                    break;
                case 'remove':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='You must select at least one email';
                    }else{
                        $count=count($_POST['ids']);
                        $sql='DELETE FROM '.BANLIST_TABLE.' WHERE id IN ('.implode(',', array_map('intval', $_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num of $count selected emails removed from banlist";
                        else
                            $errors['err']='Unable to make remove selected emails. Try again.';
                    }
                    break;
                default:
                    $errors['err']='Uknown banlist command!';
            }
            break;
        case 'email':
            require_once(INCLUDE_DIR.'class.email.php');
            $do=strtolower($_POST['do']);
            switch($do){
                case 'update':
                    $email = new Email($_POST['email_id']);
                    if($email && $email->getId()) {
                        if($email->update($_POST,$errors))
                            $msg='Email updated successfully';
                        elseif(!$errors['err'])
                            $errors['err']='Error updating email';
                    }else{
                        $errors['err']='Internal error';
                    }
                    break;
                case 'create':
                    if(Email::create($_POST,$errors))
                        $msg='Email added successfully';
                    elseif(!$errors['err'])
                         $errors['err']='Unable to add email. Internal error';
                    break;
                case 'mass_process':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='You must select at least one email to process';
                    }else{
                        $count=count($_POST['ids']);
                        $ids=implode(',', array_map('intval', $_POST['ids']));
                        $sql='SELECT count(dept_id) FROM '.DEPT_TABLE.' WHERE email_id IN ('.$ids.') OR autoresp_email_id IN ('.$ids.')';
                        list($depts)=db_fetch_row(db_query($sql));
                        if($depts>0){
                            $errors['err']='One or more of the selected emails is being used by a Dept. Remove association first.';
                        }elseif($_POST['delete']){
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if(Email::deleteEmail($v)) $i++;
                            }
                            if($i>0){
                                $msg="$i of $count selected email(s) deleted";
                            }else{
                                $errors['err']='Unable to delete selected email(s).';
                            }
                        }else{
                            $errors['err']='Unknown command';
                        }
                    }
                    break;
                default:
                    $errors['err']='Unknown topic action!';
            }
            break;
        case 'templates':
           include_once(INCLUDE_DIR.'class.msgtpl.php');
            $do=strtolower($_POST['do']);
            switch($do){
                case 'add':
                case 'create':
                    if(($tid=Template::create($_POST,$errors))){
                        $msg='Template created successfully';
                    }elseif(!$errors['err']){
                        $errors['err']='Error creating the template - try again';
                    }
                    break;
                case 'update':
                    $template=null;
                    if($_POST['id'] && is_numeric($_POST['id'])) {
                        $template= new Template($_POST['id']);
                        if(!$template || !$template->getId()) {
                            $template=null;
                            $errors['err']='Unknown template'.$id;

                        }elseif($template->update($_POST,$errors)){
                            $msg='Template updated successfully';
                        }elseif(!$errors['err']){
                            $errors['err']='Error updating the template. Try again';
                        }
                    }
                    break;
                case 'mass_process':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='You must select at least one template';
                    }elseif(in_array($cfg->getDefaultTemplateId(),$_POST['ids'])){
                        $errors['err']='You can not delete default template';
                    }else{
                        $count=count($_POST['ids']);
                        $ids=implode(',', array_map('intval', $_POST['ids']));
                        $sql='SELECT count(dept_id) FROM '.DEPT_TABLE.' WHERE tpl_id IN ('.$ids.')';
                        list($tpl)=db_fetch_row(db_query($sql));
                        if($tpl>0){
                            $errors['err']='One or more of the selected templates is being used by a Dept. Remove association first.';
                        }elseif($_POST['delete']){
                            $sql='DELETE FROM '.EMAIL_TEMPLATE_TABLE.' WHERE tpl_id IN ('.$ids.') AND tpl_id!='.db_input($cfg->getDefaultTemplateId());
                            if(($result=db_query($sql)) && ($i=db_affected_rows()))
                                $msg="$i of $count selected templates(s) deleted";
                            else
                                $errors['err']='Unable to delete selected templates(s).';
                        }else{
                            $errors['err']='Unknown command';
                        }
                    }
                    break;
                default:
                    $errors['err']='Unknown action';
            }
            break;
    case 'topics':
        require_once(INCLUDE_DIR.'class.topic.php');
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
                $topic = new Topic($_POST['topic_id']);
                if($topic && $topic->getId()) {
                    if($topic->update($_POST,$errors))
                        $msg='Topic updated successfully';
                    elseif(!$errors['err'])
                        $errors['err']='Error updating the topic';
                }else{
                    $errors['err']='Internal error';
                }
                break;
            case 'create':
                if(Topic::create($_POST,$errors))
                    $msg='Help topic created successfully';
                elseif(!$errors['err'])
                    $errors['err']='Unable to create the topic. Internal error';
                break;
            case 'mass_process':
                if(!$_POST['tids'] || !is_array($_POST['tids'])) {
                    $errors['err']='You must select at least one topic';
                }else{
                    $count=count($_POST['tids']);
                    $ids=implode(',', array_map('intval', $_POST['tids']));
                    if($_POST['enable']){
                        $sql='UPDATE '.TOPIC_TABLE.' SET isactive=1, updated=NOW() WHERE topic_id IN ('.$ids.') AND isactive=0 ';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num of $count selected services enabled";
                        else
                            $errors['err']='Unable to complete the action.';
                    }elseif($_POST['disable']){
                        $sql='UPDATE '.TOPIC_TABLE.' SET isactive=0, updated=NOW() WHERE topic_id IN ('.$ids.') AND isactive=1 ';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num of $count selected topics disabled";
                        else
                            $errors['err']='Unable to disable selected topics';
                    }elseif($_POST['delete']){
                        $sql='DELETE FROM '.TOPIC_TABLE.' WHERE topic_id IN ('.$ids.')';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num of $count selected topics deleted!";
                        else
                            $errors['err']='Unable to delete selected topics';
                    }
                }
                break;
            default:
                $errors['err']='Unknown topic action!';
        }
        break;
    case 'groups':
        include_once(INCLUDE_DIR.'class.group.php');
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
                if(Group::update($_POST['group_id'],$_POST,$errors)){
                    $msg='Group '.Format::htmlchars($_POST['group_name']).' updated successfully';
                }elseif(!$errors['err']) {
                    $errors['err']='Error(s) occured. Try again.';
                }
                break;
            case 'create':
                if(($gID=Group::create($_POST,$errors))){
                    $msg='Group '.Format::htmlchars($_POST['group_name']).' created successfully';
                }elseif(!$errors['err']) {
                    $errors['err']='Error(s) occured. Try again.';
                }
                break;
            default:
                if($_POST['grps'] && is_array($_POST['grps'])) {
                    $ids=implode(',', array_map('intval', $_POST['grps']));
                    $selected=count($_POST['grps']);
                    if(isset($_POST['activate_grps'])) {
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=1,updated=NOW() WHERE group_enabled=0 AND group_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." of  $selected selected groups Enabled";
                    }elseif(in_array((string)$thisuser->getGroupId(), array_map('strval', $_POST['grps']), true)) {
                          $errors['err']="Trying to 'Disable' or 'Delete' your group? Doesn't make any sense!";
                    }elseif(isset($_POST['disable_grps'])) {
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=0, updated=NOW() WHERE group_enabled=1 AND group_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." of  $selected selected groups Disabled";
                    }elseif(isset($_POST['delete_grps'])) {
                        $res=db_query('SELECT staff_id FROM '.STAFF_TABLE.' WHERE group_id IN('.$ids.')');
                        if(!$res || db_num_rows($res)) {
                            $errors['err']='One or more of the selected groups have users. Only empty groups can be deleted.';
                        }else{
                            db_query('DELETE FROM '.GROUP_TABLE.' WHERE group_id IN('.$ids.')');
                            $msg=db_affected_rows()." of  $selected selected groups Deleted";
                        }
                    }else{
                         $errors['err']='Uknown command!';
                    }

                }else{
                    $errors['err']='No groups selected.';
                }
        }
    break;
    case 'staff':
        include_once(INCLUDE_DIR.'class.staff.php');
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
                $staff = new Staff($_POST['staff_id']);
                if($staff && $staff->getId()) {
                    if($staff->update($_POST,$errors))
                        $msg='Staff profile updated successfully';
                    elseif(!$errors['err'])
                        $errors['err']='Error updating the user';
                }else{
                    $errors['err']='Internal error';
                }
                break;
            case 'create':
                if(($uID=Staff::create($_POST,$errors)))
                    $msg=Format::htmlchars($_POST['firstname'].' '.$_POST['lastname']).' added successfully';
                elseif(!$errors['err'])
                    $errors['err']='Unable to add the user. Internal error';
                break;
            case 'mass_process':
                if($_POST['uids'] && is_array($_POST['uids'])) {
                    $ids=implode(',', array_map('intval', $_POST['uids']));
                    $selected=count($_POST['uids']);
                    if(isset($_POST['enable'])) {
                        $sql='UPDATE '.STAFF_TABLE.' SET isactive=1,updated=NOW() WHERE isactive=0 AND staff_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." of  $selected selected users enabled";

                    }elseif(in_array($thisuser->getId(),$_POST['uids'])) {
                        $errors['err']='You can not lock or delete yourself!';
                    }elseif(isset($_POST['disable'])) {
                        $sql='UPDATE '.STAFF_TABLE.' SET isactive=0, updated=NOW() '.
                            ' WHERE isactive=1 AND staff_id IN('.$ids.') AND staff_id!='.$thisuser->getId();
                        db_query($sql);
                        $msg=db_affected_rows()." of  $selected selected users locked";

                    }elseif(isset($_POST['delete'])) {
                        db_query('DELETE FROM '.STAFF_TABLE.' WHERE staff_id IN('.$ids.') AND staff_id!='.$thisuser->getId());
                        $msg=db_affected_rows()." of  $selected selected users deleted";
                        db_query('UPDATE '.DEPT_TABLE.' SET manager_id=0 WHERE manager_id IN('.$ids.') ');
                        db_query('UPDATE '.TICKET_TABLE.' SET staff_id=0 WHERE staff_id IN('.$ids.') ');
                    }else{
                        $errors['err']='Uknown command!';
                    }
                }else{
                    $errors['err']='No users selected.';
                }
            break;
            default:
                $errors['err']='Uknown command!';
        }
    break;
    case 'priorityusers':
        include_once(INCLUDE_DIR.'class.priorityuser.php');
        $do = strtolower($_POST['do']);
        switch ($do) {
            case 'update':
                $pu = PriorityUser::lookup($_POST['pu_id']);
                if ($pu && $pu->getId()) {
                    if ($pu->update($_POST, $errors))
                        $msg = 'Приоритетный пользователь обновлен';
                    elseif (!$errors['err'])
                        $errors['err'] = 'Ошибка обновления';
                } else {
                    $errors['err'] = 'Internal error';
                }
                break;
            case 'create':
                if (($puID = PriorityUser::create($_POST, $errors)))
                    $msg = 'Приоритетный пользователь ' . Format::htmlchars($_POST['email']) . ' добавлен';
                elseif (!$errors['err'])
                    $errors['err'] = 'Не удалось добавить приоритетного пользователя';
                break;
            case 'mass_process':
                if ($_POST['pids'] && is_array($_POST['pids'])) {
                    $ids = implode(',', array_map('intval', $_POST['pids']));
                    $selected = count($_POST['pids']);
                    if (isset($_POST['enable'])) {
                        $sql = 'UPDATE ' . PRIORITY_USERS_TABLE . ' SET is_active=1, updated=NOW() WHERE is_active=0 AND id IN(' . $ids . ')';
                        db_query($sql);
                        $msg = db_affected_rows() . " из $selected выбранных пользователей включены";
                    } elseif (isset($_POST['disable'])) {
                        $sql = 'UPDATE ' . PRIORITY_USERS_TABLE . ' SET is_active=0, updated=NOW() WHERE is_active=1 AND id IN(' . $ids . ')';
                        db_query($sql);
                        $msg = db_affected_rows() . " из $selected выбранных пользователей отключены";
                    } elseif (isset($_POST['delete'])) {
                        $sql = 'DELETE FROM ' . PRIORITY_USERS_TABLE . ' WHERE id IN(' . $ids . ')';
                        db_query($sql);
                        $msg = db_affected_rows() . " из $selected выбранных пользователей удалены";
                    } else {
                        $errors['err'] = 'Неизвестная команда!';
                    }
                } else {
                    $errors['err'] = 'Не выбраны пользователи.';
                }
                break;
            default:
                $errors['err'] = 'Неизвестная команда!';
        }
        break;
    case 'dept':
        include_once(INCLUDE_DIR.'class.dept.php');
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
                $dept = new Dept($_POST['dept_id']);
                if($dept && $dept->getId()) {
                    if($dept->update($_POST,$errors))
                        $msg='Dept updated successfully';
                    elseif(!$errors['err'])
                        $errors['err']='Error updating the department';
                }else{
                    $errors['err']='Internal error';
                }
                break;
            case 'create':
                if(($deptID=Dept::create($_POST,$errors)))
                    $msg=Format::htmlchars($_POST['dept_name']).' added successfully';
                elseif(empty($errors['err']))
                    $errors['err']='Unable to add department. Correct errors below.';
                break;
            case 'mass_process':
                if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                    $errors['err']='You must select at least one department';
                }elseif(!$_POST['public'] && in_array($cfg->getDefaultDeptId(),$_POST['ids'])) {
                    $errors['err']='You can not disable/delete a default department. Remove default Dept and try again.';
                }else{
                    $count=count($_POST['ids']);
                    $ids=implode(',', array_map('intval', $_POST['ids']));
                    if($_POST['public']){
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=1 WHERE dept_id IN ('.$ids.')';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $warn="$num of $count selected departments made public";
                        else
                            $errors['err']='Unable to make depts public.';
                    }elseif($_POST['private']){
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=0 WHERE dept_id IN ('.$ids.') AND dept_id!='.db_input($cfg->getDefaultDeptId());
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            $warn="$num of $count selected departments made private";
                        }else
                            $errors['err']='Unable to make selected department(s) private. Possibly already private!';

                    }elseif($_POST['delete']){
                        $sql='SELECT count(staff_id) FROM '.STAFF_TABLE.' WHERE dept_id IN ('.$ids.')';
                        list($members)=db_fetch_row(db_query($sql));
                        $sql='SELECT count(topic_id) FROM '.TOPIC_TABLE.' WHERE dept_id IN ('.$ids.')';
                        list($topics)=db_fetch_row(db_query($sql));
                        if($members){
                            $errors['err']='Can not delete Dept. with members. Move staff first.';
                        }elseif($topics){
                             $errors['err']='Нельзя удалить отдел, связанный с темами обращений. Сначала снимите привязку.';
                        }else{
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if($v==$cfg->getDefaultDeptId()) continue;
                                if(Dept::delete($v)) $i++;
                            }
                            if($i>0){
                                $warn="$i of $count selected departments deleted";
                            }else{
                                $errors['err']='Unable to delete selected departments.';
                            }
                        }
                    }
                }
            break;
            default:
                $errors['err']='Unknown Dept action';
        }
    break;
    default:
        $errors['err']='Uknown command!';
    endswitch;
    } // end CSRF else
endif;

$thistab=strtolower(($_REQUEST['t'] ?? null)?$_REQUEST['t']:'dashboard');
$inc=$page='';
$submenu=array();
switch($thistab){
    case 'settings':
    case 'pref':
    case 'attach':
    case 'api':
    case 'api-docs':
    case 'api-monitoring':
        $nav->setTabActive('settings');
        $nav->addSubMenu(array('desc'=>'Настройки','href'=>'admin.php?t=pref','iconclass'=>'preferences'));
        $nav->addSubMenu(array('desc'=>'Вложения','href'=>'admin.php?t=attach','iconclass'=>'attachment'));
        $nav->addSubMenu(array('desc'=>'API','href'=>'admin.php?t=api','iconclass'=>'api'));
        $nav->addSubMenu(array('desc'=>'API Документация','href'=>'admin.php?t=api-docs','iconclass'=>'help'));
        $nav->addSubMenu(array('desc'=>'API Мониторинг','href'=>'admin.php?t=api-monitoring','iconclass'=>'stats'));
        switch($thistab):
        case 'settings':
        case 'pref':
            $page='preference.inc.php';
            break;
        case 'attach':
            $page='attachment.inc.php';
            break;
        case 'api':
            $page='api.inc.php';
            break;
        case 'api-docs':
            $page='api-docs.inc.php';
            break;
        case 'api-monitoring':
            $page='api-monitoring.inc.php';
        endswitch;
        break;
    case 'dashboard':
    case 'syslog':
        $nav->setTabActive('dashboard');
        $nav->addSubMenu(array('desc'=>'Системные Журналы','href'=>'admin.php?t=syslog','iconclass'=>'syslogs'));
        $page='syslogs.inc.php';
        break;
    case 'email':
    case 'templates':
    case 'banlist':
        $nav->setTabActive('emails');
        $nav->addSubMenu(array('desc'=>'Email Адреса','href'=>'admin.php?t=email','iconclass'=>'emailSettings'));
        $nav->addSubMenu(array('desc'=>'Шаблоны','href'=>'admin.php?t=templates','title'=>'Email Templates','iconclass'=>'emailTemplates'));
        $nav->addSubMenu(array('desc'=>'Банлист','href'=>'admin.php?t=banlist','title'=>'Banned Email','iconclass'=>'banList'));
        switch(strtolower($_REQUEST['t'])){
            case 'templates':
                $page='templates.inc.php';
                $template=null;
                if(($id=(!empty($_REQUEST['id'])?$_REQUEST['id']:($_POST['email_id'] ?? null))) && is_numeric($id)) {
                    include_once(INCLUDE_DIR.'class.msgtpl.php');
                    $template= new Template($id);
                    if(!$template || !$template->getId()) {
                        $template=null;
                        $errors['err']='Unable to fetch info on template ID#'.$id;
                    }else {
                        $page='template.inc.php';
                    }
                }
                break;
            case 'banlist':
                $page='banlist.inc.php';
                break;
            case 'email':
            default:
                include_once(INCLUDE_DIR.'class.email.php');
                $email=null;
                if(($id=(!empty($_REQUEST['id'])?$_REQUEST['id']:($_POST['email_id'] ?? null))) && is_numeric($id)) {
                    $email= new Email($id,false);
                    if(!$email->load()) {
                        $email=null;
                        $errors['err']='Unable to fetch info on email ID#'.$id;
                    }
                }
                $page=($email or (($_REQUEST['a'] ?? '')=='new' && empty($emailID)))?'email.inc.php':'emails.inc.php';
        }
        break;
    case 'topics':
        require_once(INCLUDE_DIR.'class.topic.php');
        $topic=null;
        $nav->setTabActive('topics');
        $nav->addSubMenu(array('desc'=>'Темы Обращения','href'=>'admin.php?t=topics','iconclass'=>'helpTopics'));
        if(($id=(!empty($_REQUEST['id'])?$_REQUEST['id']:($_POST['topic_id'] ?? null))) && is_numeric($id)) {
            $topic= new Topic($id);
            if(!$topic->load() && $topic->getId()==$id) {
                $topic=null;
                $errors['err']='Unable to fetch info on topic #'.$id;
            }
        }
        $page=($topic or (($_REQUEST['a'] ?? '')=='new' && empty($topicID)))?'topic.inc.php':'helptopics.inc.php';
        break;
    case 'grp':
    case 'groups':
    case 'staff':
    case 'priorityusers':
        $group=null;
        $nav->setTabActive('staff');
        $nav->addSubMenu(array('desc'=>'Пользователи','href'=>'admin.php?t=staff','iconclass'=>'users'));
        $nav->addSubMenu(array('desc'=>'Группы','href'=>'admin.php?t=groups','iconclass'=>'groups'));
        $nav->addSubMenu(array('desc'=>'Приоритетные','href'=>'admin.php?t=priorityusers','iconclass'=>'priorityUsers'));
        $page='';
        switch($thistab){
            case 'grp':
            case 'groups':
                if(($id=(!empty($_REQUEST['id'])?$_REQUEST['id']:($_POST['group_id'] ?? null))) && is_numeric($id)) {
                    $res=db_query('SELECT * FROM '.GROUP_TABLE.' WHERE group_id='.db_input($id));
                    if(!$res or !db_num_rows($res) or !($group=db_fetch_array($res)))
                        $errors['err']='Unable to fetch info on group ID#'.$id;
                }
                $page=($group or (($_REQUEST['a'] ?? '')=='new' && empty($gID)))?'group.inc.php':'groups.inc.php';
                break;
            case 'staff':
                $page='staffmembers.inc.php';
                if(($id=(!empty($_REQUEST['id'])?$_REQUEST['id']:($_POST['staff_id'] ?? null))) && is_numeric($id)) {
                    $staff = new Staff($id);
                    if(!$staff || !is_object($staff) || $staff->getId()!=$id) {
                        $staff=null;
                        $errors['err']='Unable to fetch info on rep ID#'.$id;
                    }
                }
                $page=($staff or (($_REQUEST['a'] ?? '')=='new' && empty($uID)))?'staff.inc.php':'staffmembers.inc.php';
                break;
            case 'priorityusers':
                include_once(INCLUDE_DIR.'class.priorityuser.php');
                $priorityuser = null;
                if(($id=(!empty($_REQUEST['id'])?$_REQUEST['id']:($_POST['pu_id'] ?? null))) && is_numeric($id)) {
                    $priorityuser = PriorityUser::lookup($id);
                    if(!$priorityuser) {
                        $errors['err']='Не удалось загрузить приоритетного пользователя #'.$id;
                    }
                }
                $page=($priorityuser or (($_REQUEST['a'] ?? '')=='new' && empty($puID)))?'priorityuser.inc.php':'priorityusers.inc.php';
                break;
            default:
                $page='staffmembers.inc.php';
        }
        break;
    case 'dept':
    case 'depts':
        $dept=null;
        if(($id=(!empty($_REQUEST['id'])?$_REQUEST['id']:($_POST['dept_id'] ?? null))) && is_numeric($id)) {
            $dept= new Dept($id);
            if(!$dept || !$dept->getId()) {
                $dept=null;
                $errors['err']='Unable to fetch info on Dept ID#'.$id;
            }
        }
        $page=($dept or (($_REQUEST['a'] ?? '')=='new' && empty($deptID)))?'dept.inc.php':'depts.inc.php';
        $nav->setTabActive('depts');
        $nav->addSubMenu(array('desc'=>'Отделы','href'=>'admin.php?t=depts','iconclass'=>'departments'));
        break;
    default:
        $page='preference.inc.php';
}
$inc=($page)?STAFFINC_DIR.$page:'';
require(STAFFINC_DIR.'header.inc.php');
?>
<div>
    <?if(!empty($errors['err'])) {?>
        <p align="center" id="errormessage"><?=Format::htmlchars($errors['err'])?></p>
    <?}elseif($msg) {?>
        <p align="center" id="infomessage"><?=Format::htmlchars($msg)?></p>
    <?}elseif($warn) {?>
        <p align="center" id="warnmessage"><?=Format::htmlchars($warn)?></p>
    <?}?>
</div>
<table width="100%" border="0" cellspacing="0" cellpadding="1">
    <tr><td>
        <div style="margin:0 5px 5px 5px;">
        <?
            if($inc && file_exists($inc)){
                require($inc);
            }else{
                ?>
                <p align="center">
                    <font class="error">Problems loading requested admin page. (<?=Format::htmlchars($thistab)?>)</font>
                    <br>Possibly access denied, if you believe this is in error please get technical support.
                </p>
            <?}?>
        </div>
    </td></tr>
</table>
<?
include_once(STAFFINC_DIR.'footer.inc.php');
?>
