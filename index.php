<?php
require('client.inc.php');
if($thisclient && is_object($thisclient) && $thisclient->isValid()) {
    require('tickets.php');
    exit;
}


require(CLIENTINC_DIR.'header.inc.php');
?>

<!-- Hero Section -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white px-4 py-10 sm:px-12 sm:py-20 mb-6 sm:mb-8">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMiIvPjwvZz48L2c+PC9zdmc+')] opacity-50"></div>
    <div class="relative max-w-3xl mx-auto text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl bg-white/10 backdrop-blur-sm mb-4 sm:mb-6">
            <i data-lucide="headphones" class="w-6 h-6 sm:w-8 sm:h-8"></i>
        </div>
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-heading font-bold mb-3 sm:mb-4">Добро пожаловать в центр технической поддержки</h1>
        <p class="text-indigo-100 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto">
            Каждой заявке присваивается уникальный номер, с помощью которого вы сможете проследить продвижение вашего обращения. Мы храним историю всех ваших заявок.
        </p>
    </div>
</div>

<!-- Action Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
    <!-- Создать новую заявку -->
    <div class="card hover:shadow-card-hover transition-shadow duration-200">
        <div class="p-6 sm:p-8 flex flex-col items-center text-center">
            <div class="w-14 h-14 rounded-xl bg-indigo-50 flex items-center justify-center mb-5">
                <i data-lucide="plus-circle" class="w-7 h-7 text-indigo-600"></i>
            </div>
            <h3 class="text-lg font-heading font-semibold text-gray-900 mb-2">Создать новую заявку</h3>
            <p class="text-gray-500 text-sm mb-6 leading-relaxed">
                Пожалуйста укажите все подробности вашей проблемы, чтобы мы смогли более эффективно помочь вам.
            </p>
            <a href="open.php" class="btn-primary btn-lg w-full sm:w-auto">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Открыть Новый Запрос
            </a>
        </div>
    </div>

    <!-- Проверить статус заявки -->
    <div class="card hover:shadow-card-hover transition-shadow duration-200">
        <div class="p-6 sm:p-8">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-14 h-14 rounded-xl bg-sky-50 flex items-center justify-center mb-5">
                    <i data-lucide="search" class="w-7 h-7 text-sky-600"></i>
                </div>
                <h3 class="text-lg font-heading font-semibold text-gray-900 mb-2">Проверить статус заявки</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Мы храним историю всех ваших обращений.</p>
            </div>

            <form action="login.php" method="post" class="space-y-4">
                <div class="form-group">
                    <label for="lemail" class="label">Email</label>
                    <input type="email" name="lemail" id="lemail" class="input" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label for="lticket" class="label">Номер заявки</label>
                    <input type="text" name="lticket" id="lticket" class="input" placeholder="12345" required>
                </div>
                <button type="submit" class="btn-primary btn-lg w-full">
                    <i data-lucide="search" class="w-4 h-4"></i> Проверить Статус
                </button>
            </form>
        </div>
    </div>
</div>

<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
