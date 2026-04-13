<?php defined('OSTSCPINC') or die('Invalid path'); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TicketHub — Панель Управления</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/tickethub-scp.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <meta name="robots" content="noindex">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="pragma" content="no-cache">
</head>
<body class="bg-gray-50 font-body antialiased">
<div class="min-h-screen flex">
    <!-- Left Panel: Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 to-indigo-800 text-white flex-col justify-center items-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMiIvPjwvZz48L2c+PC9zdmc+')] opacity-50"></div>
        <div class="relative text-center max-w-md">
            <div class="w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-8">
                <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><path d="M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z"/></svg>
            </div>
            <h1 class="text-3xl font-heading font-bold mb-4">TicketHub</h1>
            <p class="text-indigo-200 text-lg leading-relaxed">Панель управления для эффективной обработки заявок и задач вашей организации</p>
        </div>
    </div>

    <!-- Right Panel: Login Form -->
    <div class="flex-1 flex flex-col justify-center items-center p-6 sm:p-12">
        <div class="w-full max-w-sm">
            <!-- Mobile logo -->
            <div class="lg:hidden flex justify-center mb-8">
                <div class="w-14 h-14 bg-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><path d="M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z"/></svg>
                </div>
            </div>

            <h2 class="text-2xl font-heading font-bold text-gray-900 text-center mb-2">Вход в панель</h2>
            <p class="text-sm text-gray-500 text-center mb-8">Введите учётные данные для входа</p>

            <?php if($msg) { ?>
            <div class="alert-danger mb-6">
                <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
                <span><?=Format::htmlchars($msg)?></span>
            </div>
            <?php } ?>

            <form action="login.php" method="post" class="space-y-5">
                <?php echo Misc::csrfField(); ?>
                <input type="hidden" name="do" value="scplogin">

                <div class="form-group">
                    <label for="name" class="label">Логин</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="username" id="name" class="input pl-10" placeholder="Введите логин" autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pass" class="label">Пароль</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="password" name="passwd" id="pass" class="input pl-10" placeholder="Введите пароль">
                    </div>
                </div>

                <button type="submit" name="submit" value="Войти" class="btn-primary btn-lg w-full">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Войти
                </button>
            </form>

            <p class="text-center text-xs text-gray-400 mt-8">
                Copyright &copy; <?=date('Y')?> TicketHub
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
</body>
</html>
