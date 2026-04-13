<?php

declare(strict_types=1);

namespace TicketHub\Mail;

interface MimeBuilderInterface
{
    public function setTextBody(string $body): void;

    /**
     * @throws \InvalidArgumentException
     */
    public function addAttachment(string $filePath, string $mimeType, string $name): void;

    public function getBody(array $options = []): string;

    public function getHeaders(array $headers): array;
}
