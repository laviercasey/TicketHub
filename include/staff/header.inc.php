<? if(!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff() || !is_object($nav)) die('Доступ запрещён'); ?>
<?php
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

$lucideMap = array(
    'Ticket' => 'ticket', 'assignedTickets' => 'user-check', 'overdueTickets' => 'clock',
    'answeredTickets' => 'check-circle', 'closedTickets' => 'x-circle', 'archivedTickets' => 'archive',
    'newTicket' => 'plus-circle', 'premade' => 'file-text', 'newPremade' => 'file-plus',
    'staff' => 'users', 'user' => 'user', 'userPref' => 'settings', 'userPasswd' => 'lock',
    'preferences' => 'settings', 'attachment' => 'paperclip', 'api' => 'key',
    'syslogs' => 'list', 'emailTemplates' => 'mail', 'emailSettings' => 'mail',
    'newEmail' => 'mail-plus', 'banList' => 'ban', 'users' => 'users',
    'newuser' => 'user-plus', 'groups' => 'group', 'newgroup' => 'plus-square',
    'helpTopics' => 'help-circle', 'newHelpTopic' => 'plus-circle',
    'departments' => 'building-2', 'newDepartment' => 'plus-square',
    'kbCategories' => 'folder-open', 'newKBCategory' => 'folder-plus',
    'kbArticles' => 'file-text', 'newKBArticle' => 'file-plus',
    'directory' => 'contact', 'documents' => 'book-open',
    'myTasks' => 'user-circle', 'allTasks' => 'check-square',
    'taskBoards' => 'columns', 'newTask' => 'plus-circle', 'newBoard' => 'plus-square',
    'inventoryAll' => 'boxes', 'inventoryAdd' => 'plus-circle',
    'inventoryLocations' => 'map-pin', 'inventoryCatalog' => 'tags',
    'inventoryHistory' => 'history', 'inventoryExport' => 'download',
    'priorityUsers' => 'star', 'newPriorityUser' => 'star'
);

$tabIconMap = array(
    'tickets' => 'inbox', 'kbase' => 'book-open', 'tasks' => 'check-square',
    'inventory' => 'boxes', 'directory' => 'contact', 'profile' => 'user',
    'dashboard' => 'layout-dashboard', 'settings' => 'settings',
    'emails' => 'mail', 'topics' => 'help-circle', 'staff' => 'users', 'depts' => 'building-2'
);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php
if(defined('AUTO_REFRESH') && is_numeric(AUTO_REFRESH_RATE) && AUTO_REFRESH_RATE>0){
    echo '<meta http-equiv="refresh" content="'.AUTO_REFRESH_RATE.'" />';
}
?>
    <title>TicketHub — Панель Управления</title>

    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon-180.png">
    <meta name="theme-color" content="#4f46e5">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/tickethub-scp.css">

    <link rel="stylesheet" href="css/autosuggest_inquisitor.css" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <link rel="stylesheet" href="css/lib/fullcalendar.min.css" type="text/css">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/mans.js"></script>
    <script src="js/scp.js"></script>
    <script src="js/tabber.js"></script>
    <script src="js/bsn.AutoSuggest_2.1.3.js" charset="utf-8"></script>
    <script src="js/inventory.js"></script>
<?php if($cfg && $cfg->getLockTime()) { ?>
    <script src="js/autolock.js" charset="utf-8"></script>
<?php } ?>
</head>
<body class="bg-gray-50 font-body text-gray-900 antialiased" x-data="{ sidebarOpen: false }">

<?php if($sysnotice){ ?>
<div class="fixed top-0 left-0 right-0 z-[60] p-4" id="system_notice_wrap">
    <div class="max-w-4xl mx-auto">
        <div class="alert-warning flex items-start gap-3" id="system_notice">
            <div class="flex-1"><?php echo $sysnotice; ?></div>
            <button type="button" onclick="document.getElementById('system_notice_wrap').remove()" class="flex-shrink-0 p-0.5 text-amber-700 hover:text-amber-900 hover:bg-amber-200 rounded transition-colors" aria-label="Закрыть">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</div>
<?php } ?>

<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed top-0 left-0 z-50 w-64 h-full bg-gray-900 transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col no-print">

    <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-800 flex-shrink-0">
        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><path d="M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z"/></svg>
        </div>
        <span class="text-white font-heading font-bold text-lg">TicketHub</span>
        <button @click="sidebarOpen = false" class="ml-auto lg:hidden text-gray-400 hover:text-white p-1">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto custom-scrollbar px-3 py-4 space-y-1">
        <?php
        if(($tabs=$nav->getTabs()) && is_array($tabs)){
            foreach($tabs as $tabKey => $navTab) {
                $isActive = !empty($navTab['active']);
                $icon = isset($tabIconMap[$tabKey]) ? $tabIconMap[$tabKey] : 'circle';
                $linkClass = $isActive ? 'sidebar-link-active' : 'sidebar-link-default';
        ?>
        <a href="<?=htmlspecialchars($navTab['href'])?>" title="<?=htmlspecialchars($navTab['title'] ?? '')?>" class="<?=$linkClass?>">
            <i data-lucide="<?=$icon?>" class="w-5 h-5 flex-shrink-0"></i>
            <span><?=htmlspecialchars($navTab['desc'])?></span>
        </a>
        <?php
            }
        } else { ?>
        <a href="profile.php" title="Мои Настройки" class="sidebar-link-default">
            <i data-lucide="user" class="w-5 h-5 flex-shrink-0"></i>
            <span>Мой Аккаунт</span>
        </a>
        <?php } ?>

        <?php if($thisuser->isAdmin() && !defined('ADMINPAGE')) { ?>
        <div class="border-t border-gray-800 my-3"></div>
        <a href="admin.php" class="sidebar-link-default">
            <i data-lucide="shield" class="w-5 h-5 flex-shrink-0"></i>
            <span>Администрирование</span>
        </a>
        <?php } elseif(defined('ADMINPAGE')) { ?>
        <div class="border-t border-gray-800 my-3"></div>
        <a href="index.php" class="sidebar-link-default">
            <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
            <span>Панель Управления</span>
        </a>
        <?php } ?>
    </nav>

    <div class="flex-shrink-0 border-t border-gray-800 p-4">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                <?=strtoupper(mb_substr($thisuser->getUsername(), 0, 1, 'UTF-8'))?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate"><?=htmlspecialchars($thisuser->getUsername())?></p>
                <p class="text-xs text-gray-400 truncate"><?=$thisuser->isAdmin() ? 'Администратор' : 'Сотрудник'?></p>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-red-400 transition-colors" title="Выход">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</aside>

<div class="lg:ml-64 min-h-screen flex flex-col">

    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200 h-16 flex items-center px-4 sm:px-6 no-print">
        <button @click="sidebarOpen = true" class="lg:hidden mr-4 p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" aria-label="Меню">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <?php if(($subnav=$nav->getSubMenu()) && is_array($subnav)){ ?>
        <div class="flex items-center gap-1 overflow-x-auto flex-1">
            <?php foreach($subnav as $menuItem) {
                $lucideIcon = isset($lucideMap[$menuItem['iconclass']]) ? $lucideMap[$menuItem['iconclass']] : 'circle';
            ?>
            <a href="<?=htmlspecialchars($menuItem['href'])?>" title="<?=htmlspecialchars($menuItem['title'] ?? '')?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors whitespace-nowrap">
                <i data-lucide="<?=$lucideIcon?>" class="w-4 h-4"></i>
                <span><?=htmlspecialchars($menuItem['desc'])?></span>
            </a>
            <?php } ?>
        </div>
        <?php } else { ?>
        <div class="flex-1"></div>
        <?php } ?>

        <div class="flex items-center gap-2 ml-4">
            <a href="profile.php?t=pref" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Настройки">
                <i data-lucide="settings" class="w-5 h-5"></i>
            </a>
        </div>
    </header>

    <main role="main" class="flex-1 p-4 sm:p-6">
