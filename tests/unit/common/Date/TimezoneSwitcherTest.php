<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Date;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TimezoneSwitcherTest extends TestCase
{
    private string $default_timezone;

    protected function setUp(): void
    {
        $this->default_timezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->default_timezone);
    }

    public function testSetTimezoneForSpecificUserExecutionContext(): void
    {
        date_default_timezone_set('Europe/Paris');

        $user = UserTestBuilder::aUser()->withTimezone('Asia/Tokyo')->build();

        $locale_timezone = '';

        (new TimezoneSwitcher())->setTimezoneForSpecificUserExecutionContext(
            $user,
            static function () use (&$locale_timezone) {
                $locale_timezone = date_default_timezone_get();
            }
        );

        self::assertEquals('Europe/Paris', date_default_timezone_get());
        self::assertEquals('Asia/Tokyo', $locale_timezone);
    }

    public function testSetTimezoneForAnonymousUser(): void
    {
        date_default_timezone_set('Europe/Paris');

        $user = UserTestBuilder::anAnonymousUser()->build();

        $locale_timezone = '';

        (new TimezoneSwitcher())->setTimezoneForSpecificUserExecutionContext(
            $user,
            static function () use (&$locale_timezone) {
                $locale_timezone = date_default_timezone_get();
            }
        );

        self::assertEquals('Europe/Paris', date_default_timezone_get());
        self::assertEquals('Europe/Paris', $locale_timezone);
    }
}
