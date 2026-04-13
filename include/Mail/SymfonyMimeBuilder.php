<?php

declare(strict_types=1);

namespace TicketHub\Mail;

use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\MixedPart;
use Symfony\Component\Mime\Part\TextPart;

final class SymfonyMimeBuilder implements MimeBuilderInterface
{
    private string $textBody = '';

    /** @var list<array{filePath: string, mimeType: string, name: string}> */
    private array $attachments = [];

    private ?AbstractPart $builtPart = null;

    public function setTextBody(string $body): void
    {
        $this->textBody = $body;
        $this->builtPart = null;
    }

    public function addAttachment(string $filePath, string $mimeType, string $name): void
    {
        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException(
                sprintf('Attachment file is not readable: %s', $filePath)
            );
        }

        $this->attachments[] = [
            'filePath' => $filePath,
            'mimeType' => $mimeType,
            'name'     => $name,
        ];
        $this->builtPart = null;
    }

    public function getBody(array $options = []): string
    {
        $encoding = $options['text_encoding'] ?? 'quoted-printable';
        $charset  = $options['text_charset'] ?? 'utf-8';

        $textPart = new TextPart($this->textBody, $charset, 'plain', $encoding);

        if ($this->attachments === []) {
            $this->builtPart = $textPart;
        } else {
            $dataParts = array_map(
                fn(array $att): DataPart => DataPart::fromPath(
                    $att['filePath'],
                    $att['name'],
                    $att['mimeType'],
                ),
                $this->attachments,
            );
            $this->builtPart = new MixedPart($textPart, ...$dataParts);
        }

        return $this->builtPart->bodyToString();
    }

    public function getHeaders(array $headers): array
    {
        if ($this->builtPart === null) {
            $this->getBody();
        }

        $preparedHeaders = $this->builtPart->getPreparedHeaders();

        $contentType = $preparedHeaders->get('Content-Type')?->getBodyAsString() ?? 'text/plain';
        $headers['Content-Type'] = $contentType;

        if ($this->builtPart instanceof TextPart && !$this->builtPart instanceof DataPart) {
            $cte = $preparedHeaders->get('Content-Transfer-Encoding')?->getBodyAsString();
            if ($cte !== null) {
                $headers['Content-Transfer-Encoding'] = $cte;
            }
        }

        $headers['MIME-Version'] = '1.0';

        return $this->encodeHeaders($headers);
    }

    private function encodeHeaders(array $headers): array
    {
        $encoded = [];
        foreach ($headers as $name => $value) {
            $encoded[$name] = is_array($value)
                ? array_map($this->encodeHeaderValue(...), $value)
                : $this->encodeHeaderValue($value);
        }
        return $encoded;
    }

    private function encodeHeaderValue(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n", "\0"], '', $value);

        if (!preg_match('/[^\x20-\x7E]/', $value)) {
            return $value;
        }

        return mb_encode_mimeheader($value, 'UTF-8', 'Q');
    }
}
