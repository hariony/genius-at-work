<?php

declare(strict_types=1);

namespace App\UserInterface\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Domain\Lead\BudgetRange;
use App\Domain\Lead\Locale;
use App\Domain\Lead\ProjectType;
use App\Domain\Lead\Timeline;
use App\UserInterface\Api\State\ContactRequestProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /api/v1/contact-requests
 * Mêmes règles que contactRequestSchema (packages/types/src/contact.ts). Les messages de
 * violation sont des codes stables, traduits par le site.
 */
#[ApiResource(
    shortName: 'ContactRequest',
    operations: [
        new Post(
            uriTemplate: '/v1/contact-requests',
            status: 201,
            output: ContactRequestCreated::class,
            processor: ContactRequestProcessor::class,
            description: 'Envoie une demande de contact depuis le site.',
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final class ContactRequestInput
{
    #[Assert\NotBlank(message: 'required')]
    #[Assert\Choice(callback: [self::class, 'projectTypes'], message: 'invalid_choice')]
    public ?string $projectType = null;

    #[Assert\Choice(callback: [self::class, 'budgetRanges'], message: 'invalid_choice')]
    public ?string $budgetRange = null;

    #[Assert\Choice(callback: [self::class, 'timelines'], message: 'invalid_choice')]
    public ?string $timeline = null;

    #[Assert\NotBlank(message: 'required', normalizer: 'trim')]
    #[Assert\Length(min: 20, max: 2000, minMessage: 'too_short', maxMessage: 'too_long', normalizer: 'trim')]
    public ?string $message = null;

    #[Assert\NotBlank(message: 'required', normalizer: 'trim')]
    #[Assert\Length(min: 2, max: 120, minMessage: 'too_short', maxMessage: 'too_long', normalizer: 'trim')]
    public ?string $fullName = null;

    #[Assert\NotBlank(message: 'required', normalizer: 'trim')]
    #[Assert\Email(message: 'invalid_email', mode: Assert\Email::VALIDATION_MODE_HTML5)]
    #[Assert\Length(max: 180, maxMessage: 'too_long')]
    public ?string $email = null;

    #[Assert\Length(max: 30, maxMessage: 'too_long')]
    #[Assert\Regex(pattern: '/^\+?[0-9 ().-]{6,}$/', message: 'invalid_phone')]
    public ?string $phone = null;

    #[Assert\Length(max: 120, maxMessage: 'too_long')]
    public ?string $company = null;

    #[Assert\IsTrue(message: 'consent_required')]
    public ?bool $consent = null;

    #[Assert\NotBlank(message: 'required')]
    #[Assert\Choice(callback: [self::class, 'locales'], message: 'invalid_choice')]
    public ?string $locale = null;

    #[Assert\Length(max: 500, maxMessage: 'too_long')]
    public ?string $sourceUrl = null;

    /** Champ piège : doit rester vide (contrôlé par le SpamGuard, sans message d'erreur). */
    public ?string $website = null;

    #[Assert\NotBlank(message: 'required')]
    #[Assert\Positive(message: 'required')]
    public ?int $startedAt = null;

    #[Assert\NotBlank(message: 'required')]
    #[Assert\Length(max: 2048, maxMessage: 'too_long')]
    public ?string $turnstileToken = null;

    /** @return list<string> */
    public static function projectTypes(): array
    {
        return array_column(ProjectType::cases(), 'value');
    }

    /** @return list<string> */
    public static function budgetRanges(): array
    {
        return array_column(BudgetRange::cases(), 'value');
    }

    /** @return list<string> */
    public static function timelines(): array
    {
        return array_column(Timeline::cases(), 'value');
    }

    /** @return list<string> */
    public static function locales(): array
    {
        return array_column(Locale::cases(), 'value');
    }
}
