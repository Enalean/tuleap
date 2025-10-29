<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\SeatManagement;

use DateTimeImmutable;
use Feedback;
use Lcobucci\Clock\FrozenClock;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Ramsey\Uuid\Uuid;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\SeatManagement\BuildLicenseStub;

#[DisableReturnValueGenerationForTestDoubles]
final class FeedbackBuilderTest extends TestCase
{
    public function testItShouldNotAddFeedbackWhenNoExpirationDate(): void
    {
        $feedback = new Feedback();

        new FeedbackBuilder(
            BuildLicenseStub::buildWithLicense(License::buildEnterpriseEdition(new LicenseContent(
                'enalean-tuleap-enterprise',
                ['abc'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                Uuid::uuid4(),
                [],
                null,
                null,
            ))),
            UserTestBuilder::anActiveUser()->withSiteAdministrator()->build(),
            new FrozenClock(new DateTimeImmutable('now')),
        )->build($feedback);

        self::assertEmpty($feedback->logs);
    }

    public function testOneMonthBeforeExpirationItShouldNotAddFeedbackForRegularUser(): void
    {
        $feedback = new Feedback();

        new FeedbackBuilder(
            BuildLicenseStub::buildWithLicense(License::buildEnterpriseEdition(new LicenseContent(
                'enalean-tuleap-enterprise',
                ['abc'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                Uuid::uuid4(),
                [],
                new DateTimeImmutable('2025-10-22'),
                null,
            ))),
            UserTestBuilder::anActiveUser()->build(),
            new FrozenClock(new DateTimeImmutable('2025-10-20')),
        )->build($feedback);

        self::assertEmpty($feedback->logs);
    }

    public function testOneMonthBeforeItShouldAddFeedbackForSiteAdmin(): void
    {
        $feedback = new Feedback();

        new FeedbackBuilder(
            BuildLicenseStub::buildWithLicense(License::buildEnterpriseEdition(new LicenseContent(
                'enalean-tuleap-enterprise',
                ['abc'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                Uuid::uuid4(),
                [],
                new DateTimeImmutable('2025-10-22'),
                null,
            ))),
            UserTestBuilder::anActiveUser()->withSiteAdministrator()->build(),
            new FrozenClock(new DateTimeImmutable('2025-10-20')),
        )->build($feedback);

        self::assertSame([[
            'level' => Feedback::WARN,
            'msg' => 'Your Tuleap subscription will expire in 2 days. Please renew to avoid any disruption. Please get in touch with your usual company contact or send an email to sales@enalean.com.',
            'purify' => CODENDI_PURIFIER_CONVERT_HTML,
        ],
        ], $feedback->logs);
    }

    public function testOneMonthBeforeItShouldNotAddFeedbackForAnonymous(): void
    {
        $feedback = new Feedback();

        new FeedbackBuilder(
            BuildLicenseStub::buildWithLicense(License::buildEnterpriseEdition(new LicenseContent(
                'enalean-tuleap-enterprise',
                ['abc'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                Uuid::uuid4(),
                [],
                new DateTimeImmutable('2025-10-22'),
                null,
            ))),
            UserTestBuilder::anAnonymousUser()->build(),
            new FrozenClock(new DateTimeImmutable('2025-10-20')),
        )->build($feedback);

        self::assertEmpty($feedback->logs);
    }

    public function testAtExpirationDateItShouldAddFeedbackForRegularUser(): void
    {
        $feedback = new Feedback();

        new FeedbackBuilder(
            BuildLicenseStub::buildWithLicense(License::buildEnterpriseEdition(new LicenseContent(
                'enalean-tuleap-enterprise',
                ['abc'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                Uuid::uuid4(),
                [],
                new DateTimeImmutable('2025-10-18'),
                null,
            ))),
            UserTestBuilder::anActiveUser()->build(),
            new FrozenClock(new DateTimeImmutable('2025-10-20')),
        )->build($feedback);

        self::assertSame([[
            'level' => Feedback::WARN,
            'msg' => 'Your subscription has expired. Please contact your administrator to continue using Tuleap.',
            'purify' => CODENDI_PURIFIER_CONVERT_HTML,
        ],
        ], $feedback->logs);
    }

    public function testAtExpirationDateItShouldAddFeedbackForSiteAdmin(): void
    {
        $feedback = new Feedback();

        new FeedbackBuilder(
            BuildLicenseStub::buildWithLicense(License::buildEnterpriseEdition(new LicenseContent(
                'enalean-tuleap-enterprise',
                ['abc'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                Uuid::uuid4(),
                [],
                new DateTimeImmutable('2025-09-22'),
                null,
            ))),
            UserTestBuilder::anActiveUser()->withSiteAdministrator()->build(),
            new FrozenClock(new DateTimeImmutable('2025-10-20')),
        )->build($feedback);

        self::assertSame([[
            'level' => Feedback::WARN,
            'msg' => 'Your Tuleap subscription has expired. All accounts will be in read only mode in 2 days. Please get in touch with your usual company contact or send an email to sales@enalean.com.',
            'purify' => CODENDI_PURIFIER_CONVERT_HTML,
        ],
        ], $feedback->logs);
    }

    public function testAtExpirationDateItShouldNotAddFeedbackForAnonymous(): void
    {
        $feedback = new Feedback();

        new FeedbackBuilder(
            BuildLicenseStub::buildWithLicense(License::buildEnterpriseEdition(new LicenseContent(
                'enalean-tuleap-enterprise',
                ['abc'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                Uuid::uuid4(),
                [],
                new DateTimeImmutable('2025-09-30'),
                null,
            ))),
            UserTestBuilder::anAnonymousUser()->build(),
            new FrozenClock(new DateTimeImmutable('2025-10-20')),
        )->build($feedback);

        self::assertEmpty($feedback->logs);
    }
}
