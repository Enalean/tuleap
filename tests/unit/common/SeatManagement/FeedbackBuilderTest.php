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
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Ramsey\Uuid\Uuid;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\SeatManagement\BuildLicenseStub;

#[DisableReturnValueGenerationForTestDoubles]
final class FeedbackBuilderTest extends TestCase
{
    public function testItShouldNotAddFeedbackForRegularUser(): void
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
            UserTestBuilder::anActiveUser()->build(),
        )->build($feedback);

        self::assertEmpty($feedback->logs);
    }

    public function testItShouldNotAddFeedbackForSiteAdminWhenNoExpirationDate(): void
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
        )->build($feedback);

        self::assertEmpty($feedback->logs);
    }

    public function testItShouldAddFeedbackForSiteAdminWhenOneMonthBeforeExpirationDate(): void
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
                new DateTimeImmutable('+2 days')->modify('+1 hour'),
                null,
            ))),
            UserTestBuilder::anActiveUser()->withSiteAdministrator()->build(),
        )->build($feedback);

        self::assertSame([[
            'level' => Feedback::WARN,
            'msg' => 'Your Tuleap subscription will expire in 2 days. Please renew to avoid any disruption. Please get in touch with your usual company contact or send an email to sales@enalean.com.',
            'purify' => CODENDI_PURIFIER_CONVERT_HTML,
        ],
        ], $feedback->logs);
    }
}
