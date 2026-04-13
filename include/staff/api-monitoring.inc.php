<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

require_once(INCLUDE_DIR.'class.apimetrics.php');

$hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;
if (!in_array($hours, array(1, 6, 24, 168))) {
    $hours = 24;
}
$days = ($hours >= 168) ? 30 : 7;

$_t0 = microtime(true);
$realtime = ApiMetrics::getRealtimeStats($hours);
$_t1 = microtime(true);
$performance = ApiMetrics::getPerformanceMetrics($hours);
$_t2 = microtime(true);
$errors = ApiMetrics::getErrorAnalysis($hours);
$_t3 = microtime(true);
$tokens = ApiMetrics::getTokenStats();
$_t4 = microtime(true);
$health = ApiMetrics::healthCheck();
$_t5 = microtime(true);
$alerts = ApiMetrics::checkAlerts();
$_t6 = microtime(true);
$trends = ApiMetrics::getUsageTrends($days);
$_t7 = microtime(true);
$historical = ApiMetrics::getHistoricalData($days, 'hour');
$_t8 = microtime(true);
$_debug_timing = sprintf(
    "Realtime: %.3fs | Perf: %.3fs | Errors: %.3fs | Tokens: %.3fs | Health: %.3fs | Alerts: %.3fs | Trends: %.3fs | Historical: %.3fs | TOTAL: %.3fs",
    $_t1-$_t0, $_t2-$_t1, $_t3-$_t2, $_t4-$_t3, $_t5-$_t4, $_t6-$_t5, $_t7-$_t6, $_t8-$_t7, $_t8-$_t0
);

$success_rate = $realtime['total_requests'] > 0
    ? round(($realtime['successful_requests'] / $realtime['total_requests']) * 100, 2)
    : 100;

$health_color = 'emerald';
if ($health['status'] == 'degraded') $health_color = 'amber';
if ($health['status'] == 'unhealthy') $health_color = 'red';

?>

<div id="api-monitoring">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-3">
            <span class="inline-block w-3 h-3 rounded-full bg-<?php echo $health_color; ?>-500"></span>
            Мониторинг API
            <span class="text-sm text-gray-400 font-normal">
                &mdash; <?php echo ucfirst(Format::htmlchars($health['status'])); ?>
            </span>
        </h2>
        <div class="flex items-center gap-4">
            <select class="select w-auto" id="timeRange" onchange="changeTimeRange(this.value)">
                <option value="1" <?php echo $hours == 1 ? 'selected' : ''; ?>>Последний час</option>
                <option value="6" <?php echo $hours == 6 ? 'selected' : ''; ?>>Последние 6 часов</option>
                <option value="24" <?php echo $hours == 24 ? 'selected' : ''; ?>>Последние 24 часа</option>
                <option value="168" <?php echo $hours == 168 ? 'selected' : ''; ?>>Последние 7 дней</option>
            </select>
            <span class="text-xs text-gray-400">
                <i data-lucide="refresh-cw" class="w-3 h-3 inline cursor-pointer" onclick="refreshData()" title="Обновить"></i>
                Автообновление: <span id="countdown">30</span>с
            </span>
        </div>
    </div>

    <div class="alert-warning mb-4 text-xs font-mono">
        <?php echo Format::htmlchars($_debug_timing); ?>
    </div>

    <?php if (!empty($alerts)): ?>
        <div id="alerts-section" class="space-y-3 mb-6">
        <?php foreach ($alerts as $alert): ?>
            <div class="<?php echo $alert['severity'] == 'critical' ? 'alert-danger' : 'alert-warning'; ?>">
                <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
                <div>
                    <div class="font-semibold text-sm"><?php echo Format::htmlchars($alert['type']); ?></div>
                    <div class="text-sm opacity-80"><?php echo Format::htmlchars($alert['message']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="card">
            <div class="card-body">
                <h3 class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Всего запросов</h3>
                <div class="text-2xl font-bold text-gray-900"><?php echo number_format($realtime['total_requests']); ?></div>
                <div class="text-xs text-gray-400 mt-1">
                    <?php
                    if ($hours == 1) echo 'Последний час';
                    elseif ($hours <= 24) echo 'Последние '.$hours.' часов';
                    else echo 'Последние '.($hours / 24).' дней';
                    ?>
                    <?php if ($trends['growth_rate'] != 0): ?>
                        <span class="inline-block px-1 rounded text-xs font-semibold <?php echo $trends['growth_rate'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'; ?>">
                            <?php echo $trends['growth_rate'] > 0 ? '+' : ''; ?><?php echo $trends['growth_rate']; ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Процент успешных</h3>
                <div class="text-2xl font-bold text-gray-900"><?php echo $success_rate; ?><span class="text-sm font-normal text-gray-400">%</span></div>
                <div class="text-xs text-gray-400 mt-1">
                    <?php echo number_format($realtime['successful_requests']); ?> ok /
                    <?php echo number_format($realtime['failed_requests']); ?> неудачных
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Среднее время ответа</h3>
                <div class="text-2xl font-bold text-gray-900"><?php echo $realtime['avg_response_time']; ?><span class="text-sm font-normal text-gray-400">ms</span></div>
                <div class="text-xs text-gray-400 mt-1">
                    P95: <?php echo $performance['p95_response_time']; ?>ms
                    &middot; P99: <?php echo $performance['p99_response_time']; ?>ms
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Запросов/час</h3>
                <div class="text-2xl font-bold text-gray-900"><?php echo number_format($realtime['requests_per_hour']); ?></div>
                <div class="text-xs text-gray-400 mt-1">
                    Пиковая нагрузка: <?php echo Format::htmlchars($trends['busiest_day']); ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Активные токены</h3>
                <div class="text-2xl font-bold text-gray-900"><?php echo $realtime['active_tokens']; ?></div>
                <div class="text-xs text-gray-400 mt-1">
                    Всего: <?php echo $tokens['total_tokens']; ?>
                    &middot; Неактивные: <?php echo $tokens['inactive_tokens']; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Процент ошибок</h3>
                <div class="text-2xl font-bold <?php echo $realtime['error_rate'] > 10 ? 'text-red-500' : ($realtime['error_rate'] > 5 ? 'text-amber-500' : 'text-emerald-600'); ?>">
                    <?php echo $realtime['error_rate']; ?><span class="text-sm font-normal text-gray-400">%</span>
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    <?php echo number_format($errors['total_errors']); ?> ошибок всего
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="card">
            <div class="card-body">
                <h3 class="font-heading font-semibold text-gray-900 mb-4">Объём запросов (последние <?php echo $days; ?> дней)</h3>
                <?php if (!empty($historical)): ?>
                    <canvas id="requestChart" height="180"></canvas>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i data-lucide="bar-chart-2" class="w-8 h-8"></i></div>
                        <p class="empty-state-text">Пока нет данных</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="font-heading font-semibold text-gray-900 mb-4">Тренд времени ответа (последние <?php echo $days; ?> дней)</h3>
                <?php if (!empty($historical)): ?>
                    <canvas id="responseTimeChart" height="180"></canvas>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i data-lucide="history" class="w-8 h-8"></i></div>
                        <p class="empty-state-text">Пока нет данных</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="card">
            <div class="card-header"><h3 class="font-heading font-semibold">Популярные эндпоинты</h3></div>
            <?php if (!empty($realtime['top_endpoints'])): ?>
                <div class="table-wrapper">
                <table class="table-modern text-sm">
                    <thead>
                        <tr>
                            <th class="table-th">Эндпоинт</th>
                            <th class="table-th">Запросы</th>
                            <th class="table-th">Ср. время</th>
                            <th class="table-th">Использование</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $max_ep = 1;
                        foreach ($realtime['top_endpoints'] as $ep) {
                            if ($ep['count'] > $max_ep) $max_ep = $ep['count'];
                        }
                        foreach ($realtime['top_endpoints'] as $endpoint):
                            $pct = round(($endpoint['count'] / $max_ep) * 100);
                        ?>
                            <tr>
                                <td class="table-td"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded"><?php echo Format::htmlchars($endpoint['endpoint']); ?></code></td>
                                <td class="table-td"><?php echo number_format($endpoint['count']); ?></td>
                                <td class="table-td"><?php echo $endpoint['avg_response_time']; ?>ms</td>
                                <td class="table-td">
                                    <div class="h-4 bg-gray-100 rounded-full overflow-hidden min-w-[80px]">
                                        <div class="h-full bg-blue-500 rounded-full flex items-center justify-center text-white text-[10px] font-bold" style="width:<?php echo $pct; ?>%"><?php echo $pct; ?>%</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="card-body"><div class="empty-state"><p class="empty-state-text">Пока нет данных</p></div></div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="font-heading font-semibold">Распределение кодов статуса</h3></div>
            <?php if (!empty($realtime['status_distribution'])): ?>
                <div class="table-wrapper">
                <table class="table-modern text-sm">
                    <thead>
                        <tr>
                            <th class="table-th">Код статуса</th>
                            <th class="table-th">Количество</th>
                            <th class="table-th">Тип</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($realtime['status_distribution'] as $code => $cnt):
                            $badgeCls = 'badge-success';
                            $label = 'Успех';
                            if ($code >= 500) { $badgeCls = 'badge-danger'; $label = 'Ошибка сервера'; }
                            elseif ($code >= 400) { $badgeCls = 'badge-warning'; $label = 'Ошибка клиента'; }
                            elseif ($code >= 300) { $badgeCls = 'badge-info'; $label = 'Перенаправление'; }
                        ?>
                            <tr>
                                <td class="table-td"><span class="<?php echo $badgeCls; ?>"><?php echo (int)$code; ?></span></td>
                                <td class="table-td"><?php echo number_format($cnt); ?></td>
                                <td class="table-td"><?php echo $label; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="card-body"><div class="empty-state"><p class="empty-state-text">Пока нет данных</p></div></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="font-heading font-semibold">Использование токенов (последние 24 часа)</h3></div>
        <?php if (!empty($tokens['token_usage'])): ?>
            <div class="table-wrapper">
            <table class="table-modern text-sm">
                <thead>
                    <tr>
                        <th class="table-th">Имя токена</th>
                        <th class="table-th">Запросы</th>
                        <th class="table-th">Последнее использование</th>
                        <th class="table-th">Использование лимита</th>
                        <th class="table-th">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens['token_usage'] as $token):
                        $rate_pct = $token['rate_limit_percentage'];
                        $bar_color = 'bg-blue-500';
                        if ($rate_pct > 80) $bar_color = 'bg-red-500';
                        elseif ($rate_pct > 60) $bar_color = 'bg-amber-500';
                    ?>
                        <tr>
                            <td class="table-td"><?php echo Format::htmlchars($token['name']); ?></td>
                            <td class="table-td"><?php echo number_format($token['requests']); ?></td>
                            <td class="table-td"><?php echo $token['last_used'] ? Format::htmlchars($token['last_used']) : '<em class="text-gray-400">Никогда</em>'; ?></td>
                            <td class="table-td">
                                <div class="h-4 bg-gray-100 rounded-full overflow-hidden min-w-[80px]">
                                    <div class="h-full <?php echo $bar_color; ?> rounded-full flex items-center justify-center text-white text-[10px] font-bold" style="width:<?php echo max(1, $rate_pct); ?>%">
                                        <?php echo $rate_pct; ?>%
                                    </div>
                                </div>
                            </td>
                            <td class="table-td">
                                <span class="<?php echo $token['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $token['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php else: ?>
            <div class="card-body"><div class="empty-state"><p class="empty-state-text">Токены ещё не созданы</p></div></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($errors['recent_errors'])): ?>
    <div class="card mb-6">
        <div class="card-header"><h3 class="font-heading font-semibold">Последние ошибки</h3></div>
        <div class="table-wrapper">
        <table class="table-modern text-sm">
            <thead>
                <tr>
                    <th class="table-th">Время</th>
                    <th class="table-th">Эндпоинт</th>
                    <th class="table-th">Статус</th>
                    <th class="table-th">IP адрес</th>
                    <th class="table-th">Время ответа</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($errors['recent_errors'] as $error): ?>
                    <tr>
                        <td class="table-td"><?php echo Format::htmlchars($error['timestamp']); ?></td>
                        <td class="table-td"><code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded"><?php echo Format::htmlchars($error['endpoint']); ?></code></td>
                        <td class="table-td"><span class="badge-danger"><?php echo (int)$error['status_code']; ?></span></td>
                        <td class="table-td"><?php echo Format::htmlchars($error['ip_address']); ?></td>
                        <td class="table-td"><?php echo (int)$error['response_time']; ?>ms</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-6">
        <div class="card-header"><h3 class="font-heading font-semibold">Состояние системы</h3></div>
        <div class="table-wrapper">
        <table class="table-modern text-sm">
            <thead>
                <tr>
                    <th class="table-th">Component</th>
                    <th class="table-th">Status</th>
                    <th class="table-th">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($health['checks'] as $check):
                    $sBadge = 'badge-success';
                    if ($check['status'] == 'warning') $sBadge = 'badge-warning';
                    if ($check['status'] == 'error') $sBadge = 'badge-danger';
                ?>
                    <tr>
                        <td class="table-td"><?php echo Format::htmlchars($check['component']); ?></td>
                        <td class="table-td">
                            <span class="<?php echo $sBadge; ?>">
                                <?php echo strtoupper(Format::htmlchars($check['status'])); ?>
                            </span>
                        </td>
                        <td class="table-td"><?php echo Format::htmlchars($check['message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="font-heading font-semibold">Performance Summary</h3></div>
        <div class="table-wrapper">
        <table class="table-modern text-sm">
            <thead>
                <tr>
                    <th class="table-th">Metric</th>
                    <th class="table-th">Value</th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="table-td">Min Response Time</td><td class="table-td"><?php echo $performance['min_response_time']; ?>ms</td></tr>
                <tr><td class="table-td">Avg Response Time</td><td class="table-td"><?php echo $performance['avg_response_time']; ?>ms</td></tr>
                <tr><td class="table-td">Median (P50)</td><td class="table-td"><?php echo $performance['p50_response_time']; ?>ms</td></tr>
                <tr><td class="table-td">P95 Response Time</td><td class="table-td"><?php echo $performance['p95_response_time']; ?>ms</td></tr>
                <tr><td class="table-td">P99 Response Time</td><td class="table-td"><?php echo $performance['p99_response_time']; ?>ms</td></tr>
                <tr><td class="table-td">Max Response Time</td><td class="table-td"><?php echo $performance['max_response_time']; ?>ms</td></tr>
                <tr><td class="table-td">Slow Requests (&gt;2s)</td><td class="table-td"><?php echo number_format($performance['slow_requests']); ?></td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script src="js/lib/Chart.min.js"></script>
<script type="text/javascript">
var refreshInterval = 30;
var countdownVal = refreshInterval;
var countdownTimer = null;

function startCountdown() {
    countdownVal = refreshInterval;
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(function() {
        countdownVal--;
        var el = document.getElementById('countdown');
        if (el) el.textContent = countdownVal;
        if (countdownVal <= 0) {
            refreshData();
        }
    }, 1000);
}

function changeTimeRange(hours) {
    window.location.href = 'admin.php?t=api-monitoring&hours=' + hours;
}

function refreshData() {
    window.location.reload();
}

if (window.addEventListener) {
    window.addEventListener('load', startCountdown);
} else {
    window.attachEvent('onload', startCountdown);
}

<?php if (!empty($historical)): ?>
var historicalData = <?php echo json_encode($historical); ?>;

var chartLabels = [];
var chartRequests = [];
var chartResponseTimes = [];
for (var i = 0; i < historicalData.length; i++) {
    var ts = historicalData[i].timestamp;
    if (ts.length > 10) {
        var parts = ts.split(' ');
        var dateParts = parts[0].split('-');
        chartLabels.push(dateParts[1] + '/' + dateParts[2] + ' ' + (parts[1] ? parts[1].substr(0,5) : ''));
    } else {
        var dateParts = ts.split('-');
        chartLabels.push(dateParts[1] + '/' + dateParts[2]);
    }
    chartRequests.push(historicalData[i].request_count);
    chartResponseTimes.push(historicalData[i].avg_response_time);
}

var reqCtx = document.getElementById('requestChart');
if (reqCtx) {
    new Chart(reqCtx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Requests',
                data: chartRequests,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{ ticks: { beginAtZero: true } }],
                xAxes: [{ ticks: { maxTicksLimit: 12 } }]
            },
            legend: { display: false }
        }
    });
}

var rtCtx = document.getElementById('responseTimeChart');
if (rtCtx) {
    new Chart(rtCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Avg Response Time (ms)',
                data: chartResponseTimes,
                backgroundColor: 'rgba(255, 159, 64, 0.1)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 2,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: 'rgba(255, 159, 64, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(v) { return v + 'ms'; }
                    }
                }],
                xAxes: [{ ticks: { maxTicksLimit: 12 } }]
            },
            legend: { display: false },
            tooltips: {
                callbacks: {
                    label: function(item) { return item.yLabel + 'ms'; }
                }
            }
        }
    });
}
<?php endif; ?>
</script>
