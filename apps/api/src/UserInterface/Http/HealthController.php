<?php

declare(strict_types=1);

namespace App\UserInterface\Http;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sonde de santé lue par Docker et par le déploiement (rollback si elle échoue).
 * Ne révèle aucun détail interne : seulement « ok » ou « unavailable ».
 */
#[AsController]
final readonly class HealthController
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/api/v1/health', name: 'health', methods: ['GET', 'HEAD'])]
    public function __invoke(): JsonResponse
    {
        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (\Throwable $exception) {
            $this->logger->error('Health check: database unreachable.', ['exception' => $exception]);

            return $this->respond('unavailable', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->respond('ok', Response::HTTP_OK);
    }

    private function respond(string $status, int $code): JsonResponse
    {
        $response = new JsonResponse(['status' => $status], $code);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
