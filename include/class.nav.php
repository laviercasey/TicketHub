<?php
class StaffNav {
    public $tabs=array();
    public $submenu=array();

    public $activetab;
    public $ptype;

    public function __construct($pagetype='staff'){
        global $thisuser;

        $this->ptype=$pagetype;
        $tabs=array();
        if($thisuser->isAdmin() && strcasecmp($pagetype,'admin')==0) {
            $tabs['dashboard']=array('desc'=>'Главная','href'=>'admin.php?t=dashboard','title'=>'Главная');
            $tabs['settings']=array('desc'=>'Настройки','href'=>'admin.php?t=settings','title'=>'Системные Настройки');
            $tabs['emails']=array('desc'=>'Emails','href'=>'admin.php?t=email','title'=>'Настройки Email');
            $tabs['topics']=array('desc'=>'Тема Обращения','href'=>'admin.php?t=topics','title'=>'Тема Обращения');
            $tabs['staff']=array('desc'=>'Пользователи','href'=>'admin.php?t=staff','title'=>'Пользователи');
            $tabs['depts']=array('desc'=>'Отделы','href'=>'admin.php?t=depts','title'=>'Отделы');
        }else {
            $tabs['tickets']=array('desc'=>'Заявки','href'=>'tickets.php?status=open','title'=>'Заявки');
            if($thisuser && $thisuser->canManageKb()){
             $tabs['kbase']=array('desc'=>'База Знаний','href'=>'kb.php','title'=>'База Знаний');
            }
            $tabs['tasks']=array('desc'=>'Задачи','href'=>'tasks.php','title'=>'Менеджер Задач');
            $tabs['inventory']=array('desc'=>'Инвентаризация','href'=>'inventory.php','title'=>'Учёт техники');
            $tabs['directory']=array('desc'=>'Менеджеры','href'=>'directory.php','title'=>'Менеджеры');
            $tabs['profile']=array('desc'=>'Мой Аккаунт','href'=>'profile.php','title'=>'Мой Профиль');
        }
        $this->tabs=$tabs;    
    }
    
    
    function setTabActive($tab){
            
        if($this->tabs[$tab]){
            $this->tabs[$tab]['active']=true;
            if($this->activetab && $this->activetab!=$tab && $this->tabs[$this->activetab])
                 $this->tabs[$this->activetab]['active']=false;
            $this->activetab=$tab;
            return true;
        }
        return false;
    }
    
    function addSubMenu($item,$tab=null) {
        
        $tab=$tab?$tab:$this->activetab;
        $this->submenu[$tab][]=$item;
    }

    
    
    function getActiveTab(){
        return $this->activetab;
    }        

    function getTabs(){
        return $this->tabs;
    }

    function getSubMenu($tab=null){
      
        $tab=$tab?$tab:$this->activetab;  
        return $this->submenu[$tab];
    }
    
}
?>
