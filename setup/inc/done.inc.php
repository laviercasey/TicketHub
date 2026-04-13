<div class="form-section" style="padding:32px 24px;">
    <div class="done-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
    </div>
    <div class="done-title">Установка завершена!</div>
    <div class="done-subtitle">TicketHub готов к работе. Осталось несколько шагов.</div>

    <ul class="checklist">
        <li>
            <div class="check-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="check-text">
                <strong>Войдите в Панель Управления</strong>
                Используйте логин и пароль, которые вы указали при установке. По умолчанию система отключена — включите её в разделе <em>Настройки</em>.
            </div>
        </li>
        <li>
            <div class="check-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="check-text">
                <strong>Настройте отделы и сотрудников</strong>
                Создайте отделы, добавьте сотрудников и настройте темы обращений для приёма заявок.
            </div>
        </li>
        <li>
            <div class="check-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="check-text">
                <strong>Настройте email-уведомления</strong>
                Укажите шаблоны писем для автоматических уведомлений клиентов о статусе их заявок.
            </div>
        </li>
        <?php if(!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {?>
        <li>
            <div class="check-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="check-text">
                <strong>Установите OpenSSL (рекомендуется)</strong>
                Модуль шифрования не включён. IMAP/POP пароли будут храниться в открытом виде.
            </div>
        </li>
        <?php } ?>
    </ul>

    <div class="done-actions">
        <a href="../scp/admin.php" class="btn btn-primary" target="_blank">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Панель Управления
        </a>
        <a href="https://github.com/LaverCasey/TicketHub" class="btn btn-secondary" target="_blank">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22"/></svg>
            GitHub
        </a>
    </div>
</div>
