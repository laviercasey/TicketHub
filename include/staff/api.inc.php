<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

require_once(INCLUDE_DIR.'class.apitoken.php');

if (isset($_SESSION['api_msg'])) {
    $msg = $_SESSION['api_msg'];
    unset($_SESSION['api_msg']);
}
if (isset($_SESSION['api_err'])) {
    $errors['err'] = $_SESSION['api_err'];
    unset($_SESSION['api_err']);
}

$sql = "SELECT t.*, s.firstname, s.lastname,
            (SELECT COUNT(*) FROM " . API_LOG_TABLE . " l WHERE l.token_id = t.token_id AND l.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as requests_24h
        FROM " . API_TOKEN_TABLE . " t
        LEFT JOIN " . STAFF_TABLE . " s ON t.staff_id = s.staff_id
        ORDER BY t.created_at DESC";
$result = db_query($sql);
$total = $result ? db_num_rows($result) : 0;

$all_permissions = array(
    'tickets:read' => 'Заявки: Чтение',
    'tickets:write' => 'Заявки: Создание/Обновление',
    'tickets:delete' => 'Заявки: Удаление/Закрытие',
    'users:read' => 'Пользователи: Чтение',
    'users:write' => 'Пользователи: Создание/Обновление',
    'staff:read' => 'Сотрудники: Чтение',
    'staff:write' => 'Сотрудники: Управление',
    'departments:read' => 'Отделы: Чтение',
    'tasks:read' => 'Задачи: Чтение',
    'tasks:write' => 'Задачи: Создание/Обновление',
    'kb:read' => 'База знаний: Чтение',
    'kb:write' => 'База знаний: Управление',
    'admin:*' => 'Полный доступ администратора'
);

$show_new_token = false;
$new_token_display = '';
if (isset($_SESSION['new_token_value'])) {
    $show_new_token = true;
    $new_token_display = $_SESSION['new_token_value'];
    unset($_SESSION['new_token_value']);
    unset($_SESSION['new_token_id']);
}
?>

<h2 class="text-lg font-heading font-semibold text-gray-900 mb-4">API Токены (v1)</h2>

<?php if ($msg): ?>
    <div class="alert-success mb-4"><i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i><span><?php echo Format::htmlchars($msg); ?></span></div>
<?php endif; ?>

<?php if (!empty($errors['err'])): ?>
    <div class="alert-danger mb-4"><i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i><span><?php echo Format::htmlchars($errors['err']); ?></span></div>
<?php endif; ?>

<?php if ($show_new_token): ?>
    <div class="alert-warning mb-4 !flex-col !items-start">
        <div class="flex items-center gap-2 font-semibold"><i data-lucide="key" class="w-4 h-4"></i> Новый API токен создан!</div>
        <p class="text-sm mt-1">Скопируйте этот токен сейчас. В целях безопасности он не будет показан повторно.</p>
        <code id="new-token" class="block bg-gray-900 text-emerald-400 px-4 py-3 rounded-lg text-sm font-mono mt-2 w-full break-all select-all"><?php echo Format::htmlchars($new_token_display); ?></code>
        <button class="btn-primary btn-sm mt-2" onclick="copyToken('new-token',this)"><i data-lucide="clipboard" class="w-4 h-4"></i> Скопировать</button>
        <p class="text-xs text-amber-700 mt-2">
            <strong>Использование:</strong> Добавьте заголовок <code class="bg-gray-100 text-gray-800 px-1 py-0.5 rounded text-xs">Authorization: Bearer <?php echo Format::htmlchars(substr($new_token_display, 0, 8)); ?>...</code> к вашим API запросам.
        </p>
    </div>
<?php endif; ?>

<!-- Token List -->
<div class="card mb-6">
    <div class="card-header flex items-center justify-between">
        <h3 class="font-heading font-semibold">Всего токенов: <?php echo $total; ?></h3>
    </div>
<form action="admin.php?t=api" method="POST" name="tokens" onSubmit="return checkbox_checker(document.forms['tokens'],1,0);">
<?php echo Misc::csrfField(); ?>
<input type="hidden" name="t" value="api">
<input type="hidden" name="do" value="mass_process">
<div class="table-wrapper">
<table class="table-modern">
    <thead>
        <tr>
            <th class="table-th w-8">&nbsp;</th>
            <th class="table-th">Название</th>
            <th class="table-th">Тип</th>
            <th class="table-th">Разрешения</th>
            <th class="table-th">Лимит запросов</th>
            <th class="table-th">Запросов за 24ч</th>
            <th class="table-th">Последнее использование</th>
            <th class="table-th">Статус</th>
            <th class="table-th">Создан</th>
            <th class="table-th">Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if ($result && db_num_rows($result)):
        while ($row = db_fetch_array($result)):
            $perms = $row['permissions'] ? json_decode($row['permissions'], true) : array();
            if (!is_array($perms)) $perms = array();
            $is_expired = $row['expires_at'] && strtotime($row['expires_at']) < time();
    ?>
        <tr>
            <td class="table-td"><input type="checkbox" class="checkbox" name="ids[]" value="<?php echo (int)$row['token_id']; ?>"></td>
            <td class="table-td">
                <strong class="text-gray-900"><?php echo Format::htmlchars($row['name']); ?></strong>
                <?php if ($row['description']): ?>
                    <br><small class="text-gray-400"><?php echo Format::htmlchars($row['description']); ?></small>
                <?php endif; ?>
                <?php if ($row['firstname']): ?>
                    <br><small class="text-gray-400">от <?php echo Format::htmlchars($row['firstname'] . ' ' . $row['lastname']); ?></small>
                <?php endif; ?>
            </td>
            <td class="table-td"><span class="badge-info"><?php echo Format::htmlchars($row['token_type']); ?></span></td>
            <td class="table-td">
                <?php
                if (in_array('admin:*', $perms)) {
                    echo '<span class="inline-block bg-amber-100 text-amber-800 text-xs px-1.5 py-0.5 rounded font-semibold">admin:*</span>';
                } else {
                    foreach (array_slice($perms, 0, 4) as $p) {
                        echo '<span class="inline-block bg-gray-100 text-gray-600 text-xs px-1.5 py-0.5 rounded mr-0.5 mb-0.5">' . Format::htmlchars($p) . '</span>';
                    }
                    if (count($perms) > 4) {
                        echo '<span class="inline-block bg-gray-100 text-gray-600 text-xs px-1.5 py-0.5 rounded">+' . (count($perms) - 4) . ' ещё</span>';
                    }
                }
                ?>
            </td>
            <td class="table-td"><?php echo number_format($row['rate_limit']); ?>/h</td>
            <td class="table-td"><?php echo number_format($row['requests_24h']); ?></td>
            <td class="table-td">
                <?php echo $row['last_used_at'] ? Format::htmlchars($row['last_used_at']) : '<em class="text-gray-400">Никогда</em>'; ?>
            </td>
            <td class="table-td">
                <?php if ($is_expired): ?>
                    <span class="badge-danger">Истёк</span>
                <?php elseif ($row['is_active']): ?>
                    <span class="badge-success">Активен</span>
                <?php else: ?>
                    <span class="badge-danger">Неактивен</span>
                <?php endif; ?>
            </td>
            <td class="table-td"><?php echo Format::htmlchars($row['created_at']); ?></td>
            <td class="table-td">
                <div class="flex items-center gap-1">
                <?php if ($row['is_active']): ?>
                    <button type="button" class="btn-warning btn-sm" onclick="apiTokenAction('toggle_token',<?php echo (int)$row['token_id']; ?>,0,'Деактивировать этот токен?')"><i data-lucide="x-circle" class="w-3 h-3"></i></button>
                <?php else: ?>
                    <button type="button" class="btn-success btn-sm" onclick="apiTokenAction('toggle_token',<?php echo (int)$row['token_id']; ?>,1,'Активировать этот токен?')"><i data-lucide="check-circle" class="w-3 h-3"></i></button>
                <?php endif; ?>
                <button type="button" class="btn-danger btn-sm" onclick="apiTokenAction('delete_token',<?php echo (int)$row['token_id']; ?>,0,'Удалить этот токен навсегда?')"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                </div>
            </td>
        </tr>
    <?php
        endwhile;
    else:
    ?>
        <tr><td colspan="10" class="table-td">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="key" class="w-8 h-8"></i></div>
                <h3 class="empty-state-title">API токены ещё не созданы</h3>
                <p class="empty-state-text">Создайте токен ниже, чтобы начать.</p>
            </div>
        </td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($total > 0): ?>
<div class="card-footer">
    <div class="flex items-center justify-center gap-3">
        <button class="btn-success btn-sm" type="submit" name="enable" value="Enable Selected" onclick="return confirm('Включить выбранные токены?')">
            <i data-lucide="check-circle" class="w-4 h-4"></i> Включить выбранные
        </button>
        <button class="btn-warning btn-sm" type="submit" name="disable" value="Disable Selected" onclick="return confirm('Отключить выбранные токены?')">
            <i data-lucide="x-circle" class="w-4 h-4"></i> Отключить выбранные
        </button>
        <button class="btn-danger btn-sm" type="submit" name="delete" value="Delete Selected" onclick="return confirm('Удалить выбранные токены навсегда?')">
            <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить выбранные
        </button>
    </div>
</div>
<?php endif; ?>
</form>
</div>

<!-- Create New Token -->
<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Создать новый API токен</h3></div>
    <div class="card-body space-y-5">
        <form action="admin.php?t=api" method="POST">
        <?php echo Misc::csrfField(); ?>
        <input type="hidden" name="t" value="api">
        <input type="hidden" name="do" value="create_token">

        <div class="form-group">
            <label class="label">Название токена <span class="text-red-500">*</span></label>
            <input type="text" name="name" class="input max-w-md"
                   value="<?php echo $errors ? Format::htmlchars($_POST['name'] ?? '') : ''; ?>"
                   placeholder="например, Рабочая интеграция">
            <?php if (!empty($errors['name'])): ?>
                <span class="form-error"><?php echo Format::htmlchars($errors['name']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="label">Описание</label>
            <input type="text" name="description" class="input max-w-md"
                   value="<?php echo $errors ? Format::htmlchars($_POST['description'] ?? '') : ''; ?>"
                   placeholder="Для чего используется этот токен?">
        </div>

        <div class="form-group">
            <label class="label">Тип токена</label>
            <select name="token_type" class="select max-w-xs">
                <option value="permanent">Постоянный</option>
                <option value="temporary">Временный</option>
                <option value="readonly">Только чтение</option>
            </select>
        </div>

        <div class="form-group">
            <label class="label">Срок действия (дней), только для временных токенов</label>
            <input type="number" name="expires_days" class="input w-36" value="30" min="1" max="365">
        </div>

        <div class="form-group">
            <label class="label">Лимит запросов (запросов/час)</label>
            <input type="number" name="rate_limit" class="input w-36" value="1000" min="10" max="100000">
        </div>

        <div class="form-group">
            <label class="label">Разрешения <span class="text-red-500">*</span></label>
            <?php if (!empty($errors['permissions'])): ?>
                <span class="form-error"><?php echo Format::htmlchars($errors['permissions']); ?></span>
            <?php endif; ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
                <?php
                $posted_perms = ($errors && isset($_POST['permissions'])) ? $_POST['permissions'] : array();
                foreach ($all_permissions as $key => $label):
                    $checked = in_array($key, $posted_perms) ? 'checked' : '';
                ?>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="checkbox" name="permissions[]" value="<?php echo Format::htmlchars($key); ?>" <?php echo $checked; ?>>
                        <span class="text-sm text-gray-700"><?php echo Format::htmlchars($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button class="btn-primary" type="submit"><i data-lucide="plus-circle" class="w-4 h-4"></i> Создать токен</button>
        </div>
        </form>
    </div>
</div>

<!-- Pipe Token Info -->
<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Pipe Token (Email Piping)</h3></div>
    <div class="card-body">
        <p class="text-sm text-gray-600 mb-3">Токен для почтовой пересылки (<code class="bg-gray-100 px-1 rounded">api/pipe.php</code>). Используется для аутентификации запросов email piping.</p>
        <?php $pt = $cfg->getPipeToken(); ?>
        <?php if ($pt): ?>
            <code id="pipe-token" class="block bg-gray-900 text-emerald-400 px-4 py-3 rounded-lg text-sm font-mono w-full break-all select-all"><?php echo Format::htmlchars($pt); ?></code>
            <button class="btn-primary btn-sm mt-2" onclick="copyToken('pipe-token',this)"><i data-lucide="clipboard" class="w-4 h-4"></i> Скопировать</button>
            <p class="text-xs text-gray-400 mt-3">
                <strong>CLI:</strong> <code class="bg-gray-100 text-gray-800 px-1 py-0.5 rounded text-xs">php api/pipe.php <?php echo Format::htmlchars(substr($pt, 0, 8)); ?>... &lt; email.eml</code><br>
                <strong>HTTP:</strong> <code class="bg-gray-100 text-gray-800 px-1 py-0.5 rounded text-xs">curl -H "Authorization: Bearer <?php echo Format::htmlchars(substr($pt, 0, 8)); ?>..." --data-binary @email.eml http://host/api/pipe.php</code>
            </p>
        <?php else: ?>
            <p class="text-amber-600 text-sm">Pipe token не настроен. Выполните миграцию <code>20260318_remove_legacy_api</code>.</p>
        <?php endif; ?>
    </div>
</div>
<script>
function copyToken(elemId, btn) {
    var el = document.getElementById(elemId);
    var text = el.textContent.trim();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            var orig = btn.textContent;
            btn.textContent = 'Скопировано!';
            setTimeout(function() { btn.textContent = orig; }, 1500);
        });
    } else {
        var r = document.createRange();
        r.selectNodeContents(el);
        var s = window.getSelection();
        s.removeAllRanges();
        s.addRange(r);
        document.execCommand('copy');
        s.removeAllRanges();
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = orig; }, 1500);
    }
}
function apiTokenAction(action, tokenId, newStatus, confirmMsg) {
    if (!confirm(confirmMsg)) return;
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = 'admin.php?t=api';
    var fields = {t:'api', do:action, token_id:tokenId, csrf_token:'<?php echo Misc::generateCSRFToken(); ?>'};
    if (action === 'toggle_token') fields.new_status = newStatus;
    for (var k in fields) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = k;
        inp.value = fields[k];
        f.appendChild(inp);
    }
    document.body.appendChild(f);
    f.submit();
}
</script>
