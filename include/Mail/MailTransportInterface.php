<?php

declare(strict_types=1);

namespace TicketHub\Mail;

interface MailTransportInterface
{
    /**
     * @throws MailTransportException
     */
    public function send(string $to, array $headers, string $body): bool;

    /**
     * @param array{host: string, port: int, auth: bool, username: string, password: string, timeout?: int} $config
     * @throws MailTransportException
     */
    public function testSmtpConnection(array $config): bool;
}
