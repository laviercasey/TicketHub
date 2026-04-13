<?php
define('SETUPINC',true);

if(!defined('INCLUDE_DIR')):
define('ROOT_PATH','../');
define('ROOT_DIR','../');
define('INCLUDE_DIR',ROOT_DIR.'include/');
endif;

require_once(INCLUDE_DIR.'mysql.php');
require_once(INCLUDE_DIR.'class.validator.php');
require_once(INCLUDE_DIR.'class.format.php');
require_once(INCLUDE_DIR.'class.misc.php');

function replace_table_prefix($query) {
    return str_replace('%TABLE_PREFIX%',PREFIX, $query);
}


function load_sql_schema($schema,&$errors,$debug=false){

    if(!file_exists($schema) || !($schema=file_get_contents($schema))) {
        $errors['err']='Внутренняя ошибка. Убедитесь что файл SQL-схемы существует';
        return false;
    }

    $queries = array_map('replace_table_prefix',
        array_filter(array_map('trim', explode(';', $schema))));

    if(!$queries || !count($queries)) {
        $errors['err']='Ошибка парсинга SQL-схемы!';
        return false;
    }

    global $__db;
    @mysqli_query($__db, 'SET SESSION SQL_MODE =""');

    $total = count($queries);
    $executed = 0;

    foreach($queries as $k => $sql) {
        if(empty(trim($sql))) continue;

        if(!mysqli_query($__db, $sql)) {
            $mysqlErr = mysqli_error($__db);
            $queryNum = $executed + 1;
            $shortSql = mb_substr(trim($sql), 0, 120);

            $errors['err'] = sprintf(
                'Ошибка SQL-схемы на запросе %d/%d',
                $queryNum, $total
            );
            $errors['sql'] = "[$shortSql...] — $mysqlErr";

            if($debug) {
                $errors['sql_full'] = $sql;
            }
            return false;
        }
        $executed++;
    }

    return true;
}


ob_start();
echo "
Добро пожаловать в TicketHub!

Это ваша первая заявка — она создана автоматически при установке системы и служит примером того, как работает процесс обработки обращений.

Что умеет TicketHub:
- Приём и учёт заявок от пользователей через веб-форму
- Назначение заявок на отделы и конкретных сотрудников
- Приоритизация, статусы и отслеживание сроков
- Внутренние заметки и переписка с клиентом
- Шаблоны ответов (база знаний) с подстановкой переменных
- Управление сотрудниками, отделами и группами доступа
- REST API для интеграции с внешними системами

Рекомендуем:
1. Перейдите в Панель Управления (scp/) и изучите настройки
2. Создайте отделы и добавьте сотрудников
3. Настройте темы обращений и шаблоны email-уведомлений
4. Включите систему в разделе Настройки → Система онлайн

Документация и исходный код: https://github.com/LaverCasey/TicketHub

— Команда TicketHub";
$msg1 = ob_get_contents();
ob_end_clean();
define('TICKETHUB_INSTALLED',trim($msg1));

ob_start();
echo "
TicketHub обновлён!

Система успешно обновлена до последней версии. Все ваши данные сохранены.

Следите за обновлениями и участвуйте в развитии проекта: https://github.com/LaverCasey/TicketHub

— Команда TicketHub";
$msg2 = ob_get_contents();
ob_end_clean();
define('TICKETHUB_UPGRADED',trim($msg2));

$msg='';
$errors=array();
?>
