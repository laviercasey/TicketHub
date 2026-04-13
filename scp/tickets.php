<?php
if (isset($_POST['assign_message']) && empty($_POST['assign_message'])) $_POST['assign_message'] = 'Назначен ответственный.';

require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.banlist.php');


$page='';
$ticket=null;
if(!$errors && ($id=($_REQUEST['id'] ?? null)?$_REQUEST['id']:($_POST['ticket_id'] ?? null)) && is_numeric($id)) {
    $deptID=0;
    $ticket= new Ticket($id);
    if(!$ticket or !$ticket->getDeptId())
        $errors['err']='Unknown ticket ID#'.$id;
    elseif(!$thisuser->isAdmin()  && (!$thisuser->canAccessDept($ticket->getDeptId()) && $thisuser->getId()!=$ticket->getStaffId() && !in_array($thisuser->getId(), explode(',', str_replace('*', '', $ticket->getStaffsIdWithStar())))))
        $errors['err']='Access denied. Contact admin if you believe this is in error';

    if(!$errors && $ticket->getId()==$id)
        $page='viewticket.inc.php';

    if(!$errors && ($_REQUEST['a'] ?? '')==='edit') {
        if($thisuser->canEditTickets() || ($thisuser->isManager() && $ticket->getDeptId()==$thisuser->getDeptId()))
            $page='editticket.inc.php';
        else
            $errors['err']='Access denied. You are not allowed to edit this ticket. Contact admin if you believe this is in error';
    }

}elseif(($_REQUEST['a'] ?? '')==='open') {
    $page='newticket.inc.php';
}
if ($_POST && !$errors):
    if (!isset($_POST["secret"]) || !isset($_SESSION["secret"]) || !hash_equals($_SESSION["secret"], $_POST["secret"])) {
        $errors['err']='Ошибка безопасности. Обновите страницу и попробуйте снова.';
    }

    if (!$errors):

        if(!Misc::validateCSRFToken($_POST['csrf_token'])) {
            $errors['err']='Invalid form submission. Please try again.';
        }

        if ($ticket && $ticket->getId()) {
            $errors = array();
            $lock = $ticket->getLock();
            $statusKeys = array('open' => 'Открыто', 'Reopen' => 'Открыто', 'Close' => 'Закрыто');
            switch (strtolower($_POST['a'])):
                case 'reply':
                    $fields = array();
                    $fields['msg_id'] = array('type' => 'int', 'required' => 1, 'error' => 'Пропущено ID сообщения');
                    $fields['response'] = array('type' => 'text', 'required' => 1, 'error' => 'Response message required');
                    $params = new Validator($fields);
                    if (!$params->validate($_POST)) {
                        $errors = array_merge($errors, $params->errors());
                    }
                    if ($lock && $lock->getStaffId() != $thisuser->getId())
                        $errors['err'] = 'Action Denied. Ticket is locked by someone else!';

                    if ($_FILES['attachment'] && $_FILES['attachment']['size']) {
                        if (!$_FILES['attachment']['name'] || !$_FILES['attachment']['tmp_name'])
                            $errors['attachment'] = 'Invalid attachment';
                        elseif (!$cfg->canUploadFiles())
                            $errors['attachment'] = 'upload dir invalid. Contact admin.';
                        elseif (!$cfg->canUploadFileType($_FILES['attachment']['name']))
                            $errors['attachment'] = 'Неверный тип файла';
                    }

                    if (!$errors && BanList::isbanned($ticket->getEmail()))
                        $errors['err'] = 'Email в банлисте. Удалите и попробуйте снова';


                    if (!$errors && ($respId = $ticket->postResponse($_POST['msg_id'], $_POST['response'], $_POST['signature'], $_FILES['attachment']))) {
                        $msg = 'Ответ успешно добавлен';
                        $wasOpen = $ticket->isOpen();
                        if (isset($_POST['ticket_status']) && $_POST['ticket_status']) {
                            if ($ticket->setStatus($_POST['ticket_status']) && $ticket->reload()) {
                                $note = sprintf('%s %s the ticket on reply', $thisuser->getName(), $ticket->isOpen() ? 'reopened' : 'closed');
                                $ticket->logActivity('Ticket status changed to ' . ($ticket->isOpen() ? 'Open' : 'Closed'), $note);
                            }
                        }
                        if ($_FILES['attachment'] && $_FILES['attachment']['size']) {
                            $ticket->uploadAttachment($_FILES['attachment'], $respId, 'R');
                        }
                        $ticket->reload();
                        if ($ticket->isopen()) {
                            $ticket->markAnswered();
                        } elseif ($wasOpen) {
                            $page = $ticket = null;
                        }
                    } elseif (!$errors['err']) {
                        $errors['err'] = 'Unable to post the response.';
                    }
                    break;
                case 'transfer':
                    $fields = array();
                    $fields['dept_id'] = array('type' => 'int', 'required' => 1, 'error' => 'Выберите отдел');
                    $fields['message'] = array('type' => 'text', 'required' => 1, 'error' => 'Note/Message required');
                    $params = new Validator($fields);
                    if (!$params->validate($_POST)) {
                        $errors = array_merge($errors, $params->errors());
                    }

                    if (!$errors && ($_POST['dept_id'] == $ticket->getDeptId()))
                        $errors['dept_id'] = 'Ticket already in the Dept.';

                    if (!$errors && !$thisuser->canTransferTickets())
                        $errors['err'] = 'Action Denied. You are not allowed to transfer tickets.';

                    if (!$errors && $ticket->transfer($_POST['dept_id'])) {
                        $olddept = $ticket->getDeptName();
                        $ticket->reload();

                        $title = 'Dept. Transfer from ' . $olddept . ' to ' . $ticket->getDeptName();
                        $ticket->postNote($title, $_POST['message']);
                        $msg = 'Ticket transfered sucessfully to ' . $ticket->getDeptName() . ' Dept.';
                        if (!$thisuser->canAccessDept($_POST['dept_id']) && $ticket->getStaffId() != $thisuser->getId()) {
                            $page='viewticket.inc.php';
                        }
                    } elseif (!$errors['err']) {
                        $errors['err'] = 'Unable to complete the transfer';
                    }
                    break;
                case 'assign':
                    if (empty($_POST['assign_message']))
                        $_POST['assign_message'] = 'Назначен ответственный.';

                    $fields = array();
                    $fields['staffId'] = array('type' => 'int', 'required' => 0, 'error' => 'Select assignee');
                    $fields['assign_message'] = array('type' => 'text', 'required' => 0, 'error' => 'Message required');
                    $params = new Validator($fields);
                    if (!$params->validate($_POST)) {
                        $errors = array_merge($errors, $params->errors());
                    }

                    $currentMainStaff = $ticket->getStaffId();
                    $currentMoreStaffs = $ticket->getStaffsIdWithStar();

                    $newMainStaff = isset($_POST['staffId']) ? (int)$_POST['staffId'] : 0;


                    $newmorestaffs_id = '';
                    foreach ($_POST as $key => $value) {
                        if (strpos($key, 'morestaffs_id_') === 0 && !empty($value) && is_numeric($value)) {
                            $staffId = (int)$value;
                            if ($staffId != $newMainStaff) {
                                $zpt = $newmorestaffs_id == '' ? '' : ',';
                                $newmorestaffs_id .= $zpt . '*' . $staffId . '*';
                            }
                        }
                    }



                    $isMainChanged = ($newMainStaff != $currentMainStaff);
                    $isMoreChanged = ($newmorestaffs_id != $currentMoreStaffs);

                    if (!$errors && !$isMainChanged && !$isMoreChanged) {
                        $errors['err'] = 'Не внесено изменений в назначение исполнителей';
                    }



                    if (!$errors && $isMainChanged) {
                        if ($newMainStaff == 0) {
                            if ($ticket->setStaffId(0)) {
                                $msg = 'Основной исполнитель удален';
                                $ticket->postNote('Основной исполнитель удален', $_POST['assign_message'], false);
                            } else {
                                $errors['err'] = 'Ошибка удаления основного исполнителя';
                            }
                        }
                        else {
                            if ($ticket->isAssigned() && $newMainStaff == $currentMainStaff) {
                                $errors['staffId'] = 'Ticket already assigned to the staff.';
                            } else {
                                if ($ticket->assignStaff($newMainStaff, $_POST['assign_message'])) {
                                    $staff = $ticket->getStaff();
                                    $msg = 'Назначен исполнитель ' . ($staff ? $staff->getName() : 'staff');
                                } else {
                                    $errors['err'] = 'Unable to assign the ticket (main)';
                                }
                            }
                        }
                    }

                    if (!$errors && $isMoreChanged) {
                        if ($ticket->setandstaffs_id($newmorestaffs_id)) {
                            $assignMessage = isset($_POST['assign_message']) ? $_POST['assign_message'] : '';
                            $ticket->notifyNewAdditionalStaff($newmorestaffs_id, $currentMoreStaffs, $assignMessage);

                            $actionMsg = 'Дополнительные исполнители назначены';

                            if ($newmorestaffs_id === '') {
                                $actionMsg = 'Дополнительные исполнители удалены';
                            }

                            if (isset($msg) && strlen($msg) > 1) {
                                $msg .= ' и ' . $actionMsg;
                            } else {
                                $msg = $actionMsg;
                            }
                        } else {
                                $errors['err'] = 'Unable to assign the ticket (additional)';
                        }
                    }


                    if (!$errors) {
                        TicketLock::removeStaffLocks($thisuser->getId(), $ticket->getId());
                        $page = 'viewticket.inc.php';
                        $ticket->reload();
                    }
                    break;
                case 'postnote':
                    $fields = array();
                    $fields['title'] = array('type' => 'string', 'required' => 1, 'error' => 'Введите заголовок');
                    $fields['note'] = array('type' => 'string', 'required' => 1, 'error' => 'Введите сообщение');
                    $params = new Validator($fields);
                    if (!$params->validate($_POST))
                        $errors = array_merge($errors, $params->errors());

                    if (!$errors && $ticket->postNote($_POST['title'], $_POST['note'])) {
                        $msg = 'Внутреннее сообщение добавлено';
                        if (isset($_POST['ticket_status']) && $_POST['ticket_status']) {
                            if ($ticket->setStatus($_POST['ticket_status']) && $ticket->reload()) {
                                $msg .= ' and status set to ' . ($ticket->isClosed() ? 'closed' : 'open');
                                if ($ticket->isClosed())
                                    $page = $ticket = null;
                            }
                        }
                    } elseif (!$errors['err']) {
                        $errors['err'] = 'Error(s) occured. Unable to post the note.';
                    }
                    break;
                case 'update':
                    $page = 'editticket.inc.php';
                    if (!$ticket || !$thisuser->canEditTickets())
                        $errors['err'] = 'Perm. Denied. You are not allowed to edit tickets';
                    elseif ($ticket->update($_POST, $errors)) {
                        $msg = 'Заявка успешно изменена';
                        $page = 'viewticket.inc.php';
                    } elseif (!$errors['err']) {
                        $errors['err'] = 'Error(s) occured! Try again.';
                    }
                    break;
                case 'process':
                    $isdeptmanager = ($ticket->getDeptId() == $thisuser->getDeptId()) ? true : false;
                    switch (strtolower($_POST['do'])):
                        case 'change_priority':
                            if (!$thisuser->canManageTickets() && !$thisuser->isManager()) {
                                $errors['err'] = 'Perm. Denied. You are not allowed to change ticket\'s priority';
                            } elseif (!$_POST['ticket_priority'] or !is_numeric($_POST['ticket_priority'])) {
                                $errors['err'] = 'You must select priority';
                            }
                            if (!$errors) {
                                if ($ticket->setPriority($_POST['ticket_priority'])) {
                                    $msg = 'Приоритет успешно изменен';
                                    $ticket->reload();
                                    $note = 'Ticket priority set to "' . $ticket->getPriority() . '" by ' . $thisuser->getName();
                                    $ticket->logActivity('Priority Changed', $note);
                                } else {
                                    $errors['err'] = 'Problems changing priority. Try again';
                                }
                            }
                            break;
                        case 'close':
                            if (!$thisuser->isadmin() && !$thisuser->canCloseTickets()) {
                                $errors['err'] = 'Perm. Denied. You are not allowed to close tickets.';
                            } else {
                                if ($ticket->close()) {
                                    $msg = 'Заявке #' . $ticket->getExtId() . ' назначен статус ЗАКРЫТО';
                                    $note = 'Ticket closed without response by ' . $thisuser->getName();
                                    $ticket->logActivity('Ticket Closed', $note);
                                    $page = $ticket = null;
                                } else {
                                    $errors['err'] = 'Problems closing the ticket. Try again';
                                }
                            }
                            break;
                        case 'reopen':
                            if (!$thisuser->isadmin() && !$thisuser->canCloseTickets()) {
                                $errors['err'] = 'Perm. Denied. You are not allowed to reopen tickets.';
                            } else {
                                if ($ticket->reopen()) {
                                    $msg = 'Заявке назначен статус ОТКРЫТЫЙ';
                                    $note = 'Ticket reopened (without comments)';
                                    if ($_POST['ticket_priority']) {
                                        $ticket->setPriority($_POST['ticket_priority']);
                                        $ticket->reload();
                                        $note .= ' и назначен статус ' . $ticket->getPriority();
                                    }
                                    $note .= ' by ' . $thisuser->getName();
                                    $ticket->logActivity('Ticket Reopened', $note);
                                } else {
                                    $errors['err'] = 'Problems reopening the ticket. Try again';
                                }
                            }
                            break;
                        case 'release':
                            if (!($staff = $ticket->getStaff()))
                                $errors['err'] = 'Ticket is not assigned!';
                            elseif ($ticket->release()) {
                                $msg = 'Ticket released (unassigned) from ' . $staff->getName() . ' by ' . $thisuser->getName();;
                                $ticket->logActivity('Ticket unassigned', $msg);
                            } else
                                $errors['err'] = 'Problems releasing the ticket. Try again';
                            break;
                        case 'overdue':
                            if (!$thisuser->isadmin() && !$thisuser->isManager()) {
                                $errors['err'] = 'Perm. Denied. You are not allowed to flag tickets overdue';
                            } else {
                                if ($ticket->markOverdue()) {
                                    $msg = 'Заявка помечена как просроченная';
                                    $note = $msg;
                                    if ($_POST['ticket_priority']) {
                                        $ticket->setPriority($_POST['ticket_priority']);
                                        $ticket->reload();
                                        $note .= ' и назначен статус ' . $ticket->getPriority();
                                    }
                                    $note .= ' by ' . $thisuser->getName();
                                    $ticket->logActivity('Ticket Marked Overdue', $note);
                                } else {
                                    $errors['err'] = 'Problems marking the the ticket overdue. Try again';
                                }
                            }
                            break;
                        case 'banemail':
                            if (!$thisuser->isadmin() && !$thisuser->canManageBanList()) {
                                $errors['err'] = 'Perm. Denied. You are not allowed to ban emails';
                            } elseif (Banlist::add($ticket->getEmail(), $thisuser->getName())) {
                                $msg = 'Email (' . $ticket->getEmail() . ') добавлен в банлист';
                                if ($ticket->isOpen() && $ticket->close()) {
                                    $msg .= ' & заявке назначен статус ЗАКРЫТО';
                                    $ticket->logActivity('Ticket Closed', $msg);
                                    $page = $ticket = null;
                                }
                            } else {
                                $errors['err'] = 'Unable to add the email to banlist';
                            }
                            break;
                        case 'unbanemail':
                            if (!$thisuser->isadmin() && !$thisuser->canManageBanList()) {
                                $errors['err'] = 'Perm. Denied. You are not allowed to remove emails from banlist.';
                            } elseif (Banlist::remove($ticket->getEmail())) {
                                $msg = 'Email removed from banlist';
                            } else {
                                $errors['err'] = 'Unable to remove the email from banlist. Try again.';
                            }
                            break;
                        case 'delete':
                            if (!$thisuser->isadmin() && !$thisuser->canDeleteTickets()) {
                                $errors['err'] = 'Perm. Denied. You are not allowed to DELETE tickets!!';
                            } else {
                                if ($ticket->delete()) {
                                    $page = 'tickets.inc.php';
                                    $msg = 'Заявка успешно удалена';
                                    $ticket = null;
                                } else {
                                    $errors['err'] = 'Problems deleting the ticket. Try again';
                                }
                            }
                            break;
                        default:
                            $errors['err'] = 'You must select action to perform';
                    endswitch;
                    break;
                default:
                    $errors['err'] = 'Неизвестное действие';
            endswitch;
            if ($ticket && is_object($ticket))
                $ticket->reload();
        } elseif ($_POST['a']) {
            switch ($_POST['a']) {
                case 'mass_process':
                    if (!$thisuser->canManageTickets())
                        $errors['err'] = 'You do not have permission to mass manage tickets. Contact admin for such access';
                    elseif (!$_POST['tids'] || !is_array($_POST['tids']))
                        $errors['err'] = 'Нет выбранных заявок. Вы должны выбрать хотя бы один запрос.';
                    elseif (($_POST['reopen'] || $_POST['close']) && !$thisuser->canCloseTickets())
                        $errors['err'] = 'You do not have permission to close/reopen tickets';
                    elseif ($_POST['delete'] && !$thisuser->canDeleteTickets())
                        $errors['err'] = 'You do not have permission to delete tickets';
                    elseif (!$_POST['tids'] || !is_array($_POST['tids']))
                        $errors['err'] = 'You must select at least one ticket';

                    if (!$errors) {
                        $count = count($_POST['tids']);
                        if (isset($_POST['reopen'])) {
                            $i = 0;
                            $note = 'Заявка снова открыта ' . $thisuser->getName();
                            foreach ($_POST['tids'] as $k => $v) {
                                $t = new Ticket($v);
                                if ($t && @$t->reopen()) {
                                    $i++;
                                    $t->logActivity('Ticket Reopened', $note, false, 'System');
                                }
                            }
                            $msg = "$i of $count selected tickets reopened";
                        } elseif (isset($_POST['close'])) {
                            $i = 0;
                            $note = 'Ticket closed without response by ' . $thisuser->getName();
                            foreach ($_POST['tids'] as $k => $v) {
                                $t = new Ticket($v);
                                if ($t && @$t->close()) {
                                    $i++;
                                    $t->logActivity('Ticket Closed', $note, false, 'System');
                                }
                            }
                            $msg = "$i из $count выбраных заявок закрыты";
                        } elseif (isset($_POST['overdue'])) {
                            $i = 0;
                            $note = 'Ticket flagged as overdue by ' . $thisuser->getName();
                            foreach ($_POST['tids'] as $k => $v) {
                                $t = new Ticket($v);
                                if ($t && !$t->isoverdue())
                                    if ($t->markOverdue()) {
                                        $i++;
                                        $t->logActivity('Ticket Marked Overdue', $note, false, 'System');
                                    }
                            }
                            $msg = "$i из $count заявок отмечены как просроченные";
                        } elseif (isset($_POST['delete'])) {
                            $i = 0;
                            foreach ($_POST['tids'] as $k => $v) {
                                $t = new Ticket($v);
                                if ($t && @$t->delete()) $i++;
                            }
                            $msg = "$i из $count выбранных заявок удалены";
                        }
                    }
                    break;
                case 'open':
                    $ticket = null;
                    if (($ticket = Ticket::create_by_staff($_POST, $errors))) {
                        $ticket->reload();
                        $msg = 'Заявка создана успешно';
                        if ($thisuser->canAccessDept($ticket->getDeptId()) || $ticket->getStaffId() == $thisuser->getId()) {
                            $page = 'viewticket.inc.php';
                        } else {
                            $page = 'tickets.inc.php';
                            $ticket = null;
                        }
                    } elseif (!$errors['err']) {
                        $errors['err'] = 'Невозможно создать заявку. Исправьте ошибки и попробуйте еще раз';
                    }
                    break;
            }
        }
        $unused = '';
    endif;
endif;
$_SESSION["secret"] = bin2hex(random_bytes(16));

$sql='SELECT '.
     'SUM(IF(ticket.status=\'open\' AND ticket.isanswered=0, 1, 0)) as open, '.
     'SUM(IF(ticket.status=\'open\' AND ticket.isanswered=1, 1, 0)) as answered, '.
     'SUM(IF(ticket.status=\'open\' AND ticket.isoverdue=1, 1, 0)) as overdue, '.
     'SUM(IF((ticket.staff_id='.db_input($thisuser->getId()).' OR ticket.andstaffs_id LIKE \'%*'.$thisuser->getId().'*%\') AND ticket.status=\'open\', 1, 0)) as assigned, '.
     'SUM(IF(ticket.status=\'closed\', 1, 0)) as closed '.
     'FROM '.TICKET_TABLE.' ticket ';

$sql2='SELECT count(ticket_archived.ticket_id) as archived '.
     ' FROM '.TICKET_TABLE_ARCHIVED.' ticket_archived ';

if(!$thisuser->isAdmin()){
    $sql.=' WHERE (ticket.dept_id IN('.implode(',',$thisuser->getDepts()).') OR ticket.andstaffs_id LIKE \'%*'.$thisuser->getId().'*%\' OR ticket.staff_id='.db_input($thisuser->getId()).')';
	$sql2.=' WHERE (ticket_archived.dept_id IN('.implode(',',$thisuser->getDepts()).') OR ticket_archived.andstaffs_id LIKE \'%*'.$thisuser->getId().'*%\' OR ticket_archived.staff_id='.db_input($thisuser->getId()).')';
}

$stats=db_fetch_array(db_query($sql));
$stats2=db_fetch_array(db_query($sql2));
$nav->setTabActive('tickets');

if($cfg->showAnsweredTickets()) {
    $nav->addSubMenu(array('desc'=>'Открыто ('.($stats['open']+$stats['answered']).')'
                            ,'title'=>'Открытые Заявки', 'href'=>'tickets.php?status=open', 'iconclass'=>'Ticket'));
}else{
    $nav->addSubMenu(array('desc'=>'Открыто ('.$stats['open'].')','title'=>'Открытые Заявки', 'href'=>'tickets.php?status=open', 'iconclass'=>'Ticket'));
    if($stats['answered']) {
        $nav->addSubMenu(array('desc'=>'Отвечено ('.$stats['answered'].')',
                           'title'=>'Отвеченные Заявки', 'href'=>'tickets.php?status=answered', 'iconclass'=>'answeredTickets'));
    }
}

if(!$sysnotice && $stats['assigned']>10)
    $sysnotice=$stats['assigned'].' assigned to you!';

$nav->addSubMenu(array('desc'=>'Мои Заявки ('.$stats['assigned'].')','title'=>'Назначенные Заявки',
                'href'=>'tickets.php?status=assigned','iconclass'=>'assignedTickets'));

if($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>'Просрочено ('.$stats['overdue'].')','title'=>'Просроченные Заявки',
                    'href'=>'tickets.php?status=overdue','iconclass'=>'overdueTickets'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=$stats['overdue'] .' overdue tickets!';
}

$nav->addSubMenu(array('desc'=>'Закрытые ('.$stats['closed'].')','title'=>'Закрытые Заявки', 'href'=>'tickets.php?status=closed', 'iconclass'=>'closedTickets'));

$acrchivedClass = (isset($_REQUEST['status']) && $_REQUEST['status']=='archived') ? 'archivedTickets' : 'closedTickets';

$nav->addSubMenu(array('desc'=>'Архив ('.$stats2['archived'].')','title'=>'Архив', 'href'=>'tickets.php?status=archived', 'iconclass'=>$acrchivedClass));


$inc=$page?$page:'tickets.inc.php';

if(!$_POST && ($_REQUEST['a'] ?? '')!='search' && !strcmp($inc,'tickets.inc.php') && ($min=$thisuser->getRefreshRate())){
    define('AUTO_REFRESH',1);
}

require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
unset($_POST);
?>
