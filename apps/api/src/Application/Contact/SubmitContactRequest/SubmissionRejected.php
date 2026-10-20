<?php

declare(strict_types=1);

namespace App\Application\Contact\SubmitContactRequest;

final class SubmissionRejected extends \RuntimeException
{
    public const string RATE_LIMITED = 'rate_limited';
    public const string BOT_CHECK_FAILED = 'bot_check_failed';

    private function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }

    public static function rateLimited(): self
    {
        return new self(self::RATE_LIMITED);
    }

    public static function botCheckFailed(): self
    {
        return new self(self::BOT_CHECK_FAILED);
    }
}
