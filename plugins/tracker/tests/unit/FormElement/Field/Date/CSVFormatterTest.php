<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Date;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\StoreUserPreferenceStub;

#[DisableReturnValueGenerationForTestDoubles]
final class CSVFormatterTest extends TestCase
{
    private const TIMESTAMP = 1540456782;

    private CSVFormatter $formatter;
    private PFUser $user;
    private StoreUserPreferenceStub $user_preference_store;

    protected function setUp(): void
    {
        $this->formatter             = new CSVFormatter();
        $this->user_preference_store = new StoreUserPreferenceStub();
        $this->user                  = UserTestBuilder::anActiveUser()->withPreferencesStore($this->user_preference_store)->build();
    }

    public function testFormatDateForCSVWithMonthFirst(): void
    {
        $this->user_preference_store->set($this->user->getId(), 'user_csv_dateformat', 'month_day_year');

        $result = $this->formatter->formatDateForCSVForUser($this->user, self::TIMESTAMP, false);

        self::assertEquals('10/25/2018', $result);
    }

    public function testFormatDateForCSVWithDayFirst(): void
    {
        $this->user_preference_store->set($this->user->getId(), 'user_csv_dateformat', 'day_month_year');

        $result = $this->formatter->formatDateForCSVForUser($this->user, self::TIMESTAMP, false);

        self::assertEquals('25/10/2018', $result);
    }

    public function testFormatDateForCSVWithDayAndTime(): void
    {
        $this->user_preference_store->set($this->user->getId(), 'user_csv_dateformat', 'day_month_year');

        $result = $this->formatter->formatDateForCSVForUser($this->user, self::TIMESTAMP, true);

        self::assertEquals('25/10/2018 10:39', $result);
    }

    public function testFormatDateForCSVWithMonthAndTime(): void
    {
        $this->user_preference_store->set($this->user->getId(), 'user_csv_dateformat', 'month_day_year');

        $result = $this->formatter->formatDateForCSVForUser($this->user, self::TIMESTAMP, true);

        self::assertEquals('10/25/2018 10:39', $result);
    }

    public function testFormatDateForCSVWithDefault(): void
    {
        $result = $this->formatter->formatDateForCSVForUser($this->user, self::TIMESTAMP, false);

        self::assertEquals('10/25/2018', $result);
    }
}
