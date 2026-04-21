<?php
$title=($cfg && is_object($cfg))?$cfg->getTitle():'TicketHub — Система Технической Поддержки';
header("Content-Type: text/html; charset=UTF-8");
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=Format::htmlchars($title)?></title>

    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon-180.png">
    <meta name="theme-color" content="#4f46e5">

    <!-- Google Fonts: Poppins + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind compiled CSS -->
    <link rel="stylesheet" href="./styles/tickethub.css?v=<?=filemtime(__DIR__.'/../../styles/tickethub.css')?>">

    <!-- Lucide Icons (pinned to 0.263.1 for createIcons API) -->
    <script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.min.js"></script>
</head>
<body class="bg-gray-50 font-body text-gray-900 antialiased" x-data="{ mobileMenu: false }">

<!-- Fixed Navbar -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200 no-print">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><path d="M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z"/></svg>
                </div>
                <span class="text-lg font-heading font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">TicketHub</span>
            </a>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center gap-1">
                <a href="index.php" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                    <i data-lucide="home" class="w-4 h-4"></i> Главная
                </a>
                <a href="open.php" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Новая заявка
                </a>
                <a href="docs.php" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                    <i data-lucide="book-open" class="w-4 h-4"></i> База знаний
                </a>
                <?php if($thisclient && is_object($thisclient) && $thisclient->isValid()) { ?>
                    <a href="tickets.php" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                        <i data-lucide="inbox" class="w-4 h-4"></i> Мои заявки
                    </a>
                    <a href="logout.php" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Выход
                    </a>
                <?php } else { ?>
                    <a href="tickets.php" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                        <i data-lucide="search" class="w-4 h-4"></i> Статус заявки
                    </a>
                <?php } ?>
                <div class="w-px h-5 bg-gray-200 mx-1"></div>
                <a href="scp/" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Панель менеджера
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" aria-label="Меню">
                <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="mobileMenu" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenu" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1" class="md:hidden border-t border-gray-200 bg-white">
        <div class="px-4 py-3 space-y-1">
            <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg">
                <i data-lucide="home" class="w-4 h-4"></i> Главная
            </a>
            <a href="open.php" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Новая заявка
            </a>
            <a href="docs.php" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg">
                <i data-lucide="book-open" class="w-4 h-4"></i> База знаний
            </a>
            <?php if($thisclient && is_object($thisclient) && $thisclient->isValid()) { ?>
                <a href="tickets.php" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg">
                    <i data-lucide="inbox" class="w-4 h-4"></i> Мои заявки
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-red-500 hover:bg-red-50 rounded-lg">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Выход
                </a>
            <?php } else { ?>
                <a href="tickets.php" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg">
                    <i data-lucide="search" class="w-4 h-4"></i> Статус заявки
                </a>
            <?php } ?>
            <div class="border-t border-gray-100 my-1"></div>
            <a href="scp/" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Панель менеджера
            </a>
        </div>
    </div>
</nav>

<!-- Main Content (offset for fixed nav) -->
<div class="pt-16">
    <main role="main" class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
