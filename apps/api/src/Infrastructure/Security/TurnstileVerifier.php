<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Contact\Port\BotVerifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Vérifie le jeton Turnstile auprès de Cloudflare (https://developers.cloudflare.com/turnstile/). */
final readonly class TurnstileVerifier implements BotVerifier
{
    private const string ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire('%env(TURNSTILE_SECRET_KEY)%')]
        private string $secretKey,
    ) {
    }

    public function verify(string $token, ?string $clientIp): bool
    {
        try {
            $response = $this->httpClient->request('POST', self::ENDPOINT, [
                'body' => array_filter([
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $clientIp,
                ]),
                'timeout' => 5,
            ]);
            /** @var array{success?: bool, 'error-codes'?: list<string>} $result */
            $result = $response->toArray(false);
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Vérification Turnstile impossible.', ['exception' => $exception::class]);

            return false;
        }

        if (true !== ($result['success'] ?? false)) {
            $this->logger->notice('Jeton Turnstile refusé.', ['codes' => $result['error-codes'] ?? []]);

            return false;
        }

        return true;
    }
}
