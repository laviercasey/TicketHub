<?php

define('OSTSCPINC', TRUE);
require_once('../main.inc.php');
require_once(INCLUDE_DIR.'class.apisecurity.php');

$allowed_ips = array('127.0.0.1', '::1', 'localhost');
$client_ip = $_SERVER['REMOTE_ADDR'];

if (!in_array($client_ip, $allowed_ips)) {
    if (!isset($thisstaff) || !$thisstaff->isAdmin()) {
        die('Access Denied: Run from localhost or login as admin');
    }
}

$checks = array();
$warnings = array();
$errors = array();

$check = array(
    'name' => 'HTTPS Requirement',
    'status' => 'ok',
    'message' => ''
);

$https_required = $cfg->get('api_require_https', 0);
if (!$https_required) {
    $check['status'] = 'warning';
    $check['message'] = 'HTTPS is not required. Enable for production!';
    $warnings[] = 'HTTPS enforcement disabled';
} else {
    $check['message'] = 'HTTPS is required for API access';
}
$checks[] = $check;

$check = array(
    'name' => 'Security Headers',
    'status' => 'ok',
    'message' => ''
);

$headers_enabled = $cfg->get('api_security_headers_enabled', 1);
if (!$headers_enabled) {
    $check['status'] = 'error';
    $check['message'] = 'Security headers are DISABLED!';
    $errors[] = 'Security headers disabled';
} else {
    $check['message'] = 'Security headers enabled';
}
$checks[] = $check;

$check = array(
    'name' => 'Rate Limiting',
    'status' => 'ok',
    'message' => ''
);

$default_limit = $cfg->get('api_default_rate_limit', 0);
if ($default_limit < 100) {
    $check['status'] = 'warning';
    $check['message'] = "Rate limit is low ($default_limit/hour). Consider increasing.";
    $warnings[] = 'Low rate limit';
} else {
    $check['message'] = "Rate limit: $default_limit requests/hour";
}
$checks[] = $check;

$check = array(
    'name' => 'Brute Force Protection',
    'status' => 'ok',
    'message' => ''
);

$bf_enabled = $cfg->get('api_brute_force_protection', 1);
if (!$bf_enabled) {
    $check['status'] = 'error';
    $check['message'] = 'Brute force protection is DISABLED!';
    $errors[] = 'Brute force protection disabled';
} else {
    $max_attempts = $cfg->get('api_brute_force_max_attempts', 5);
    $window = $cfg->get('api_brute_force_window', 300);
    $check['message'] = "Max $max_attempts attempts per " . ($window/60) . " minutes";
}
$checks[] = $check;

$check = array(
    'name' => 'Audit Logging',
    'status' => 'ok',
    'message' => ''
);

$audit_enabled = $cfg->get('api_audit_log_enabled', 1);
if (!$audit_enabled) {
    $check['status'] = 'warning';
    $check['message'] = 'Audit logging is disabled';
    $warnings[] = 'Audit logging disabled';
} else {
    $retention = $cfg->get('api_audit_log_retention_days', 90);
    $check['message'] = "Enabled (retention: $retention days)";
}
$checks[] = $check;

$check = array(
    'name' => 'Admin Tokens',
    'status' => 'ok',
    'message' => ''
);

$sql = "SELECT COUNT(*) as count FROM " . API_TOKEN_TABLE . "
        WHERE is_active=1 AND permissions LIKE '%admin:*%'";
$result = db_query($sql);
$row = db_fetch_array($result);
$admin_tokens = $row['count'];

if ($admin_tokens > 3) {
    $check['status'] = 'warning';
    $check['message'] = "$admin_tokens tokens have admin:* permission. Review and minimize.";
    $warnings[] = 'Too many admin tokens';
} else {
    $check['message'] = "$admin_tokens tokens with admin:* permission";
}
$checks[] = $check;

$check = array(
    'name' => 'Inactive Tokens',
    'status' => 'ok',
    'message' => ''
);

$sql = "SELECT COUNT(*) as count FROM " . API_TOKEN_TABLE . "
        WHERE is_active=1
        AND (last_used IS NULL OR last_used < DATE_SUB(NOW(), INTERVAL 90 DAY))";
$result = db_query($sql);
$row = db_fetch_array($result);
$unused_tokens = $row['count'];

if ($unused_tokens > 0) {
    $check['status'] = 'warning';
    $check['message'] = "$unused_tokens tokens not used in 90+ days. Consider deactivating.";
    $warnings[] = 'Unused tokens found';
} else {
    $check['message'] = 'No inactive tokens';
}
$checks[] = $check;

$check = array(
    'name' => 'Recent Security Events',
    'status' => 'ok',
    'message' => ''
);

$sql = "SELECT COUNT(*) as count FROM " . API_AUDIT_LOG_TABLE . "
        WHERE severity IN ('warning', 'error', 'critical')
        AND created > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$result = db_query($sql);
$row = db_fetch_array($result);
$security_events = $row['count'];

if ($security_events > 50) {
    $check['status'] = 'error';
    $check['message'] = "$security_events security events in last 24h! INVESTIGATE!";
    $errors[] = 'High security event count';
} elseif ($security_events > 10) {
    $check['status'] = 'warning';
    $check['message'] = "$security_events security events in last 24h";
    $warnings[] = 'Elevated security events';
} else {
    $check['message'] = "$security_events security events in last 24h";
}
$checks[] = $check;

$check = array(
    'name' => 'IP Blacklist',
    'status' => 'ok',
    'message' => ''
);

$sql = "SELECT COUNT(*) as count FROM " . API_IP_BLACKLIST_TABLE . "
        WHERE expires_at IS NULL OR expires_at > NOW()";
$result = db_query($sql);
$row = db_fetch_array($result);
$blacklisted = $row['count'];

$check['message'] = "$blacklisted IP(s) currently blacklisted";
$checks[] = $check;

$check = array(
    'name' => 'Attack Attempts (7 days)',
    'status' => 'ok',
    'message' => ''
);

$sql = "SELECT COUNT(*) as count FROM " . API_AUDIT_LOG_TABLE . "
        WHERE event_type IN ('sql_injection_attempt', 'xss_attempt')
        AND created > DATE_SUB(NOW(), INTERVAL 7 DAY)";
$result = db_query($sql);
$row = db_fetch_array($result);
$attacks = $row['count'];

if ($attacks > 0) {
    $check['status'] = 'warning';
    $check['message'] = "$attacks attack attempts detected. Review logs.";
    $warnings[] = 'Attack attempts detected';
} else {
    $check['message'] = 'No attack attempts detected';
}
$checks[] = $check;

$overall_status = 'ok';
if (count($errors) > 0) {
    $overall_status = 'error';
} elseif (count($warnings) > 0) {
    $overall_status = 'warning';
}

if (php_sapi_name() == 'cli') {
    echo "\n";
    echo "=================================================\n";
    echo "   TicketHub API Security Check\n";
    echo "=================================================\n\n";

    foreach ($checks as $check) {
        $status_icon = $check['status'] == 'ok' ? '✓' : ($check['status'] == 'warning' ? '⚠' : '✗');
        $status_text = strtoupper($check['status']);

        printf("%-30s [%s] %s\n", $check['name'], $status_text, $status_icon);
        printf("  %s\n\n", $check['message']);
    }

    echo "=================================================\n";
    echo "Summary:\n";
    echo "  Errors: " . count($errors) . "\n";
    echo "  Warnings: " . count($warnings) . "\n";
    echo "  Overall Status: " . strtoupper($overall_status) . "\n";
    echo "=================================================\n\n";

    if (count($errors) > 0) {
        echo "ERRORS:\n";
        foreach ($errors as $error) {
            echo "  • $error\n";
        }
        echo "\n";
    }

    if (count($warnings) > 0) {
        echo "WARNINGS:\n";
        foreach ($warnings as $warning) {
            echo "  • $warning\n";
        }
        echo "\n";
    }

} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>API Security Check</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                max-width: 900px;
                margin: 40px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                border-radius: 8px;
                padding: 30px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
                margin-top: 0;
            }
            .check {
                padding: 15px;
                margin: 10px 0;
                border-left: 4px solid #ddd;
                background: #f9f9f9;
            }
            .check.ok {
                border-color: #4caf50;
                background: #f1f8f4;
            }
            .check.warning {
                border-color: #ff9800;
                background: #fff8f0;
            }
            .check.error {
                border-color: #f44336;
                background: #fef5f5;
            }
            .check-name {
                font-weight: bold;
                margin-bottom: 5px;
            }
            .check-status {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 12px;
                margin-left: 10px;
            }
            .check-status.ok {
                background: #4caf50;
                color: white;
            }
            .check-status.warning {
                background: #ff9800;
                color: white;
            }
            .check-status.error {
                background: #f44336;
                color: white;
            }
            .check-message {
                color: #666;
                font-size: 14px;
            }
            .summary {
                margin-top: 30px;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 4px;
            }
            .summary-item {
                display: inline-block;
                margin-right: 30px;
            }
            .summary-value {
                font-size: 24px;
                font-weight: bold;
            }
            .summary-label {
                color: #666;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔒 API Security Check</h1>
            <p style="color: #666;">Automated security audit for TicketHub REST API v1</p>

            <?php foreach ($checks as $check): ?>
            <div class="check <?php echo $check['status']; ?>">
                <div class="check-name">
                    <?php echo Format::htmlchars($check['name']); ?>
                    <span class="check-status <?php echo $check['status']; ?>">
                        <?php echo strtoupper($check['status']); ?>
                    </span>
                </div>
                <div class="check-message">
                    <?php echo Format::htmlchars($check['message']); ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="summary">
                <div class="summary-item">
                    <div class="summary-value" style="color: #f44336;"><?php echo count($errors); ?></div>
                    <div class="summary-label">Errors</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" style="color: #ff9800;"><?php echo count($warnings); ?></div>
                    <div class="summary-label">Warnings</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" style="color: <?php echo $overall_status == 'ok' ? '#4caf50' : ($overall_status == 'warning' ? '#ff9800' : '#f44336'); ?>">
                        <?php echo strtoupper($overall_status); ?>
                    </div>
                    <div class="summary-label">Overall Status</div>
                </div>
            </div>

            <p style="margin-top: 20px; color: #999; font-size: 12px;">
                Generated: <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>
    </body>
    </html>
    <?php
}

?>
