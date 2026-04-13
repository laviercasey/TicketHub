<?php

declare(strict_types=1);

namespace TicketHub\Mail;

interface MimeParserInterface
{
    /**
     * @return \stdClass|false
     */
    public function decode(
        string $rawMessage,
        bool $includeBodies = true,
        bool $decodeHeaders = true,
        bool $decodeBodies = true,
    ): \stdClass|false;

    /**
     * @return array<\stdClass>
     * @throws MailParseException
     */
    public function parseAddressList(string $addressList): array;
}
