<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use ForgeConfig;
use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Date\TimezoneWrapper;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class DateFormatterTest extends TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private DateField&MockObject $field;
    private DateFormatter $date_formatter;

    #[Override]
    protected function setUp(): void
    {
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');

        $this->field          = $this->createMock(DateField::class);
        $user                 = UserTestBuilder::anActiveUser()->withTimezone('Europe/Paris')->build();
        $this->date_formatter = new DateFormatter($this->field, ProvideCurrentUserStub::buildWithUser($user));

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->with(105)->willReturn($user);
        UserManager::setInstance($user_manager);
    }

    #[Override]
    protected function tearDown(): void
    {
        UserManager::clearInstance();
    }

    public function testItFormatsTimestampInRightFormat(): void
    {
        $timestamp = 1409752174;
        $expected  = '2014-09-03';

        self::assertEquals($expected, $this->date_formatter->formatDate($timestamp, null));
    }

    public function testItValidatesWellFormedValue(): void
    {
        $value = '2014-09-03';

        self::assertTrue($this->date_formatter->validate($value));
    }

    public function testItDoesNotValidateNotWellFormedValue(): void
    {
        $value = '2014/09/03';
        $this->field->expects($this->once())->method('getLabel');

        self::assertFalse($this->date_formatter->validate($value));
    }

    #[TestWith([0, '2025-09-05'])] // Date displayed with user timezone (America/Los_Angeles)
    #[TestWith([1, '2025-09-06'])] // Date displayed with submitter timezone (Europe/Paris)
    public function testItUsesSubmitterTimezone(int $with_submitter_timezone, string $expected): void
    {
        ForgeConfig::setFeatureFlag(DateFormatter::DISPLAY_DATE_WITH_SUBMITTER_TIMEZONE, $with_submitter_timezone);
        $changeset = ChangesetTestBuilder::aChangeset(12)->submittedBy(105)->build();
        $value     = ChangesetValueDateTestBuilder::aValue(1, $changeset, $this->field)
            ->withTimestamp((int) strtotime('2025-09-06 00:00 Europe/Paris'))
            ->build();
        $artifact  = ArtifactTestBuilder::anArtifact(652)->build();
        $GLOBALS['Language']->method('getText')->with('system', 'datefmt_short')->willReturn('Y-m-d');
        TimezoneWrapper::wrapTimezone('America/Los_Angeles', function () use ($artifact, $value, $expected): void {
            self::assertSame($expected, $this->date_formatter->fetchArtifactValueReadOnly($artifact, $value));
        });
    }
}
