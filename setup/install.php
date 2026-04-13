<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_trans_sid', 0);
ini_set('session.cache_limiter', 'nocache');
ini_set('display_errors',0);
ini_set('display_startup_errors',0);

session_start();
require('setup.inc.php');

$errors=array();
$_SESSION['abort']=false;
define('VERSION','1.0');
define('VERSION_VERBOSE','1.0');
define('CONFIGFILE','../include/th-config.php');
define('SCHEMAFILE','./inc/tickethub-v1.0.sql');
define('ENVFILE','../.env');
define('URL',rtrim('http'.((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on')?'s':'').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']),'setup'));

$install='<strong>Нужна помощь?</strong> <a href="https://github.com/LaverCasey/TicketHub" target="_blank">TicketHub Support</a>';
$support='<strong>Техническая поддержка на</strong> <a href="https://github.com/LaverCasey/TicketHub" target="_blank">TicketHub Support</a>';

$envFile = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'.env';
if(file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($envLines as $envLine) {
        $envLine = trim($envLine);
        if($envLine === '' || $envLine[0] === '#') continue;
        if(strpos($envLine, '=') === false) continue;
        list($k, $v) = explode('=', $envLine, 2);
        $k = trim($k);
        $v = trim($v);
        if(!getenv($k)) putenv("$k=$v");
    }
}

$envDbReady = (getenv('DB_HOST') && getenv('DB_PASS'));

$inc='install.inc.php';
$info=$install;

if(file_exists('../include/settings.php')) {
    header('Location: upgrade.php');
    die('Обнаружена старая установка... попробуйте обновление');
}elseif(version_compare(PHP_VERSION, '8.0.0', '<')){
    $errors['err']='Требуется PHP 8.0 или новее. Текущая версия: '.PHP_VERSION;
    $inc='php.inc.php';
}elseif(!file_exists(CONFIGFILE)) {
    $errors['err']=sprintf('Файл конфигурации (%s) не найден!',basename(CONFIGFILE));
    $inc='missing.inc.php';
}elseif(($cFile=file_get_contents(CONFIGFILE)) && preg_match("/define\('THINSTALLED',TRUE\)/i",$cFile)){
    $errors['err']='Система уже установлена!';
    $inc='unclean.inc.php';
}elseif($_POST){
    $f=array();
    $f['title']     = array('type'=>'string', 'required'=>1, 'error'=>'Требуется название');
    $f['sysemail']  = array('type'=>'email',  'required'=>1, 'error'=>'Требуется корректный email');
    $f['username']  = array('type'=>'username', 'required'=>1, 'error'=>'Требуется имя пользователя');
    $f['password']  = array('type'=>'password', 'required'=>1, 'error'=>'Требуется пароль');
    $f['password2'] = array('type'=>'password', 'required'=>1, 'error'=>'Подтвердите пароль');
    $f['email']     = array('type'=>'email',  'required'=>1, 'error'=>'Требуется корректный email');

    if(!$envDbReady) {
        $f['dbhost']  = array('type'=>'string', 'required'=>1, 'error'=>'Требуется адрес сервера');
        $f['dbname']  = array('type'=>'string', 'required'=>1, 'error'=>'Требуется имя базы данных');
        $f['dbuser']  = array('type'=>'string', 'required'=>1, 'error'=>'Требуется имя пользователя');
        $f['dbpass']  = array('type'=>'string', 'required'=>1, 'error'=>'Требуется пароль');
    }

    $validate = new Validator($f);
    if(!$validate->validate($_POST)){
        $errors=array_merge($errors,$validate->errors());
    }
    if($_POST['sysemail'] && $_POST['email'] && !strcasecmp($_POST['sysemail'],$_POST['email']))
        $errors['email']='Совпадает с системным email выше';
    if(!$errors && strcasecmp($_POST['password'],$_POST['password2']))
        $errors['password2']='Пароли не совпадают!';

    if(empty($errors['username']) && in_array(strtolower($_POST['username']),array('admin','admins','username','tickethub')))
        $errors['username']='Недопустимое имя пользователя';

    $prefix = 'th_';
    if($envDbReady) {
        $dbhost  = getenv('DB_HOST');
        $dbname  = getenv('DB_NAME') ?: 'tickethub';
        $dbuser  = getenv('DB_USER') ?: 'tickethub';
        $dbpass  = getenv('DB_PASS');
    } else {
        $dbhost  = $_POST['dbhost'] ?? '';
        $dbname  = $_POST['dbname'] ?? '';
        $dbuser  = $_POST['dbuser'] ?? '';
        $dbpass  = $_POST['dbpass'] ?? '';
    }

    if(!$errors && !db_connect($dbhost, $dbuser, $dbpass))
        $errors['mysql']='Не удалось подключиться к серверу MySQL.';

    if(!$errors && version_compare(db_version(), '5.7.0', '<'))
        $errors['mysql']='TicketHub требует MySQL 5.7 или новее!';

    if(!$errors && !preg_match('/^[a-zA-Z0-9_]+$/', $dbname))
        $errors['dbname']='Недопустимое имя базы данных.';

    if(!$errors && !db_select_database($dbname)) {
        global $__db;
        $safe_dbname = mysqli_real_escape_string($__db, $dbname);
        if(!mysqli_query($__db, 'CREATE DATABASE `'.$safe_dbname.'` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')) {
            $errors['dbname']='База данных не существует и не удалось создать';
        }elseif(!db_select_database($dbname)) {
            $errors['dbname']='Не удалось выбрать базу данных';
        }
    }

    if(!$errors && !file_exists(SCHEMAFILE)) {
        $errors['err']='Отсутствует файл SQL-схемы';
    }

    if(!$errors) {
        define('ADMIN_EMAIL',$_POST['email']);
        define('PREFIX',$prefix);

        $debug=false;
        if(!load_sql_schema(SCHEMAFILE,$errors,$debug) && !$errors['err'])
            $errors['err']='Ошибка при обработке SQL-схемы!';

        if(!$errors) {
            $info=$support;

            $secretSalt = Misc::randcode(32);

            if(!$envDbReady) {
                $envContent = "# TicketHub Environment Configuration\n";
                $envContent .= "# Generated by installer on ".date('Y-m-d H:i:s')."\n\n";
                $envContent .= "# Database\n";
                $envContent .= "DB_HOST=$dbhost\n";
                $envContent .= "DB_NAME=$dbname\n";
                $envContent .= "DB_USER=$dbuser\n";
                $envContent .= "DB_PASS=$dbpass\n\n";
                $envContent .= "# Security\n";
                $envContent .= "SECRET_SALT=$secretSalt\n\n";
                $envContent .= "# Admin\n";
                $envContent .= "ADMIN_EMAIL={$_POST['email']}\n";

                if(!@file_put_contents(ENVFILE, $envContent)) {
                    $errors['err']='Не удалось записать .env файл! Проверьте права на директорию.';
                }
            } else {
                $currentEnv = file_exists(ENVFILE) ? file_get_contents(ENVFILE) : '';
                if(strpos($currentEnv, 'change_me') !== false || !getenv('SECRET_SALT') || getenv('SECRET_SALT') === '') {
                    if(preg_match('/^SECRET_SALT=.*$/m', $currentEnv)) {
                        $currentEnv = preg_replace('/^SECRET_SALT=.*$/m', "SECRET_SALT=$secretSalt", $currentEnv);
                    } else {
                        $currentEnv .= "\nSECRET_SALT=$secretSalt\n";
                    }
                    if(preg_match('/^ADMIN_EMAIL=.*$/m', $currentEnv)) {
                        $currentEnv = preg_replace('/^ADMIN_EMAIL=.*$/m', "ADMIN_EMAIL={$_POST['email']}", $currentEnv);
                    } else {
                        $currentEnv .= "ADMIN_EMAIL={$_POST['email']}\n";
                    }
                    @file_put_contents(ENVFILE, $currentEnv);
                }
            }

            if(!$errors) {
                $configContent = file_get_contents(CONFIGFILE);
                $configContent = str_replace("define('THINSTALLED',FALSE);", "define('THINSTALLED',TRUE);", $configContent);
                if(!@file_put_contents(CONFIGFILE, $configContent)) {
                    $errors['err']='Не удалось записать файл конфигурации!';
                }
            }

            if(!$errors) {
                putenv("SECRET_SALT=$secretSalt");
                putenv("ADMIN_EMAIL={$_POST['email']}");

                $tzoffset = date("Z")/3600;
                $sql='INSERT INTO '.$prefix.'staff SET created=NOW(), isadmin=1,change_passwd=0,group_id=1,dept_id=1 '.
                    ',email='.db_input($_POST['email']).',firstname='.db_input('Admin').',lastname='.db_input('Admin').
                    ',username='.db_input($_POST['username']).',passwd='.db_input(password_hash($_POST['password'], PASSWORD_DEFAULT)).
                    ',timezone_offset='.db_input($tzoffset);
                db_query($sql);

                list($uname,$domain)=explode('@',$_POST['sysemail']);
                $sql='INSERT INTO '.$prefix.'email SET created=NOW(),updated=NOW(),priority_id=2,dept_id=1'.
                     ',name='.db_input('Support').',email='.db_input($_POST['sysemail']);
                db_query($sql);
                $sql='INSERT INTO '.$prefix.'email SET created=NOW(),updated=NOW(),priority_id=1,dept_id=1'.
                     ',name='.db_input('TicketHub Alerts').',email='.db_input('alerts@'.$domain);
                db_query($sql);
                $sql='INSERT INTO '.$prefix.'email SET created=NOW(),updated=NOW(),priority_id=1,dept_id=1'.
                     ',name='.db_input('').',email='.db_input('noreply@'.$domain);
                db_query($sql);

                $sql='INSERT INTO '.$prefix.'config SET updated=NOW() '.
                     ',isonline=0,default_email_id=1,alert_email_id=2,default_dept_id=1,default_template_id=1'.
                     ',timezone_offset='.db_input($tzoffset).
                     ',thversion='.db_real_escape(VERSION, true).
                     ',admin_email='.db_input($_POST['email']).
                     ',helpdesk_url='.db_input(URL).
                     ',helpdesk_title='.db_input($_POST['title']);
                db_query($sql);

                $sql='INSERT INTO '.$prefix.'ticket SET created=NOW(),ticketID='.db_input(Misc::randNumber(6)).
                    ",priority_id=2,dept_id=1,email='support@tickethub.com',name='Команда TicketHub' ".
                    ",subject='Добро пожаловать в TicketHub!',helptopic='Техническая проблема',status='open',source='Web'";
                if(db_query($sql) && ($id=db_insert_id())){
                    db_query('INSERT INTO '.$prefix."ticket_message VALUES (1,$id,NULL,".db_input(TICKETHUB_INSTALLED).",NULL,'Web','',NOW(),NULL)");
                }

                $sql='INSERT INTO '.$prefix.'syslog SET created=NOW(),updated=NOW() '.
                     ',title="TicketHub установлен!",log_type="Debug" '.
                     ',log='.db_input("Установка TicketHub успешно завершена!\n\nСпасибо за выбор TicketHub!").
                     ',ip_address='.db_input($_SERVER['REMOTE_ADDR']);
                db_query($sql);

                // Загрузка тестовых данных если чекбокс отмечен
                if(!empty($_POST['load_seed_data'])) {
                    $seedFile = __DIR__ . '/inc/seed-data.sql';
                    if(file_exists($seedFile)) {
                        $seedSql = file_get_contents($seedFile);
                        $seedSql = str_replace('%TABLE_PREFIX%', $prefix, $seedSql);
                        $seedPassword = password_hash('TestPassword1!', PASSWORD_DEFAULT);
                        $seedSql = str_replace('%STAFF_PASSWD%', addslashes($seedPassword), $seedSql);

                        global $__db;
                        @mysqli_query($__db, 'SET SESSION SQL_MODE=""');
                        @mysqli_query($__db, 'SET FOREIGN_KEY_CHECKS=0');

                        $seedQueries = array_filter(array_map('trim', explode(';', $seedSql)));
                        foreach($seedQueries as $sq) {
                            if($sq === '') continue;
                            @mysqli_query($__db, $sq);
                        }

                        @mysqli_query($__db, 'SET FOREIGN_KEY_CHECKS=1');
                    }
                }

                $msg='Поздравляем! Установка TicketHub завершена!';
                $inc='done.inc.php';
            }
        }
    }

    if($errors && $inc=='install.inc.php') {
        $errors['err']=!empty($errors['err'])?$errors['err']:'Произошли ошибки. Исправьте и попробуйте снова';
    }
}

$title=sprintf('TicketHub версия %s - Установка',VERSION_VERBOSE);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Установка TicketHub</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="setup-wrapper">
    <div class="setup-header">
        <div class="logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="fill:none;stroke:#fff"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><path d="M5 5h14a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a2 2 0 000-4V7a2 2 0 012-2z"/></svg>
        </div>
        <h1>Установка TicketHub</h1>
        <div class="version">Версия <?=VERSION_VERBOSE?></div>
    </div>

    <div class="setup-card">
        <?php if(!empty($errors['err'])) {?>
            <div class="msg-box msg-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                <span><?=$errors['err']?></span>
            </div>
        <?php }elseif(!empty($msg)) {?>
            <div class="msg-box msg-success">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
                <span><?=$msg?></span>
            </div>
        <?php }elseif(!empty($warn)) {?>
            <div class="msg-box msg-warn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                <span><?=$warn?></span>
            </div>
        <?php }?>

        <?php
            if(file_exists("./inc/$inc"))
                require("./inc/$inc");
            else
                echo '<div class="info-page"><p class="error">Неверный путь — обратитесь в техническую поддержку</p></div>';
        ?>
    </div>

    <div class="setup-footer">
        &copy; <?=date('Y')?> TicketHub &middot; <a href="https://github.com/LaverCasey/TicketHub" target="_blank">GitHub</a>
    </div>
</div>
</body>
</html>
