<?php

declare(strict_types=1);

namespace TicketHub\Mail;

use Symfony\Component\Mime\Address;

final class SymfonyMimeParser implements MimeParserInterface
{
    private const MAX_MIME_DEPTH = 20;

    private bool $includeBodies = true;
    private bool $decodeHeaders = true;
    private bool $decodeBodies = true;

    public function decode(
        string $rawMessage,
        bool $includeBodies = true,
        bool $decodeHeaders = true,
        bool $decodeBodies = true,
    ): \stdClass|false {
        $this->includeBodies = $includeBodies;
        $this->decodeHeaders = $decodeHeaders;
        $this->decodeBodies = $decodeBodies;

        $rawMessage = str_replace("\r\n", "\n", $rawMessage);
        $rawMessage = str_replace("\r", "\n", $rawMessage);
        $rawMessage = str_replace("\n", "\r\n", $rawMessage);

        [$headerBlock, $body] = $this->splitBodyHeader($rawMessage);

        if ($headerBlock === '') {
            return false;
        }

        $parsedHeaders = $this->parseHeaders($headerBlock);

        $result = $this->decodeStructure($parsedHeaders, $body, 'text/plain', 0);

        return (count($result->headers) > 1) ? $result : false;
    }

    public function parseAddressList(string $addressList): array
    {
        $addressList = trim($addressList);
        if ($addressList === '') {
            throw new MailParseException('Empty address list');
        }

        $results = [];
        $addresses = $this->splitAddresses($addressList);

        foreach ($addresses as $raw) {
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }

            $entry = new \stdClass();
            $entry->mailbox = '';
            $entry->host = '';
            $entry->personal = '';
            $entry->comment = [];

            try {
                $addr = Address::create($raw);
                $email = $addr->getAddress();
                $parts = explode('@', $email, 2);
                $entry->mailbox = $parts[0];
                $entry->host = $parts[1] ?? '';
                $entry->personal = $addr->getName();
            } catch (\Throwable) {
                $parsed = $this->parseAddressManually($raw);
                $entry->mailbox = $parsed['mailbox'];
                $entry->host = $parsed['host'];
                $entry->personal = $parsed['personal'];
                $entry->comment = $parsed['comment'] !== '' ? [$parsed['comment']] : [];
            }

            $results[] = $entry;
        }

        if ($results === []) {
            throw new MailParseException('Could not parse any address from the provided list.');
        }

        return $results;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitBodyHeader(string $input): array
    {
        $pos = strpos($input, "\r\n\r\n");
        if ($pos === false) {
            return [$input, ''];
        }

        return [
            substr($input, 0, $pos),
            substr($input, $pos + 4),
        ];
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    private function parseHeaders(string $headerBlock): array
    {
        $headerBlock = preg_replace("/\r\n([\t ]+)/", ' $1', $headerBlock);
        $lines = explode("\r\n", $headerBlock);
        $headers = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue;
            }
            $name = substr($line, 0, $colonPos);
            $value = ltrim(substr($line, $colonPos + 1));

            if ($this->decodeHeaders) {
                $value = $this->decodeHeaderValue($value);
            }

            $headers[] = ['name' => $name, 'value' => $value];
        }

        return $headers;
    }

    /**
     * @return array{value: string, other: array<string, string>}
     */
    private function parseHeaderValue(string $value): array
    {
        $parts = preg_split('/\s*;\s*/', $value);
        $mainValue = array_shift($parts) ?? '';
        $params = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $eqPos = strpos($part, '=');
            if ($eqPos === false) {
                continue;
            }
            $paramName = strtolower(trim(substr($part, 0, $eqPos)));
            $paramValue = trim(substr($part, $eqPos + 1));
            $paramValue = trim($paramValue, '"');
            $params[$paramName] = $paramValue;
        }

        return ['value' => $mainValue, 'other' => $params];
    }

    /**
     * @param list<array{name: string, value: string}> $parsedHeaders
     */
    private function decodeStructure(
        array $parsedHeaders,
        string $body,
        string $defaultContentType,
        int $depth,
    ): \stdClass {
        if ($depth > self::MAX_MIME_DEPTH) {
            $struct = new \stdClass();
            $struct->headers = [];
            $struct->ctype_primary = 'text';
            $struct->ctype_secondary = 'plain';
            $struct->body = '';
            $struct->parts = [];
            return $struct;
        }

        $struct = new \stdClass();
        $struct->headers = [];
        $struct->ctype_primary = '';
        $struct->ctype_secondary = '';
        $struct->body = null;
        $struct->parts = [];

        $contentType = $defaultContentType;
        $ctypeParams = [];
        $encoding = '7bit';
        $disposition = null;
        $dParams = [];

        foreach ($parsedHeaders as $header) {
            $lowerName = strtolower($header['name']);
            $headerValue = $header['value'];

            if (isset($struct->headers[$lowerName])) {
                if (is_array($struct->headers[$lowerName])) {
                    $struct->headers[$lowerName][] = $headerValue;
                } else {
                    $struct->headers[$lowerName] = [
                        $struct->headers[$lowerName],
                        $headerValue,
                    ];
                }
            } else {
                $struct->headers[$lowerName] = $headerValue;
            }

            match ($lowerName) {
                'content-type' => (function () use ($headerValue, &$contentType, &$ctypeParams): void {
                    $parsed = $this->parseHeaderValue($headerValue);
                    $contentType = $parsed['value'];
                    $ctypeParams = $parsed['other'];
                })(),
                'content-transfer-encoding' => $encoding = strtolower(trim($headerValue)),
                'content-disposition' => (function () use ($headerValue, &$disposition, &$dParams): void {
                    $parsed = $this->parseHeaderValue($headerValue);
                    $disposition = $parsed['value'];
                    $dParams = $parsed['other'];
                })(),
                default => null,
            };
        }

        $ctypeParts = explode('/', strtolower($contentType), 2);
        $struct->ctype_primary = $ctypeParts[0];
        $struct->ctype_secondary = $ctypeParts[1] ?? 'plain';

        if ($ctypeParams !== []) {
            $struct->ctype_parameters = $ctypeParams;
        }

        if ($disposition !== null) {
            $struct->disposition = $disposition;
        }

        if ($dParams !== []) {
            $struct->d_parameters = $dParams;
        }

        if ($struct->ctype_primary === 'multipart' && isset($ctypeParams['boundary'])) {
            $parts = $this->boundarySplit($body, $ctypeParams['boundary']);
            $childDefault = match ($struct->ctype_secondary) {
                'digest' => 'message/rfc822',
                default  => 'text/plain',
            };

            foreach ($parts as $partRaw) {
                [$partHeader, $partBody] = $this->splitBodyHeader($partRaw);
                $partHeaders = $this->parseHeaders($partHeader);
                $struct->parts[] = $this->decodeStructure(
                    $partHeaders,
                    $partBody,
                    $childDefault,
                    $depth + 1,
                );
            }
        } elseif ($struct->ctype_primary === 'message' && $struct->ctype_secondary === 'rfc822') {
            [$subHeader, $subBody] = $this->splitBodyHeader($body);
            $subHeaders = $this->parseHeaders($subHeader);
            $struct->parts[] = $this->decodeStructure(
                $subHeaders,
                $subBody,
                'text/plain',
                $depth + 1,
            );
        } else {
            if ($this->includeBodies) {
                $struct->body = $this->decodeBodies
                    ? $this->decodeBody($body, $encoding)
                    : $body;
            }
        }

        return $struct;
    }

    /**
     * @return list<string>
     */
    private function boundarySplit(string $body, string $boundary): array
    {
        $parts = [];
        $boundaryLine = '--' . $boundary;

        $segments = explode($boundaryLine, $body);

        // First segment is the preamble (before the first boundary), skip it.
        // Last segment ending with "--" is the epilogue, skip it.
        array_shift($segments);

        foreach ($segments as $segment) {
            if (str_starts_with(trim($segment), '--')) {
                continue;
            }

            // Strip leading CRLF after boundary line
            if (str_starts_with($segment, "\r\n")) {
                $segment = substr($segment, 2);
            } elseif (str_starts_with($segment, "\n")) {
                $segment = substr($segment, 1);
            }

            // Strip trailing CRLF before next boundary
            if (str_ends_with($segment, "\r\n")) {
                $segment = substr($segment, 0, -2);
            } elseif (str_ends_with($segment, "\n")) {
                $segment = substr($segment, 0, -1);
            }

            $parts[] = $segment;
        }

        return $parts;
    }

    private function decodeBody(string $body, string $encoding): string
    {
        return match ($encoding) {
            'base64'           => base64_decode(str_replace(["\r", "\n"], '', $body), strict: false) ?: '',
            'quoted-printable' => quoted_printable_decode($body),
            default            => $body,
        };
    }

    private function decodeHeaderValue(string $value): string
    {
        $decoded = mb_decode_mimeheader($value);

        // mb_decode_mimeheader may not handle all cases; fall back to iconv_mime_decode
        if ($decoded === $value && preg_match('/=\?[^?]+\?[BQbq]\?[^?]*\?=/', $value)) {
            $result = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($result !== false) {
                return $result;
            }
        }

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private function splitAddresses(string $addressList): array
    {
        $addresses = [];
        $current = '';
        $depth = 0;
        $inQuote = false;
        $len = strlen($addressList);

        for ($i = 0; $i < $len; $i++) {
            $char = $addressList[$i];

            if ($char === '"' && ($i === 0 || $addressList[$i - 1] !== '\\')) {
                $inQuote = !$inQuote;
                $current .= $char;
                continue;
            }

            if ($inQuote) {
                $current .= $char;
                continue;
            }

            match ($char) {
                '(', '<' => (function () use (&$depth, &$current, $char): void {
                    $depth++;
                    $current .= $char;
                })(),
                ')', '>' => (function () use (&$depth, &$current, $char): void {
                    $depth = max(0, $depth - 1);
                    $current .= $char;
                })(),
                ',' => (function () use (&$addresses, &$current, $depth): void {
                    if ($depth === 0) {
                        $addresses[] = $current;
                        $current = '';
                    } else {
                        $current .= ',';
                    }
                })(),
                default => $current .= $char,
            };
        }

        if (trim($current) !== '') {
            $addresses[] = $current;
        }

        return $addresses;
    }

    /**
     * @return array{mailbox: string, host: string, personal: string, comment: string}
     */
    private function parseAddressManually(string $raw): array
    {
        $personal = '';
        $comment = '';
        $email = '';

        // Extract comment (text in parentheses)
        if (preg_match('/\(([^)]*)\)/', $raw, $commentMatch)) {
            $comment = $commentMatch[1];
            $raw = str_replace($commentMatch[0], '', $raw);
        }

        // Extract angle-bracket address
        if (preg_match('/<([^>]*)>/', $raw, $angleMatch)) {
            $email = trim($angleMatch[1]);
            $personal = trim(str_replace($angleMatch[0], '', $raw));
            $personal = trim($personal, " \t\n\r\0\x0B\"'");
        } else {
            $email = trim($raw);
            $email = trim($email, " \t\n\r\0\x0B\"'");
        }

        $parts = explode('@', $email, 2);
        $mailbox = $parts[0];
        $host = $parts[1] ?? '';

        return [
            'mailbox'  => $mailbox,
            'host'     => $host,
            'personal' => $personal,
            'comment'  => $comment,
        ];
    }
}
