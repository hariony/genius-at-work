<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Infrastructure\Security\TurnstileVerifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class TurnstileVerifierTest extends TestCase
{
    public function testWithoutSecretKeyTurnstileIsDisabled(): void
    {
        $http = new MockHttpClient(static fn () => throw new \LogicException('Cloudflare ne doit pas être appelé.'));

        self::assertTrue(new TurnstileVerifier($http, new NullLogger(), '')->verify('disabled', '203.0.113.1'));
    }

    public function testWithSecretKeyTheTokenIsChecked(): void
    {
        $http = new MockHttpClient([new MockResponse('{"success":false,"error-codes":["invalid-input-response"]}'), new MockResponse('{"success":true}')]);
        $verifier = new TurnstileVerifier($http, new NullLogger(), 'secret');

        self::assertFalse($verifier->verify('disabled', '203.0.113.1'));
        self::assertTrue($verifier->verify('valid', '203.0.113.1'));
    }
}
