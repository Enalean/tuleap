<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Tests\Stub\EndDateFieldReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveEndDateValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStartDateValueStub;
use Tuleap\ProgramManagement\Tests\Stub\StartDateFieldReferenceStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DateValueFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TIMESTAMP = 1436068265;
    private \Rule_Date $date_validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->date_validator = new \Rule_Date();
    }

    private function getFormatter(): DateValueFormatter
    {
        return new DateValueFormatter();
    }

    public function testItFormatsStartDateTimestampToStringExpectedByTrackerPluginAPI(): void
    {
        $start_date = StartDateValue::fromStartDateReference(
            RetrieveStartDateValueStub::withValue(self::TIMESTAMP),
            StartDateFieldReferenceStub::withDefaults()
        );
        self::assertTrue($this->date_validator->isValid($this->getFormatter()->formatForTrackerPlugin($start_date)));
    }

    public function testItFormatsEndDateTimestampToStringExpectedByTrackerPluginAPI(): void
    {
        $end_date = EndDateValue::fromEndDateReference(
            RetrieveEndDateValueStub::withValue(self::TIMESTAMP),
            EndDateFieldReferenceStub::withDefaults()
        );
        self::assertTrue($this->date_validator->isValid($this->getFormatter()->formatForTrackerPlugin($end_date)));
    }
}
