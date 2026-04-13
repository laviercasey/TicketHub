<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');

class TokensController extends ApiController {

    function current() {
        $this->checkMethod(array('GET'));
        $this->requireAuth();

        $rate_info = $this->token->getRateLimitInfo();

        $data = array(
            'token_id' => $this->token->getId(),
            'name' => $this->token->getName(),
            'description' => $this->token->getDescription(),
            'type' => $this->token->getType(),
            'permissions' => $this->token->getPermissions(),
            'is_active' => $this->token->isActive(),
            'is_expired' => $this->token->isExpired(),
            'rate_limit' => array(
                'limit' => $rate_info['limit'],
                'remaining' => $rate_info['remaining'],
                'reset_at' => $this->formatDate(date('Y-m-d H:i:s', $rate_info['reset'])),
                'window_seconds' => $rate_info['window']
            ),
            'usage' => array(
                'total_requests' => $this->token->getTotalRequests(),
                'last_used_at' => $this->formatDate($this->token->getLastUsedAt())
            ),
            'created_at' => $this->formatDate($this->token->created_at)
        );

        ApiResponse::success($data)
            ->setRateLimitHeaders(
                $rate_info['limit'],
                $rate_info['remaining'],
                $rate_info['reset'],
                $rate_info['window']
            )
            ->send();
    }

    function usage() {
        $this->checkMethod(array('GET'));
        $this->requireAuth();

        $days = (int)$this->getQuery('days', 7);
        if ($days < 1) $days = 7;
        if ($days > 90) $days = 90;

        require_once(INCLUDE_DIR.'class.api.php');
        $stats = Api::getStats($this->token->getId(), $days);

        $data = array(
            'period_days' => $days,
            'total_requests' => $stats['total_requests'],
            'avg_response_time_ms' => $stats['avg_response_time'],
            'by_status_code' => $stats['by_status'],
            'top_endpoints' => $stats['top_endpoints'],
            'current_rate_limit' => $this->token->getRateLimitInfo()
        );

        ApiResponse::success($data)->send();
    }
}

?>
