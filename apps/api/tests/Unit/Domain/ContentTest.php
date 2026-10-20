<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Content\LocalizedText;
use App\Domain\Content\Office;
use App\Domain\Content\Phone;
use App\Domain\Content\SiteSettings;
use App\Domain\Content\SocialLink;
use PHPUnit\Framework\TestCase;

final class ContentTest extends TestCase
{
    public function testPhoneKeepsTheDisplayAndDerivesTheDialableNumber(): void
    {
        $phone = new Phone('+261 34 85 311 20');

        self::assertSame('+261 34 85 311 20', $phone->display);
        self::assertSame('+261348531120', $phone->e164);
    }

    public function testPhoneMustBeInternational(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Phone('034 85 311 20');
    }

    public function testUtcLabelFollowsTheTimeZone(): void
    {
        $at = new \DateTimeImmutable('2026-01-15 12:00:00 UTC');

        self::assertSame('UTC+3', self::office('Indian/Antananarivo')->utcLabel($at));
        self::assertSame('UTC+5:30', self::office('Asia/Kolkata')->utcLabel($at));
        self::assertSame('UTC', self::office('Europe/London')->utcLabel($at));
        self::assertSame('UTC+2', self::office('Europe/Paris')->utcLabel(new \DateTimeImmutable('2026-07-15 12:00:00 UTC')));
    }

    public function testSocialLinkIdIsDerivedFromTheLabel(): void
    {
        self::assertSame('linkedin', (new SocialLink('LinkedIn', 'https://www.linkedin.com/'))->id());
    }

    public function testSettingsNeedAtLeastOneOffice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SiteSettings('Genius At Work', 'contact@example.com', [], []);
    }

    public function testBothLanguagesAreRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LocalizedText('Bureau', ' ');
    }

    private static function office(string $timeZone): Office
    {
        return new Office('bureau', new LocalizedText('Bureau', 'Office'), 'Ville', ['Rue'], [], $timeZone, 0.0, 0.0);
    }
}
