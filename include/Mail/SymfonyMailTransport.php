<?php

declare(strict_types=1);

namespace TicketHub\Mail;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\RawMessage;

final class SymfonyMailTransport implements MailTransportInterface
{
    private readonly ?array $smtpConfig;

    /**
     * @param array{host: string, port: int, auth: bool, username: string, password: string, timeout?: int}|null $smtpConfig
     */
    public function __construct(?array $smtpConfig = null)
    {
        $this->smtpConfig = $smtpConfig;
    }

    public function send(string $to, array $headers, string $body): bool
    {
        $rawMessage = $this->buildRawMessage($headers, $body);

        try {
            $transport = $this->smtpConfig !== null
                ? $this->createSmtpTransport($this->smtpConfig)
                : new SendmailTransport();

            $transport->send(new RawMessage($rawMessage));
        } catch (TransportExceptionInterface $e) {
            throw new MailTransportException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return true;
    }

    public function testSmtpConnection(array $config): bool
    {
        try {
            $transport = $this->createSmtpTransport($config);
            $transport->start();
            $transport->stop();
        } catch (TransportExceptionInterface $e) {
            throw new MailTransportException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return true;
    }

    private function createSmtpTransport(array $config): EsmtpTransport
    {
        $host = $config['host'] ?? 'localhost';
        $port = (int) ($config['port'] ?? 25);

        $tls = $config['tls'] ?? match (true) {
            $port === 465 => true,
            default       => null,
        };

        $transport = new EsmtpTransport(
            host: $host,
            port: $port,
            tls:  $tls,
        );

        if (!empty($config['auth'])) {
            $transport->setUsername($config['username'] ?? '');
            $transport->setPassword($config['password'] ?? '');
        }

        if (isset($config['timeout'])) {
            /** @var SocketStream $stream */
            $stream = $transport->getStream();
            $stream->setTimeout((float) $config['timeout']);
        }

        return $transport;
    }

    private function buildRawMessage(array $headers, string $body): string
    {
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $name = $this->sanitizeHeaderName($name);
            if (is_array($value)) {
                foreach ($value as $v) {
                    $headerLines[] = $name . ': ' . $this->sanitizeHeaderValue($v);
                }
            } else {
                $headerLines[] = $name . ': ' . $this->sanitizeHeaderValue($value);
            }
        }

        return implode("\r\n", $headerLines) . "\r\n\r\n" . $body;
    }

    private function sanitizeHeaderValue(string $value): string
    {
        return str_replace(["\r\n", "\r", "\n", "\0"], '', $value);
    }

    private function sanitizeHeaderName(string $name): string
    {
        if (!preg_match('/^[\x21-\x39\x3B-\x7E]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid header name: $name");
        }
        return $name;
    }
}
