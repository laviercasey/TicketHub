<?php
include_once(INCLUDE_DIR.'class.staff.php');
include_once(INCLUDE_DIR.'class.email.php');
include_once(INCLUDE_DIR.'class.dept.php');
include_once(INCLUDE_DIR.'class.topic.php');
include_once(INCLUDE_DIR.'class.lock.php');
include_once(INCLUDE_DIR.'class.banlist.php');


class Ticket{

    public $id;
    public $extid;
    public $email;
    public $status;
    public $created;
    public $updated;
    public $lastrespdate;
    public $lastmsgdate;
    public $duedate;
    public $priority;
    public $priority_id;
    public $fullname;
    public $staff_id;
	public $andstaffs_id;
    public $dept_id;
    public $topic_id;
    public $dept_name;
    public $subject;
    public $helptopic;
    public $overdue;

    public $closed;
    public $lock_id;
    public $row;

    public $lastMsgId;

    public $dept;
    public $staff;
    public $topic;
    public $tlock;

    public function __construct($id,$exid=false){
        $this->load($id);
    }

    function load($id) {


        $sql =' SELECT  ticket.*,topic.topic_id as topicId,lock_id,dept_name,priority_desc FROM '.TICKET_TABLE.' ticket '.
              ' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id '.
              ' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON ticket.priority_id=pri.priority_id '.
              ' LEFT JOIN '.TOPIC_TABLE.' topic ON ticket.topic_id=topic.topic_id '.
              ' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW() '.
              ' WHERE ticket.ticket_id='.db_input($id);
        if(($res=db_query($sql)) && db_num_rows($res)):
            $row=db_fetch_array($res);
            $this->id       =$row['ticket_id'];
            $this->extid    =$row['ticketID'];
            $this->email    =$row['email'];
            $this->fullname =$row['name'];
            $this->status   =$row['status'];
            $this->created  =$row['created'];
            $this->updated  =$row['updated'];
            $this->duedate  =$row['duedate'];
            $this->closed   =$row['closed'];
            $this->lastmsgdate  =$row['lastmessage'];
            $this->lastrespdate  =$row['lastresponse'];
            $this->lock_id  =$row['lock_id'];
            $this->priority_id=$row['priority_id'];
            $this->priority=$row['priority_desc'];
            $this->staff_id =$row['staff_id'];
			$this->andstaffs_id = $row['andstaffs_id'];
            $this->dept_id  =$row['dept_id'];
            $this->topic_id  =$row['topicId'];
            $this->dept_name    =$row['dept_name'];
            $this->subject =$row['subject'];
            $this->helptopic =$row['helptopic'];
            $this->overdue =$row['isoverdue'];
            $this->row=$row;
            $this->staff=array();
            $this->dept=array();
            return true;
        endif;
        return false;
    }

    function reload() {
        return $this->load($this->id);
    }

    function isOpen(){
        return (strcasecmp($this->getStatus(),'Open')==0)?true:false;
    }

    function isClosed() {
        return (strcasecmp($this->getStatus(),'Closed')==0)?true:false;
    }

    function isAssigned() {
        return $this->getStaffId()?true:false;
    }

    function isOverdue() {
        return $this->overdue?true:false;
    }

    function isLocked() {
        return $this->lock_id?true:false;
    }

    function getId(){
        return  $this->id;
    }

    function getExtId(){
        return  $this->extid;
    }

    function getEmail(){
        return $this->email;
    }

    function getName(){
        return $this->fullname;
    }

    function getSubject() {
        return $this->subject;
    }

    function getHelpTopic() {
        if($this->topic_id && ($topic=$this->getTopic()))
            return $topic->getName();

        return $this->helptopic;
    }

    function getCreateDate(){
        return $this->created;
    }

    function getUpdateDate(){
        return $this->updated;
    }

    function getDueDate(){
        return $this->duedate;
    }

    function getCloseDate(){
        return $this->closed;
    }

    function getStatus(){
        return $this->status;
    }

    function getDeptId(){
       return $this->dept_id;
    }

    function getDeptName(){
       return $this->dept_name;
    }

    function getPriorityId() {
        return $this->priority_id;
    }

    function getPriority() {
        return $this->priority;
    }

    function getPhone() {
        return $this->row['phone'];
    }

    function getPhoneExt() {
        return $this->row['phone_ext'];
    }

    function getPhoneNumber(){
        $phone=Format::phone($this->getPhone());
        if(($ext=$this->getPhoneExt()))
            $phone.=" $ext";

        return $phone;
    }

    function getSource() {
        return $this->row['source'];
    }

    function getIP() {
        return $this->row['ip_address'];
    }

    function getLock(){

        if(!$this->tlock && $this->lock_id)
            $this->tlock= new TicketLock($this->lock_id);

        return $this->tlock;
    }

    function acquireLock() {
        global $thisuser,$cfg;

        if(!$thisuser or !$cfg->getLockTime())
            return null;

        if(($lock=$this->getLock()) && !$lock->isExpired()) {
            if($lock->getStaffId()!=$thisuser->getId())
                return null;
            $lock->renew();

            return $lock;
        }
        $this->tlock=null;
        $this->lock_id=TicketLock::acquire($this->getId(),$thisuser->getId());
        return $this->getLock();
    }

    function getDept(){

        if(!$this->dept && $this->dept_id)
            $this->dept= new Dept($this->dept_id);
        return $this->dept;
    }

    function getStaffId(){
        return $this->staff_id;
    }

    function getStaffsIdWithStar(){
        return $this->andstaffs_id;
    }

	function getStaffsId(){
		return str_replace('*','', $this->andstaffs_id ?? '');
	}

    function getStaffsIdNames($mystaffs_id = '', $mystaff_id = ''){
        if ($mystaffs_id == '') {
            $mystaffs_id = $this->andstaffs_id;
            $mystaff_id = $this->staff_id;
            $this->andstaffs_id = $this->getStaffsId();
        }
        $mystaffs_id = str_replace('*','', $mystaffs_id ?? '');
        if (empty($mystaffs_id)) return '';
        $ids = array_filter(array_map('intval', explode(',', $mystaffs_id)));
        if (empty($ids)) return '';
        $in_clause = implode(',', $ids);
        $sql=' SELECT staff_id,CONCAT_WS(" ",firstname,lastname) as name FROM '.STAFF_TABLE.
            ' WHERE isactive=1 AND onvacation=0 AND staff_id IN (' . $in_clause . ')';
        $staffs= db_query($sql.' ORDER BY firstname,lastname ');
        $mystaffs = '';
        while (list($staffId,$staffName) = db_fetch_row($staffs)) {
            $zpt = empty($mystaffs) ? '' : ', ';
            $a1 = '';
            $a2 = '';
            if ($staffId == $mystaff_id) {
                $a1 = '<a style="color:Red;">';
                $a2 = '</a>';
            }
            $mystaffs .= $zpt . $a1 . htmlspecialchars(Ticket::GetShortFIO($staffName), ENT_QUOTES | ENT_HTML5, 'UTF-8') . $a2;
        }
        return $mystaffs;
    }

    static function GetShortFIO($fio){
        if (empty($fio)) return '';
        $tmp_fio = explode(' ', $fio);
        if (count($tmp_fio) == 1) {
            return $tmp_fio[0];
        } else {
            return $tmp_fio[0] . ' ' . $tmp_fio[1];
        }
    }

    function getStaff(){

        if(!$this->staff && $this->staff_id)
            $this->staff= new Staff($this->staff_id);
        return $this->staff;
    }

    function getTopicId(){
        return $this->topic_id;
    }

    function getTopic(){

        if(!$this->topic && $this->topic_id)
            $this->topic= new Topic($this->topic_id);

        return $this->topic;
    }

    function getAdditionalStaff() {

        $staffList = array();

        $staffIds = $this->getStaffsId();

        if(empty($staffIds)) {
            return $staffList;
        }

        $ids = explode(',', $staffIds);

        foreach($ids as $id) {
            $id = trim($id);
            if(!empty($id) && is_numeric($id)) {
                $staff = new Staff($id);
                if($staff && $staff->getId() && $staff->isAvailable()) {
                    $staffList[] = $staff;
                }
            }
        }

        return $staffList;
    }

    function getLastRespondent() {

        $sql ='SELECT  resp.staff_id FROM '.TICKET_RESPONSE_TABLE.' resp LEFT JOIN '.STAFF_TABLE. ' USING(staff_id) '.
            ' WHERE  resp.ticket_id='.db_input($this->getId()).' AND resp.staff_id>0  ORDER BY resp.created DESC LIMIT 1';
        $res=db_query($sql);
        if($res && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return ($id)?new Staff($id):null;

    }

    function getLastMessageDate() {


        if($this->lastmsgdate)
            return $this->lastmsgdate;

        $createDate=0;
        $sql ='SELECT created FROM '.TICKET_MESSAGE_TABLE.' WHERE ticket_id='.db_input($this->getId()).' ORDER BY created DESC LIMIT 1';
        if(($res=db_query($sql)) && db_num_rows($res))
            list($createDate)=db_fetch_row($res);

        return $createDate;
    }

    function getLastResponseDate() {


        if($this->lastrespdate)
            return $this->lastrespdate;

        $createDate=0;
        $sql ='SELECT created FROM '.TICKET_RESPONSE_TABLE.' WHERE ticket_id='.db_input($this->getId()).' ORDER BY created DESC LIMIT 1';
        if(($res=db_query($sql)) && db_num_rows($res))
            list($createDate)=db_fetch_row($res);

        return $createDate;
    }

    function getRelatedTicketsCount(){

        $num=0;
        $sql='SELECT count(*)  FROM '.TICKET_TABLE.' WHERE email='.db_input($this->getEmail());
        if(($res=db_query($sql)) && db_num_rows($res))
            list($num)=db_fetch_row($res);

        return $num;
    }

    function getLastMsgId() {
        return $this->lastMsgId;
    }

    function setLastMsgId($msgid) {
        return $this->lastMsgId=$msgid;
    }
    function setPriority($priority_id){

        if(!$priority_id)
            return false;

        $sql='UPDATE '.TICKET_TABLE.' SET priority_id='.db_input($priority_id).',updated=NOW() WHERE ticket_id='.db_input($this->getId());
        if(db_query($sql) && db_affected_rows()){
            return true;
        }
        return false;

    }
    function setDeptId($deptId){

        if(!$deptId)
            return false;

        $sql= 'UPDATE '.TICKET_TABLE.' SET dept_id='.db_input($deptId).' WHERE ticket_id='.db_input($this->getId());
        return (db_query($sql) && db_affected_rows())?true:false;
    }

    function setStaffId($staffId){
      $sql= 'UPDATE '.TICKET_TABLE.' SET staff_id='.db_input($staffId).' WHERE ticket_id='.db_input($this->getId());
      return (db_query($sql)  && db_affected_rows())?true:false;
    }

	function setandstaffs_id($staffsId){
      $sql= 'UPDATE '.TICKET_TABLE.' SET andstaffs_id='.db_input($staffsId).' WHERE ticket_id='.db_input($this->getId());
        return db_query($sql);
    }

    function notifyNewAdditionalStaff($newStaffsId, $oldStaffsId, $message) {
        global $thisuser, $cfg;

        if (empty($newStaffsId)) {
            return;
        }

        $oldIds = array();
        if (!empty($oldStaffsId)) {
            $cleanOldStaffs = str_replace('*', '', $oldStaffsId);
            $oldIds = explode(',', $cleanOldStaffs);
            $oldIds = array_map('trim', $oldIds);
            $oldIds = array_filter($oldIds, 'is_numeric');
        }

        $newStaffsIdClean = str_replace('*', '', $newStaffsId);
        $newIds = explode(',', $newStaffsIdClean);
        $newIds = array_map('trim', $newIds);
        $newIds = array_filter($newIds, 'is_numeric');

        $addedIds = array_diff($newIds, $oldIds);

        if (empty($addedIds)) {
            return;
        }

        $dept = $this->getDept();
        if (!$dept || !($tplId = $dept->getTemplateId())) {
            $tplId = $cfg->getDefaultTemplateId();
        }

        $sql = 'SELECT assigned_alert_subj, assigned_alert_body FROM ' . EMAIL_TEMPLATE_TABLE .
               ' WHERE cfg_id=' . db_input($cfg->getId()) . ' AND tpl_id=' . db_input($tplId);
        $resp = db_query($sql);

        if (!$resp || !db_num_rows($resp)) {
            return;
        }

        list($subj, $body) = db_fetch_row($resp);

        $body = $this->replaceTemplateVars($body);
        $subj = $this->replaceTemplateVars($subj);
        $body = str_replace('%note', $message, $body);
        $body = str_replace('%message', $message, $body);
        $body = str_replace('%assigner', ($thisuser) ? $thisuser->getName() : 'System', $body);

        $email = $cfg->getAlertEmail();
        if (!$email) {
            $email = $cfg->getDefaultEmail();
        }

        if (!$email) {
            return;
        }

        $sentlist = array();
        foreach ($addedIds as $staffId) {
            $staff = new Staff($staffId);
            if (!$staff || !$staff->getId() || !$staff->isAvailable() || !$staff->getEmail()) {
                continue;
            }
            if (in_array($staff->getEmail(), $sentlist)) {
                continue;
            }
            if ($thisuser && $staff->getId() == $thisuser->getId()) {
                continue;
            }

            $alert = str_replace('%assignee', $staff->getName(), $body);
            $email->send($staff->getEmail(), $subj, $alert);
            $sentlist[] = $staff->getEmail();
        }
    }

    function setStatus($status){

        if(strcasecmp($this->getStatus(),$status)==0)
            return true;

        switch(strtolower($status)):
        case 'reopen':
        case 'open':
            return $this->reopen();
            break;
        case 'close':
            return $this->close();
         break;
        endswitch;

        return false;
    }

    function setAnswerState($isanswered) {
        db_query('UPDATE '.TICKET_TABLE.' SET isanswered='.db_input($isanswered).' WHERE ticket_id='.db_input($this->getId()));
    }

    function close(){
        $sql= 'UPDATE '.TICKET_TABLE.' SET status='.db_input('closed').',isoverdue=0,duedate=NULL,updated=NOW(),closed=NOW() '.
            ' WHERE ticket_id='.db_input($this->getId());
        return (db_query($sql) && db_affected_rows())?true:false;
    }
    function reopen($isanswered=0){
        global $thisuser;
        $sql= 'UPDATE '.TICKET_TABLE.' SET status='.db_input('open').',isanswered=0,updated=NOW(),reopened=NOW() WHERE ticket_id='.db_input($this->getId());
        return (db_query($sql) && db_affected_rows())?true:false;
    }


    function onResponse(){
        db_query('UPDATE '.TICKET_TABLE.' SET isanswered=1,lastresponse=NOW(), updated=NOW() WHERE ticket_id='.db_input($this->getId()));
    }

    function onMessage(){
        db_query('UPDATE '.TICKET_TABLE.' SET isanswered=0,lastmessage=NOW() WHERE ticket_id='.db_input($this->getId()));
    }

    function onNote(){

    }

    function onOverdue() {

    }

    function replaceTemplateVars($text){
        global $cfg;

        $dept = $this->getDept();
        $staff= $this->getStaff();

        $search = array('/%id/','/%ticket/','/%email/','/%name/','/%subject/','/%topic/','/%phone/','/%status/','/%priority/',
                        '/%dept/','/%assigned_staff/','/%createdate/','/%duedate/','/%closedate/','/%url/');
        $replace = array($this->getId(),
                         $this->getExtId(),
                         $this->getEmail(),
                         $this->getName(),
                         $this->getSubject(),
                         $this->getHelpTopic(),
                         $this->getPhoneNumber(),
                         $this->getStatus(),
                         $this->getPriority(),
                         ($dept?$dept->getName():''),
                         ($staff?$staff->getName():''),
                         Format::db_daydatetime($this->getCreateDate()),
                         Format::db_daydatetime($this->getDueDate()),
                         Format::db_daydatetime($this->getCloseDate()),
                         $cfg->getBaseUrl());
        return preg_replace($search,$replace,$text);
    }




    function markUnAnswered() {
        $this->setAnswerState(0);
    }

    function markAnswered(){
        $this->setAnswerState(1);
    }

    function markOverdue($sendAlert=false) {
        global $cfg;

        if($this->isOverdue())
            return true;

        $sql= 'UPDATE '.TICKET_TABLE.' SET isoverdue=1,updated=NOW() WHERE ticket_id='.db_input($this->getId());
        if(db_query($sql) && db_affected_rows()) {
            $dept=$this->getDept();

            if(!$dept || !($tplId=$dept->getTemplateId()))
                $tplId=$cfg->getDefaultTemplateId();

            if($sendAlert && $cfg->alertONOverdueTicket()){
                $sql='SELECT ticket_overdue_subj,ticket_overdue_body FROM '.EMAIL_TEMPLATE_TABLE.
                     ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);
                if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){

                    $body=$this->replaceTemplateVars($body);
                    $subj=$this->replaceTemplateVars($subj);

                    if(!($email=$cfg->getAlertEmail()))
                        $email=$cfg->getDefaultEmail();

                    if($email && $email->getId()) {
                        $alert = str_replace("%staff",'Admin',$body);
                        $email->send($cfg->getAdminEmail(),$subj,$alert);

                        $recipients=array();
                        if($this->isAssigned() && $cfg->alertAssignedONOverdueTicket()){
                            $recipients[]=$this->getStaff();
                        }elseif($cfg->alertDeptMembersONOverdueTicket()){
                            $sql='SELECT staff_id FROM '.STAFF_TABLE.' WHERE dept_id='.db_input($dept->getId());
                            if(($users=db_query($sql)) && db_num_rows($users)) {
                                while(list($id)=db_fetch_row($users))
                                    $recipients[]= new Staff($id);
                            }
                        }
                        if($cfg->alertDeptManagerONOverdueTicket() && $dept) {
                            $recipients[]=$dept->getManager();
                        }

                        $sentlist=array();
                        foreach( $recipients as $k=>$staff){
                            if(!$staff || !is_object($staff) || !$staff->isAvailable()) continue;
                            if(in_array($staff->getEmail(),$sentlist)) continue;
                            $alert = str_replace("%staff",$staff->getFirstName(),$body);
                            $email->send($staff->getEmail(),$subj,$alert);
                        }
                    }
                }else {
                    Sys::log(LOG_WARNING,'Template Fetch Error',"Unable to fetch 'overdue' alert template #$tplId");
                }
            }
            return true;
        }
        return false;
    }


    function transfer($deptId){
        global $cfg;
        return $this->setDeptId($deptId)?true:false;
    }

    function assignStaff($staffId,$message,$alertstaff=true) {
        global $thisuser,$cfg;


        $staff = new Staff($staffId);
        if(!$staff || !$staff->isAvailable() || !$thisuser)
            return false;

        if($this->setStaffId($staff->getId())){
            if($this->isClosed())
                $this->reopen();
            $this->reload();
            if($alertstaff && ($thisuser && $staff->getId()!=$thisuser->getId())) {
                $dept=$this->getDept();
                if(!$dept || !($tplId=$dept->getTemplateId()))
                    $tplId=$cfg->getDefaultTemplateId();

                $sql='SELECT assigned_alert_subj,assigned_alert_body FROM '.EMAIL_TEMPLATE_TABLE.
                 ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);
                if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){

                    $body=$this->replaceTemplateVars($body);
                    $subj=$this->replaceTemplateVars($subj);
                    $body = str_replace('%note',$message,$body);
                    $body = str_replace("%message", $message,$body);
                    $body = str_replace("%assignee", $staff->getName(),$body);
                    $body = str_replace("%assigner", ($thisuser)?$thisuser->getName():'System',$body);

                    if(!($email=$cfg->getAlertEmail()))
                        $email=$cfg->getDefaultEmail();

                    if($email) {
                        $email->send($staff->getEmail(),$subj,$body);

                        $additionalStaff = $this->getAdditionalStaff();
                        $sentlist = array($staff->getEmail());

                        foreach($additionalStaff as $addstaff) {
                            if(!$addstaff || !is_object($addstaff) || !$addstaff->getEmail()) continue;
                            if(in_array($addstaff->getEmail(), $sentlist)) continue;
                            if($thisuser && $addstaff->getId()==$thisuser->getId()) continue;

                            $alert = str_replace("%assignee", $addstaff->getName(), $body);
                            $email->send($addstaff->getEmail(), $subj, $alert);
                            $sentlist[] = $addstaff->getEmail();
                        }
                    }
                }else {
                    Sys::log(LOG_WARNING,'Template Fetch Error',"Unable to fetch 'assigned' alert template #$tplId");
                }
            }
            $message=$message?$message:'Ticket assigned';
            $this->postNote('Ticket Assigned to '.$staff->getName(),$message,false);
            return true;
        }
        return false;
    }
    function release(){
        global $thisuser;

        if(!$this->isAssigned())
            return true;

        return $this->setStaffId(0)?true:false;
    }

    function postMessage($msg,$source='',$msgid=NULL,$headers='',$newticket=false){
        global $cfg;

        if(!$this->getId())
            return 0;

        $source=$source?$source:$_SERVER['REMOTE_ADDR'];

        $sql='INSERT INTO '.TICKET_MESSAGE_TABLE.' SET created=NOW() '.
             ',ticket_id='.db_input($this->getId()).
             ',messageId='.db_input($msgid).
             ',message='.db_input(Format::striptags($msg)).
             ',headers='.db_input($headers).
             ',source='.db_input($source).
             ',ip_address='.db_input($_SERVER['REMOTE_ADDR']);

        if(db_query($sql) && ($msgid=db_insert_id())) {
            $this->setLastMsgId($msgid);
            $this->onMessage();
            if(!$newticket){
                $dept =$this->getDept();

                if(!$this->isOpen()) {
                    $this->reopen();
                    if($cfg->autoAssignReopenedTickets() && ($lastrep=$this->getLastRespondent())) {
                        if($lastrep->isAvailable() && $lastrep->canAccessDept($this->getDeptId())
                                && (time()-strtotime($this->getLastResponseDate()))<=90*24*3600) {
                            $this->setStaffId($lastrep->getId());
                        }
                    }
                }

                if(!$dept || !($tplId=$dept->getTemplateId()))
                    $tplId=$cfg->getDefaultTemplateId();


                $autorespond=true;
                if(Email::getIdByEmail($this->getEmail()))
                    $autorespond=false;
                elseif(strpos(strtolower($this->getEmail()),'mailer-daemon@')!==false || strpos(strtolower($this->getEmail()),'postmaster@')!==false)
                    $autorespond=false;


                if($autorespond && $cfg->autoRespONNewMessage() && $dept && $dept->autoRespONNewMessage()){

                    $sql='SELECT message_autoresp_subj,message_autoresp_body FROM '.EMAIL_TEMPLATE_TABLE.
                         ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);
                    if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){

                        $body=$this->replaceTemplateVars($body);
                        $subj=$this->replaceTemplateVars($subj);
                        $body = str_replace('%signature',($dept && $dept->isPublic())?$dept->getSignature():'',$body);
                        if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                            $body ="\n$tag\n\n".$body;

                        if(!$dept || !($email=$dept->getAutoRespEmail()))
                            $email=$cfg->getDefaultEmail();

                        if($email) {
                            $email->send($this->getEMail(),$subj,$body);
                        }

                    }else {
                        Sys::log(LOG_WARNING,'Template Fetch Error',"Unable to fetch 'new message' auto response template #$tplId");
                    }
                }
                if($cfg->alertONNewMessage()){
                    $sql='SELECT message_alert_subj,message_alert_body FROM '.EMAIL_TEMPLATE_TABLE.
                         ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);

                    $resp=db_query($sql);
                    if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){

                        $body=$this->replaceTemplateVars($body);
                        $subj=$this->replaceTemplateVars($subj);
                        $body = str_replace("%message", $msg,$body);

                        if(!($email=$cfg->getAlertEmail()))
                            $email =$cfg->getDefaultEmail();

                        if($email && $email->getId()) {
                            $recipients=array();
                            if($cfg->alertLastRespondentONNewMessage() || $cfg->alertAssignedONNewMessage())
                                $recipients[]=$this->getLastRespondent();
                            if($this->isAssigned())
                                $recipients[]=$this->getStaff();
                            if($cfg->alertDeptManagerONNewMessage() && $dept)
                                $recipients[]=$dept->getManager();
                            $additionalStaff = $this->getAdditionalStaff();
                            foreach($additionalStaff as $addstaff) {
                                if($addstaff && is_object($addstaff)) {
                                    $recipients[] = $addstaff;
                                }
                            }

                            $sentlist=array();
                            foreach( $recipients as $k=>$staff){
                                if(!$staff || !is_object($staff) || !$staff->getEmail() || !$staff->isAvailable()) continue;
                                if(in_array($staff->getEmail(),$sentlist)) continue;
                                $alert = str_replace("%staff",$staff->getFirstName(),$body);
                                $email->send($staff->getEmail(),$subj,$alert);
                                $sentlist[]=$staff->getEmail();
                            }
                        }
                    }else {
                        Sys::log(LOG_WARNING,'Template Fetch Error',"Unable to fetch 'new message' alert template #$tplId");
                    }
                }

            }
        }
        return $msgid;
    }

    function postResponse($msgid,$response,$signature='none',$attachment=false,$canalert=true){
        global $thisuser,$cfg;

        if(!$thisuser || !$thisuser->getId() || !$thisuser->isStaff())
            return 0;


        $sql= 'INSERT INTO '.TICKET_RESPONSE_TABLE.' SET created=NOW() '.
                ',ticket_id='.db_input($this->getId()).
                ',msg_id='.db_input($msgid).
                ',response='.db_input(Format::striptags($response)).
                ',staff_id='.db_input($thisuser->getId()).
                ',staff_name='.db_input($thisuser->getName()).
                ',ip_address='.db_input($thisuser->getIP());
        $resp_id=0;
        if(db_query($sql) && ($resp_id=db_insert_id())):
            $this->onResponse();
            if(!$canalert)
                return $resp_id;

            $dept=$this->getDept();
            if(!$dept || !($tplId=$dept->getTemplateId()))
                $tplId=$cfg->getDefaultTemplateId();


            $sql='SELECT ticket_reply_subj,ticket_reply_body FROM '.EMAIL_TEMPLATE_TABLE.
                ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);
            if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){

                $body=$this->replaceTemplateVars($body);
                $subj=$this->replaceTemplateVars($subj);
                $body = str_replace('%response',$response,$body);
                switch(strtolower($signature)):
                case 'mine';
                $signature=$thisuser->getSignature();
                break;
                case 'dept':
                $signature=($dept && $dept->isPublic())?$dept->getSignature():'';
                break;
                case 'none';
                default:
                $signature='';
                break;
                endswitch;
                $body = str_replace("%signature",$signature,$body);

                $file=null;
                if(($attachment && is_file($attachment['tmp_name'])) && $cfg->emailAttachments()) {
                    $file=array('file'=>$attachment['tmp_name'], 'name'=>$attachment['name'], 'type'=>$attachment['type']);
                }

                if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                    $body ="\n$tag\n\n".$body;

                if(!$dept || !($email=$dept->getEmail()))
                    $email =$cfg->getDefaultEmail();

                if($email && $email->getId()) {
                    $email->send($this->getEmail(),$subj,$body,$file);
                }
            }else{

                $msg='Problems fetching response template for ticket#'.$this->getId().' Possible config error - template #'.$tplId;
                Sys::alertAdmin('System Error',$msg);
            }
            return $resp_id;
        endif;

        return 0;
    }

    function logActivity($title,$note){
        global $cfg;

        if(!$cfg || !$cfg->logTicketActivity())
            return 0;

        return $this->postNote($title,$note,false,'system');
    }

    function postNote($title,$note,$alert=true,$poster='') {
        global $thisuser,$cfg;

        $sql= 'INSERT INTO '.TICKET_NOTE_TABLE.' SET created=NOW() '.
                ',ticket_id='.db_input($this->getId()).
                ',title='.db_input(Format::striptags($title)).
                ',note='.db_input(Format::striptags($note)).
                ',staff_id='.db_input($thisuser?$thisuser->getId():0).
                ',source='.db_input(($poster || !$thisuser)?$poster:$thisuser->getName());
        if(db_query($sql) && ($id=db_insert_id())) {
            if($alert && $cfg->alertONNewNote()){
                $dept=$this->getDept();
                if(!$dept || !($tplId=$dept->getTemplateId()))
                    $tplId=$cfg->getDefaultTemplateId();

                $sql='SELECT note_alert_subj,note_alert_body FROM '.EMAIL_TEMPLATE_TABLE.
                     ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);
                if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){
                    $body=$this->replaceTemplateVars($body);
                    $subj=$this->replaceTemplateVars($subj);
                    $body = str_replace('%note',"$title\n\n$note",$body);

                    if(!($email=$cfg->getAlertEmail()))
                        $email =$cfg->getDefaultEmail();

                    if($email && $email->getId()) {
                        $recipients=array();
                        if($cfg->alertLastRespondentONNewNote() || $cfg->alertAssignedONNewNote())
                            $recipients[]=$this->getLastRespondent();
                        if($this->isAssigned())
                            $recipients[]=$this->getStaff();
                        if($cfg->alertDeptManagerONNewNote() && $dept)
                            $recipients[]=$dept->getManager();
                        $additionalStaff = $this->getAdditionalStaff();
                        foreach($additionalStaff as $addstaff) {
                            if($addstaff && is_object($addstaff)) {
                                $recipients[] = $addstaff;
                            }
                        }

                        $sentlist=array();
                        foreach( $recipients as $k=>$staff){
                            if(!$staff || !is_object($staff) || !$staff->getEmail() || !$staff->isAvailable()) continue;
                            if(in_array($staff->getEmail(),$sentlist) || ($thisuser && $thisuser->getId()==$staff->getId())) continue;
                            $alert = str_replace('%staff',$staff->getFirstName(),$body);
                            $email->send($staff->getEmail(),$subj,$alert);
                            $sentlist[]=$staff->getEmail();
                        }
                    }
                }else {
                    Sys::log(LOG_WARNING,'Template Fetch Error',"Unable to fetch 'new note' alert template #$tplId");
                }

            }
        }
        return $id;
    }


    function uploadAttachment($file, $refid, $type) {
        if (!$file['tmp_name'] || !$refid || !$type)
            return 0;

        require_once(INCLUDE_DIR . 'class.fileupload.php');
        $errors = [];
        $info = FileUpload::process($file, 'tickets', 0, $errors);
        if (!$info) return 0;

        $sql = 'INSERT INTO ' . TICKET_ATTACHMENT_TABLE . ' SET created=NOW()'
             . ',ticket_id=' . db_input($this->getId())
             . ',ref_id='    . db_input($refid)
             . ',ref_type='  . db_input($type)
             . ',file_size=' . db_input($info['file_size'])
             . ',file_name=' . db_input($info['file_name'])
             . ',file_key='  . db_input($info['file_key']);

        if (db_query($sql) && ($id = db_insert_id()))
            return $id;

        FileUpload::delete($info['file_path']);
        return 0;
    }

    function saveAttachment($name,$data,$refid,$type){
       global $cfg;

        if(!$refid ||!$name || !$data)
            return 0;

        $dir=$cfg->getUploadDir();
        $rand=Misc::randCode(16);
        $name=Format::file_name($name);
        $month=date('my',strtotime($this->getCreateDate()));

        if(!file_exists(rtrim($dir,'/').'/'.$month))
            @mkdir(rtrim($dir,'/').'/'.$month,0755);

        if(is_writable(rtrim($dir,'/').'/'.$month))
            $filename=sprintf("%s/%s/%s_%s",rtrim($dir,'/'),$month,$rand,$name);
        else
            $filename=rtrim($dir,'/').'/'.$rand.'_'.$name;

        if(($fp=fopen($filename,'w'))) {
            fwrite($fp,$data);
            fclose($fp);
            $size=@filesize($filename);
            $sql ='INSERT INTO '.TICKET_ATTACHMENT_TABLE.' SET created=NOW() '.
                  ',ticket_id='.db_input($this->getId()).
                  ',ref_id='.db_input($refid).
                  ',ref_type='.db_input($type).
                  ',file_size='.db_input($size).
                  ',file_name='.db_input($name).
                  ',file_key='.db_input($rand);
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;

             @unlink($filename);
        }
        return 0;
    }

    function delete(){


        if(db_query('DELETE FROM '.TICKET_TABLE.' WHERE ticket_id='.db_input($this->getId())) && db_affected_rows()):
            db_query('DELETE FROM '.TICKET_MESSAGE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
            db_query('DELETE FROM '.TICKET_RESPONSE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
            db_query('DELETE FROM '.TICKET_NOTE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
            $this->deleteAttachments();
            return TRUE;
        endif;

        return FALSE;
    }

    function fixAttachments(){
        global $cfg;

        $sql='SELECT attach_id,file_name,file_key FROM '.TICKET_ATTACHMENT_TABLE.' WHERE ticket_id='.db_input($this->getId());
        $res=db_query($sql);
        if($res && db_num_rows($res)) {
            $dir=$cfg->getUploadDir();
            $month=date('my',strtotime($this->getCreateDate()));
            while(list($id,$name,$key)=db_fetch_row($res)){
                $origfilename=sprintf("%s/%s_%s",rtrim($dir,'/'),$key,$name);
                if(!file_exists($origfilename)) continue;

                if(!file_exists(rtrim($dir,'/').'/'.$month))
                    @mkdir(rtrim($dir,'/').'/'.$month,0777);
                if(!is_writable(rtrim($dir,'/').'/'.$month)) continue;

                $filename=sprintf("%s/%s/%s_%s",rtrim($dir,'/'),$month,$key,$name);
                if(rename($origfilename,$filename) && file_exists($filename)) {
                    @unlink($origfilename);
                }
            }

        }
    }

    function deleteAttachments(){
        global $cfg;

        $sql='SELECT attach_id,file_name,file_key FROM '.TICKET_ATTACHMENT_TABLE.' WHERE ticket_id='.db_input($this->getId());
        $res=db_query($sql);
        if($res && db_num_rows($res)) {
            $dir=$cfg->getUploadDir();
            $month=date('my',strtotime($this->getCreateDate()));
            $ids=array();
            while(list($id,$name,$key)=db_fetch_row($res)){
                $filename=sprintf("%s/%s/%s_%s",rtrim($dir,'/'),$month,$key,$name);
                if(!file_exists($filename))
                    $filename=sprintf("%s/%s_%s",rtrim($dir,'/'),$key,$name);
                @unlink($filename);
                $ids[]=$id;
            }
            if($ids){
                db_query('DELETE FROM '.TICKET_ATTACHMENT_TABLE.' WHERE attach_id IN('.implode(',',$ids).') AND ticket_id='.db_input($this->getId()));
            }
            return TRUE;
        }
        return FALSE;
    }

    function getAttachmentStr($refid,$type){

        $sql ='SELECT attach_id,file_size,file_name FROM '.TICKET_ATTACHMENT_TABLE.
             ' WHERE deleted=0 AND ticket_id='.db_input($this->getId()).' AND ref_id='.db_input($refid).' AND ref_type='.db_input($type);
        $res=db_query($sql);
        if($res && db_num_rows($res)){
            while(list($id,$size,$name)=db_fetch_row($res)){
                $hash=hash('sha256', $this->getId().'_'.$refid.'_'.session_id());
                $size=Format::file_size($size);
                $name=Format::htmlchars($name);
                $attachstr.= "<a class='Icon file' href='attachment.php?id=$id&ref=$hash' target='_blank'><b>$name</b></a>&nbsp;(<i>$size</i>)&nbsp;&nbsp;";
            }
        }
        return ($attachstr);
    }

    static function getIdByExtId($extid) {
        $sql ='SELECT  ticket_id FROM '.TICKET_TABLE.' ticket WHERE ticketID='.db_input($extid);
        $res=db_query($sql);
        if($res && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    static function genExtRandID() {
        global $cfg;

        $id=Misc::randNumber(EXT_TICKET_ID_LEN);
        if(db_num_rows(db_query('SELECT ticket_id FROM '.TICKET_TABLE.' WHERE ticketID='.db_input($id))))
            return Ticket::genExtRandID();

        return $id;
    }

    static function getIdByMessageId($mid,$email) {

        if(!$mid || !$email)
            return 0;

        $sql='SELECT ticket.ticket_id FROM '.TICKET_TABLE. ' ticket '.
             ' LEFT JOIN '.TICKET_MESSAGE_TABLE.' msg USING(ticket_id) '.
             ' WHERE messageId='.db_input($mid).' AND email='.db_input($email);
        $id=0;
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }


    static function getOpenTicketsByEmail($email){

        $sql='SELECT count(*) as open FROM '.TICKET_TABLE.' WHERE status='.db_input('open').' AND email='.db_input($email);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($num)=db_fetch_row($res);

        return $num;
    }

    function update($var,&$errors) {
         global $cfg,$thisuser;

         $fields=array();
         $fields['name']     = array('type'=>'string',   'required'=>1, 'error'=>'Введите Имя');
         $fields['email']    = array('type'=>'email',    'required'=>1, 'error'=>'Введите Email');
         $fields['note']     = array('type'=>'text',     'required'=>1, 'error'=>'Введите причину изменения');
         $fields['subject']  = array('type'=>'string',   'required'=>1, 'error'=>'Введите тему');
         $fields['topicId']  = array('type'=>'int',      'required'=>0, 'error'=>'Неверный выбор');
         $fields['pri']      = array('type'=>'int',      'required'=>0, 'error'=>'Неверный приоритет');
         $fields['phone']    = array('type'=>'phone',    'required'=>0, 'error'=>'Введите правильный номер');
         $fields['duedate']  = array('type'=>'date',     'required'=>0, 'error'=>'Неправильная дата - формат MM/DD/YY');


         $params = new Validator($fields);
         if(!$params->validate($var)){
             $errors=array_merge($errors,$params->errors());
         }

         if($var['duedate']){
             if($this->isClosed())
                 $errors['duedate']='Duedate can NOT be set on a closed ticket';
             elseif(!$var['time'] || strpos($var['time'],':')===false)
                 $errors['time']='Select time';
             elseif(strtotime($var['duedate'].' '.$var['time'])===false)
                 $errors['duedate']='Invalid duedate';
             elseif(strtotime($var['duedate'].' '.$var['time'])<=time())
                 $errors['duedate']='Due date must be in the future';
         }

        $cleartopic=false;
        $topicDesc='';
        if($var['topicId'] && ($topic= new Topic($var['topicId'])) && $topic->getId()) {
            $topicDesc=$topic->getName();
        }elseif(!$var['topicId'] && $this->getTopicId()){
            $topicDesc='';
            $cleartopic=true;
        }


         if(!$errors){
             $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW() '.
                  ',email='.db_input($var['email']).
                  ',name='.db_input(Format::striptags($var['name'])).
                  ',subject='.db_input(Format::striptags($var['subject'])).
                  ',phone="'.db_input($var['phone'],false).'"'.
                  ',phone_ext='.db_input($var['phone_ext']?$var['phone_ext']:NULL).
                  ',priority_id='.db_input($var['pri']).
                  ',topic_id='.db_input($var['topicId']).
                  ',duedate='.($var['duedate']?db_input(date('Y-m-d G:i',Misc::dbtime($var['duedate'].' '.$var['time']))):'NULL');
             if($var['duedate']) {
                 $sql.=',isoverdue=0';
             }
             if($topicDesc || $cleartopic) {
                 $sql.=',helptopic='.db_input($topicDesc);
             }
             $sql.=' WHERE ticket_id='.db_input($this->getId());
             if(db_query($sql)){
                 $this->postNote('Заявка изменена',$var['note']);
                 $this->reload();
                 return true;
             }
         }

         return false;
    }

    static function create($var,&$errors,$origin,$autorespond=true,$alertstaff=true) {
        global $cfg,$thisclient,$_FILES;

        $id=0;
        $fields=array();
        $fields['name']     = array('type'=>'string',   'required'=>1, 'error'=>'Введите имя');
        $fields['email']    = array('type'=>'email',    'required'=>1, 'error'=>'Введите правильный email');
        $fields['subject']  = array('type'=>'string',   'required'=>1, 'error'=>'Введите заголовок');
        $fields['message']  = array('type'=>'text',     'required'=>1, 'error'=>'Введите сообщение');
        if(strcasecmp($origin,'web')==0) {
            $fields['topicId']  = array('type'=>'int',      'required'=>1, 'error'=>'Select help topic');
        }elseif(strcasecmp($origin,'staff')==0){
            $fields['deptId']   = array('type'=>'int',      'required'=>1, 'error'=>'Введите Отдел');
            $fields['source']   = array('type'=>'string',   'required'=>1, 'error'=>'Выберите Источник');
            $fields['duedate']  = array('type'=>'date',    'required'=>0, 'error'=>'Неверная дата - должна быть MM/DD/YY');
        }else {
            $fields['emailId']  = array('type'=>'int',  'required'=>1, 'error'=>'Неизвестный Email');
        }
        $fields['pri']      = array('type'=>'int',      'required'=>0, 'error'=>'Неверный приоритет');
        $fields['phone']    = array('type'=>'int',    'required'=>0, 'error'=>'Введите правильный номер телефона');

        $validate = new Validator($fields);
        if(!$validate->validate($var)){
            $errors=array_merge($errors,$validate->errors());
        }

        if(!$errors && BanList::isbanned($var['email'])) {
            $errors['err']='Ticket denied. Error #403';
            Sys::log(LOG_WARNING,'Ticket denied','Banned email - '.$var['email']);
        }

        if(!$errors && $thisclient && strcasecmp($thisclient->getEmail(),$var['email']))
            $errors['email']='Пропущен Email.';

        if($var['phone_ext'] ) {
            if(!is_numeric($var['phone_ext']) && !$errors['phone'])
                $errors['phone']='Invalid phone ext.';
            elseif(!$var['phone'])
                $errors['phone']='Phone number required';
        }

        if($var['duedate']){
            if(!$var['time'] || strpos($var['time'],':')===false)
                $errors['time']='Select time';
            elseif(strtotime($var['duedate'].' '.$var['time'])===false)
                $errors['duedate']='Invalid duedate';
            elseif(strtotime($var['duedate'].' '.$var['time'])<=time())
                $errors['duedate']='Due date must be in the future';
        }

        if($_FILES['attachment']['name'] && $cfg->allowOnlineAttachments()) {
            if(!$cfg->canUploadFileType($_FILES['attachment']['name']))
                $errors['attachment']='Неверный тип файла [ '.Format::htmlchars($_FILES['attachment']['name']).' ]';
            elseif($_FILES['attachment']['size']>$cfg->getMaxFileSize())
                $errors['attachment']='Файл слишком большой. Разрешено '.$cfg->getMaxFileSize().' байт';
        }

        if($var['email'] && !$errors && $cfg->getMaxOpenTickets()>0 && strcasecmp($origin,'staff')){
            $openTickets=Ticket::getOpenTicketsByEmail($var['email']);
            if($openTickets>=$cfg->getMaxOpenTickets()) {
                $errors['err']="You've reached the maximum open tickets allowed.";
                if($cfg->getMaxOpenTickets()==$openTickets && $cfg->sendOverlimitNotice()) {
                    if($var['deptId'])
                        $dept = new Dept($var['deptId']);

                    if(!$dept || !($tplId=$dept->getTemplateId()))
                        $tplId=$cfg->getDefaultTemplateId();

                    $sql='SELECT ticket_overlimit_subj,ticket_overlimit_body FROM '.EMAIL_TEMPLATE_TABLE.
                        ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);
                    $resp=db_query($sql);
                    if(db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){

                        $body = str_replace("%name", $var['name'],$body);
                        $body = str_replace("%email",$var['email'],$body);
                        $body = str_replace("%url", $cfg->getBaseUrl(),$body);
                        $body = str_replace('%signature',($dept && $dept->isPublic())?$dept->getSignature():'',$body);

                        if(!$dept || !($email=$dept->getAutoRespEmail()))
                            $email=$cfg->getDefaultEmail();

                        if($email)
                            $email->send($var['email'],$subj,$body);
                    }
                    $msg='Support ticket request denied for '.$var['email']."\n".
                         'Open ticket:'.$openTickets."\n".
                         'Max Allowed:'.$cfg->getMaxOpenTickets()."\n\nNotice only sent once";
                    Sys::alertAdmin('Overlimit Notice',$msg);
                }
            }
        }

        if($errors) { return 0; }

        $deptId=$var['deptId'];
        $priorityId=$var['pri'];
        $source=ucfirst($var['source']);
        $topic=NULL;

        if(isset($var['topicId'])) {

            if($var['topicId'] && ($topic= new Topic($var['topicId'])) && $topic->getId()) {
                $deptId=$deptId?$deptId:$topic->getDeptId();
                $priorityId=$priorityId?$priorityId:$topic->getPriorityId();
                $topicDesc=$topic->getName();
                if($autorespond)
                    $autorespond=$topic->autoRespond();
            }
            $source='Web';
        }elseif($var['emailId'] && !$var['deptId']) {
            $email= new Email($var['emailId']);
            if($email && $email->getId()){
                $deptId=$email->getDeptId();
                $priorityId=$priorityId?$priorityId:$email->getPriorityId();
                if($autorespond)
                    $autorespond=$email->autoRespond();
            }
            $email=null;
            $source='Email';
        }elseif($var['deptId']){
            $deptId=$var['deptId'];
            $source=ucfirst($var['source']);
        }

        if(strpos(strtolower($var['email']),'mailer-daemon@')!==false || strpos(strtolower($var['email']),'postmaster@')!==false)
            $autorespond=false;

        $priorityId=$priorityId?$priorityId:$cfg->getDefaultPriorityId();
        $deptId=$deptId?$deptId:$cfg->getDefaultDeptId();
        $topicId=$var['topicId']?$var['topicId']:0;
        $ipaddress=$var['ip']?$var['ip']:$_SERVER['REMOTE_ADDR'];


        $extId=Ticket::genExtRandID();
        $sql=   'INSERT INTO '.TICKET_TABLE.' SET created=NOW() '.
                ',ticketID='.db_input($extId).
                ',dept_id='.db_input($deptId).
                ',topic_id='.db_input($topicId).
                ',priority_id='.db_input($priorityId).
                ',email='.db_input($var['email']).
                ',name='.db_input(Format::striptags($var['name'])).
                ',subject='.db_input(Format::striptags($var['subject'])).
                ',helptopic='.db_input(Format::striptags($topicDesc)).
                ',phone="'.db_input($var['phone'],false).'"'.
                ',phone_ext='.db_input($var['phone_ext']?$var['phone_ext']:'').
                ',ip_address='.db_input($ipaddress).
                ',source='.db_input($source);

        if($var['duedate'] && !strcasecmp($origin,'staff'))
             $sql.=',duedate='.db_input(date('Y-m-d G:i',Misc::dbtime($var['duedate'].' '.$var['time'])));

        $ticket=null;
        if(db_query($sql) && ($id=db_insert_id())){

            if(!$cfg->useRandomIds()){
                $extId=$id;
                db_query('UPDATE '.TICKET_TABLE.' SET ticketID='.db_input($extId).' WHERE ticket_id='.$id);
            }
            $ticket = new Ticket($id);
            $msgid=$ticket->postMessage($var['message'],$source,$var['mid'],$var['header'],true);
            if($_FILES['attachment']['name'] && $cfg->allowOnlineAttachments() && $msgid) {
                if(!$cfg->allowAttachmentsOnlogin() || ($cfg->allowAttachmentsOnlogin() && ($thisclient && $thisclient->isValid()))) {
                    $ticket->uploadAttachment($_FILES['attachment'],$msgid,'M');
                }
            }

            $dept=$ticket->getDept();

            if(!$dept || !($tplId=$dept->getTemplateId()))
                $tplId=$cfg->getDefaultTemplateId();

            if($autorespond && (Email::getIdByEmail($ticket->getEmail())))
                $autorespond=false;

            if($autorespond && $cfg->autoRespONNewTicket() && $dept->autoRespONNewTicket()){


                $sql='SELECT ticket_autoresp_subj,ticket_autoresp_body FROM '.EMAIL_TEMPLATE_TABLE.
                    ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);
                if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){
                    $body=$ticket->replaceTemplateVars($body);
                    $subj=$ticket->replaceTemplateVars($subj);
                    $body = str_replace('%message',($var['issue']?$var['issue']:$var['message']),$body);
                    $body = str_replace('%signature',($dept && $dept->isPublic())?$dept->getSignature():'',$body);

                    if(!$dept || !($email=$dept->getAutoRespEmail()))
                        $email=$cfg->getDefaultEmail();

                    if($email){
                        if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                            $body ="\n$tag\n\n".$body;
                        $email->send($ticket->getEmail(),$subj,$body);
                    }
                }else {
                    Sys::log(LOG_WARNING,'Template Fetch Error',"Unable to fetch autoresponse template #$tplId");
                }


            }

            if($alertstaff && $cfg->alertONNewTicket() && is_object($ticket)){

                $sql='SELECT ticket_alert_subj,ticket_alert_body FROM '.EMAIL_TEMPLATE_TABLE.
                    ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);
                if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){

                    $body=$ticket->replaceTemplateVars($body);
                    $subj=$ticket->replaceTemplateVars($subj);
                    $body = str_replace('%message',($var['issue']?$var['issue']:$var['message']),$body);
                    if(!($email=$cfg->getAlertEmail()))
                        $email =$cfg->getDefaultEmail();

                    if($email && $email->getId()) {
                        $sentlist=array();
                        if($cfg->alertAdminONNewTicket()){
                            $alert = str_replace("%staff",'Admin',$body);
                            $email->send($cfg->getAdminEmail(),$subj,$alert);
                            $sentlist[]=$cfg->getAdminEmail();
                        }
                        $recipients=array();
                        if($cfg->alertDeptManagerONNewTicket()) {
                            $recipients[]=$dept->getManager();
                        }
                        if($cfg->alertDeptMembersONNewTicket()) {
                            $sql='SELECT staff_id FROM '.STAFF_TABLE.' WHERE onvacation=0 AND dept_id='.db_input($dept->getId());
                            if(($users=db_query($sql)) && db_num_rows($users)) {
                                while(list($id)=db_fetch_row($users))
                                    $recipients[]= new Staff($id);
                            }
                        }
                        foreach( $recipients as $k=>$staff){
                            if(!$staff || !is_object($staff) || !$staff->isAvailable()) continue;
                            if(in_array($staff->getEmail(),$sentlist)) continue;
                            $alert = str_replace("%staff",$staff->getFirstName(),$body);
                            $email->send($staff->getEmail(),$subj,$alert);
                            $sentlist[]=$staff->getEmail();
                        }
                    }
                }else {
                    Sys::log(LOG_WARNING,'Template Fetch Error',"Unable to fetch 'new ticket' alert template #$tplId");
                }
            }
        }
        return $ticket;
    }

    static function create_by_staff($var,&$errors) {
        global $_FILES,$thisuser,$cfg;

        if(!$thisuser || !$thisuser->getId() || !$thisuser->isStaff() || !$thisuser->canCreateTickets())
            $errors['err']='Permission denied';

        if(!$var['issue'])
            $errors['issue']='Summary of the issue required';
        if($var['source'] && !in_array(strtolower($var['source']),array('email','phone','other')))
            $errors['source']='Invalid source - '.Format::htmlchars($var['source']);

        $var['emailId']=0;
        $var['message']='Запрос создан менеджером';

        if(($ticket=Ticket::create($var,$errors,'staff',false,(!$var['staffId'])))){

            $msgId=$ticket->getLastMsgId();
            $issue=$ticket->replaceTemplateVars($var['issue']);
            if(($respId=$ticket->postResponse($msgId,$issue,'none',null,false))) {
                $ticket->markUnAnswered();
                if($cfg->notifyONNewStaffTicket() && isset($var['alertuser'])) {
                    $dept=$ticket->getDept();
                    if(!$dept || !($tplId=$dept->getTemplateId()))
                        $tplId=$cfg->getDefaultTemplateId();

                    $sql='SELECT ticket_notice_subj,ticket_notice_body FROM '.EMAIL_TEMPLATE_TABLE.
                         ' WHERE cfg_id='.db_input($cfg->getId()).' AND tpl_id='.db_input($tplId);

                    if(($resp=db_query($sql)) && db_num_rows($resp) && list($subj,$body)=db_fetch_row($resp)){
                        $body=$ticket->replaceTemplateVars($body);
                        $subj=$ticket->replaceTemplateVars($subj);
                        $body = str_replace('%message',$var['issue'],$body);
                        switch(strtolower($var['signature'])):
                        case 'mine';
                            $signature=$thisuser->getSignature();
                            break;
                        case 'dept':
                            $signature=($dept && $dept->isPublic())?$dept->getSignature():'';
                            break;
                        case 'none';
                        default:
                            $signature='';
                            break;
                        endswitch;
                        $body = str_replace("%signature",$signature,$body);
                        $file=null;
                        $attachment=$_FILES['attachment'];
                        if(($attachment && is_file($attachment['tmp_name'])) && $cfg->emailAttachments()) {
                            $file=array('file'=>$attachment['tmp_name'], 'name'=>$attachment['name'], 'type'=>$attachment['type']);
                        }

                        if($cfg->stripQuotedReply() && ($tag=trim($cfg->getReplySeparator())))
                            $body ="\n$tag\n\n".$body;

                        if(!$dept || !($email=$dept->getEmail()))
                            $email =$cfg->getDefaultEmail();

                        if($email && $email->getId()) {
                            $email->send($ticket->getEmail(),$subj,$body,$file);
                        }
                    }else{

                        $msg='Problems fetching response template for ticket#'.$ticket->getId().' Possible config error - template #'.$tplId;
                        Sys::alertAdmin('System Error',$msg);
                    }

                }


                if($_FILES['attachment'] && $_FILES['attachment']['size']){
                    $ticket->uploadAttachment($_FILES['attachment'],$respId,'R');
                }

            }else{
                $errors['err']='Internal error - message/response post error.';
            }
            if($var['staffId']) {
                $ticket->assignStaff($var['staffId'],$var['note'],(isset($var['alertstaff'])));
            }elseif($var['note']){
                $ticket->postNote('Новая заявка',$var['note'],false);
            }else{
                $ticket->logActivity('New Ticket by Staff','Ticket created by staff -'.$thisuser->getName());
            }

        }else{
            $errors['err']=$errors['err']?$errors['err']:'Невозможно создать запрос. Исправьте ошибки и попробуйте еще раз!';
        }

        return $ticket;

    }

    static function checkOverdue(){

        global $cfg;

        if(($hrs=$cfg->getGracePeriod())) {
            $sec=$hrs*3600;
            $sql='SELECT ticket_id FROM '.TICKET_TABLE.' WHERE status=\'open\' AND isoverdue=0 '.
                 ' AND ((reopened is NULL AND duedate is NULL AND TIME_TO_SEC(TIMEDIFF(NOW(),created))>='.$sec.')  '.
                 ' OR (reopened is NOT NULL AND duedate is NULL AND TIME_TO_SEC(TIMEDIFF(NOW(),reopened))>='.$sec.') '.
                 ' OR (duedate is NOT NULL AND duedate<NOW()) '.
                 ') ORDER BY created LIMIT 50';
        }else{
            $sql='SELECT ticket_id FROM '.TICKET_TABLE.' WHERE status=\'open\' AND isoverdue=0 '.
                 ' AND (duedate is NOT NULL AND duedate<NOW()) ORDER BY created LIMIT 100';
        }
        if(($stale=db_query($sql)) && db_num_rows($stale)){
            while(list($id)=db_fetch_row($stale)){
                $ticket = new Ticket($id);
                if($ticket->markOverdue(true))
                    $ticket->logActivity('Ticket Marked Overdue','Ticket flagged as overdue by the system.');
            }
        }
   }

   static function GetRusStatus($engstatus){
		if (strtolower($engstatus)!='reopen' && strtolower($engstatus)!='open' && strtolower($engstatus)!='closed') return $engstatus;
		switch(strtolower($engstatus)):
        case 'reopen':
			return 'В работе';
            break;
        case 'open':
            return 'В работе';
            break;
        case 'closed':
            return 'Завершено';
            break;
         break;
        endswitch;
		return $engstatus;
   }

}
?>
