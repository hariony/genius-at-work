<?php

declare(strict_types=1);

namespace App\UserInterface\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Application\Contact\SubmitContactRequest\SubmissionRejected;
use App\Application\Contact\SubmitContactRequest\SubmitContactRequestCommand;
use App\Application\Contact\SubmitContactRequest\SubmitContactRequestHandler;
use App\Domain\Lead\BudgetRange;
use App\Domain\Lead\Locale;
use App\Domain\Lead\ProjectType;
use App\Domain\Lead\Timeline;
use App\UserInterface\Api\Resource\ContactRequestCreated;
use App\UserInterface\Api\Resource\ContactRequestInput;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * DTO validé → commande → handler. Aucune logique métier ici.
 *
 * @implements ProcessorInterface<ContactRequestInput, ContactRequestCreated>
 */
final readonly class ContactRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private SubmitContactRequestHandler $handler,
        private RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ContactRequestCreated
    {
        $optional = static fn (?string $value): ?string => null === $value || '' === trim($value) ? null : trim($value);

        $command = new SubmitContactRequestCommand(
            projectType: ProjectType::from((string) $data->projectType),
            message: trim((string) $data->message),
            budgetRange: BudgetRange::tryFrom((string) $data->budgetRange),
            timeline: Timeline::tryFrom((string) $data->timeline),
            fullName: trim((string) $data->fullName),
            email: trim((string) $data->email),
            phone: $optional($data->phone),
            company: $optional($data->company),
            locale: Locale::from((string) $data->locale),
            sourceUrl: $optional($data->sourceUrl),
            honeypot: (string) $data->website,
            startedAt: (int) $data->startedAt,
            turnstileToken: (string) $data->turnstileToken,
            clientIp: $this->requestStack->getCurrentRequest()?->getClientIp(),
        );

        try {
            $result = ($this->handler)($command);
        } catch (SubmissionRejected $rejection) {
            if (SubmissionRejected::RATE_LIMITED === $rejection->reason) {
                throw new TooManyRequestsHttpException(3600, 'rate_limited');
            }

            throw new ValidationException(new ConstraintViolationList([new ConstraintViolation($rejection->reason, null, [], $data, 'turnstileToken', $data->turnstileToken)]));
        }

        return new ContactRequestCreated($result->reference);
    }
}
