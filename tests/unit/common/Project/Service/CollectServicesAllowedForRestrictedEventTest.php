<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use Tuleap\Test\PHPUnit\TestCase;

final class CollectServicesAllowedForRestrictedEventTest extends TestCase
{
    private const ALLOWED_SERVICE_SHORTNAME = 'git';

    public function testItReturnsTrueWhenServiceShortnameHasBeenAdded(): void
    {
        $event = new CollectServicesAllowedForRestrictedEvent();
        $event->addServiceShortname(self::ALLOWED_SERVICE_SHORTNAME);
        self::assertTrue($event->isServiceShortnameAllowed(self::ALLOWED_SERVICE_SHORTNAME));
    }

    public function testItReturnsFalseWhenServiceShortnameWasNeverAdded(): void
    {
        $event = new CollectServicesAllowedForRestrictedEvent();
        $event->addServiceShortname('custom');
        self::assertFalse($event->isServiceShortnameAllowed(self::ALLOWED_SERVICE_SHORTNAME));
    }

    public function testItReturnsFalseWhenEmpty(): void
    {
        $event = new CollectServicesAllowedForRestrictedEvent();
        self::assertFalse($event->isServiceShortnameAllowed(self::ALLOWED_SERVICE_SHORTNAME));
    }
}
