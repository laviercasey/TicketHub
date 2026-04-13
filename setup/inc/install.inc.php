<?php
if(!defined('SETUPINC')) die('Kwaheri wafiki!');

$info=($errors && $_POST)?array_map(function($v){ return htmlspecialchars($v, ENT_QUOTES); }, $_POST):array();

if(!isset($info['title'])) {
    $info['title']='TicketHub - Система Технической Поддержки';
}
if(!isset($info['dbhost'])) {
    $info['dbhost']='localhost';
}
?>
<form action="install.php" method="post" autocomplete="off">

    <!-- Система -->
    <div class="form-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            Настройки системы
        </div>

        <div class="field">
            <label>URL сайта</label>
            <div class="field-static"><?=URL?></div>
        </div>
        <div class="field">
            <label for="f-title">Название системы</label>
            <input type="text" id="f-title" name="title" value="<?=$info['title']?>" placeholder="Моя система поддержки" class="<?=!empty($errors['title'])?'has-error':''?>">
            <?php if(!empty($errors['title'])):?><div class="error-text"><?=$errors['title']?></div><?php endif;?>
        </div>
        <div class="field">
            <label for="f-sysemail">Системный email</label>
            <input type="email" id="f-sysemail" name="sysemail" value="<?=$info['sysemail'] ?? ''?>" placeholder="support@company.ru" class="<?=!empty($errors['sysemail'])?'has-error':''?>">
            <?php if(!empty($errors['sysemail'])):?><div class="error-text"><?=$errors['sysemail']?></div><?php endif;?>
            <div class="hint">Используется для отправки уведомлений. Можно изменить позже.</div>
        </div>
    </div>

    <!-- Администратор -->
    <div class="form-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Администратор
        </div>

        <div class="field-row">
            <div class="field">
                <label for="f-username">Логин</label>
                <input type="text" id="f-username" name="username" value="<?=$info['username'] ?? ''?>" placeholder="mylogin" autocomplete="new-password" class="<?=!empty($errors['username'])?'has-error':''?>">
                <?php if(!empty($errors['username'])):?><div class="error-text"><?=$errors['username']?></div><?php endif;?>
            </div>
            <div class="field">
                <label for="f-email">Email администратора</label>
                <input type="email" id="f-email" name="email" value="<?=$info['email'] ?? ''?>" placeholder="admin@company.ru" class="<?=!empty($errors['email'])?'has-error':''?>">
                <?php if(!empty($errors['email'])):?><div class="error-text"><?=$errors['email']?></div><?php endif;?>
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="f-password">Пароль</label>
                <input type="password" id="f-password" name="password" value="<?=$info['password'] ?? ''?>" placeholder="Минимум 6 символов" autocomplete="new-password" class="<?=!empty($errors['password'])?'has-error':''?>">
                <?php if(!empty($errors['password'])):?><div class="error-text"><?=$errors['password']?></div><?php endif;?>
            </div>
            <div class="field">
                <label for="f-password2">Подтверждение пароля</label>
                <input type="password" id="f-password2" name="password2" value="<?=$info['password2'] ?? ''?>" placeholder="Повторите пароль" autocomplete="new-password" class="<?=!empty($errors['password2'])?'has-error':''?>">
                <?php if(!empty($errors['password2'])):?><div class="error-text"><?=$errors['password2']?></div><?php endif;?>
            </div>
        </div>
    </div>

    <!-- База данных -->
    <div class="form-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            База данных
        </div>

        <?php if($envDbReady) { ?>
        <div class="db-env-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            Подключение настроено через окружение
        </div>
        <div class="db-env-details">
            Сервер: <span><?=htmlspecialchars(getenv('DB_HOST'))?></span> &middot;
            База: <span><?=htmlspecialchars(getenv('DB_NAME') ?: 'tickethub')?></span>
        </div>
        <?php } else { ?>
        <?php if(!empty($errors['mysql'])):?><div class="msg-box msg-error" style="margin:0 0 14px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg><span><?=$errors['mysql']?></span></div><?php endif;?>
        <div class="field-row">
            <div class="field">
                <label for="f-dbhost">Сервер MySQL</label>
                <input type="text" id="f-dbhost" name="dbhost" value="<?=$info['dbhost']?>" class="<?=!empty($errors['dbhost'])?'has-error':''?>">
                <?php if(!empty($errors['dbhost'])):?><div class="error-text"><?=$errors['dbhost']?></div><?php endif;?>
            </div>
            <div class="field">
                <label for="f-dbname">Имя базы данных</label>
                <input type="text" id="f-dbname" name="dbname" value="<?=$info['dbname'] ?? ''?>" placeholder="tickethub" class="<?=!empty($errors['dbname'])?'has-error':''?>">
                <?php if(!empty($errors['dbname'])):?><div class="error-text"><?=$errors['dbname']?></div><?php endif;?>
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="f-dbuser">Пользователь MySQL</label>
                <input type="text" id="f-dbuser" name="dbuser" value="<?=$info['dbuser'] ?? ''?>" class="<?=!empty($errors['dbuser'])?'has-error':''?>">
                <?php if(!empty($errors['dbuser'])):?><div class="error-text"><?=$errors['dbuser']?></div><?php endif;?>
            </div>
            <div class="field">
                <label for="f-dbpass">Пароль MySQL</label>
                <input type="password" id="f-dbpass" name="dbpass" value="<?=$info['dbpass'] ?? ''?>" class="<?=!empty($errors['dbpass'])?'has-error':''?>">
                <?php if(!empty($errors['dbpass'])):?><div class="error-text"><?=$errors['dbpass']?></div><?php endif;?>
            </div>
        </div>
        <div class="hint" style="margin-top:4px">MySQL 5.7+ или MariaDB 10.3+. Данные подключения сохранятся в <code>.env</code></div>
        <?php } ?>
    </div>

    <!-- Seed данные -->
    <div class="form-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            Дополнительно
        </div>

        <label class="checkbox-field">
            <input type="checkbox" name="load_seed_data" value="1" <?=!empty($info['load_seed_data'])?'checked':''?>>
            <div class="cb-content">
                <div class="cb-title">Загрузить тестовые данные</div>
                <div class="cb-desc">
                    Добавит сотрудников, заявки, задачи, базу знаний и инвентарь для демонстрации.
                    Рекомендуется для ознакомления с системой. Пароль тестовых аккаунтов: <code>TestPassword1!</code>
                </div>
            </div>
        </label>
    </div>

    <!-- Actions -->
    <div class="form-actions">
        <button type="reset" class="btn btn-secondary">Очистить</button>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Установить
        </button>
    </div>
</form>
