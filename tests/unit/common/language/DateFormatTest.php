<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\language;

use DateTimeImmutable;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class DateFormatTest extends TestCase
{
    public function testGetYearFullMonthAndDayFormatter(): void
    {
        $dylan = UserTestBuilder::aUser()->withLocale('en_US')->build();
        $jean  = UserTestBuilder::aUser()->withLocale('fr_FR')->build();

        $date = (new DateTimeImmutable())->setTimestamp(1234567890);

        self::assertEquals('February 14, 2009', DateFormat::getYearFullMonthAndDayFormatter($dylan)->format($date));
        self::assertEquals('14 février 2009', DateFormat::getYearFullMonthAndDayFormatter($jean)->format($date));
    }

    public function testGetYearFullMonthAndDayFormatterFormatsCorrectlyDateForLastDayOfYear(): void
    {
        $dylan = UserTestBuilder::aUser()->withLocale('en_US')->build();
        $jean  = UserTestBuilder::aUser()->withLocale('fr_FR')->build();

        $date = (new DateTimeImmutable())->setTimestamp(1767135600);

        self::assertEquals('December 31, 2025', DateFormat::getYearFullMonthAndDayFormatter($dylan)->format($date));
        self::assertEquals('31 décembre 2025', DateFormat::getYearFullMonthAndDayFormatter($jean)->format($date));
    }
}
