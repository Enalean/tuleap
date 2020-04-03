<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\CLI\Events;

use PHPUnit\Framework\TestCase;

final class GetWhitelistedKeysTest extends TestCase
{
    public function testGetKeysWithAnnotationsOnClasses(): void
    {
        $get_whitelisted_keys = GetWhitelistedKeys::build();

        $all_keys = [];
        foreach ($get_whitelisted_keys->getSortedKeysWithMetadata() as $key => $summary) {
            $all_keys[$key] = $summary;
        }
        $this->assertArrayHasKey('sys_use_project_registration', $all_keys);
        $this->assertSame("Is project creation allowed to regular users (1) or not (0)", $all_keys['sys_use_project_registration']);
    }

    public function testIsKeyAllowed(): void
    {
        $get_whitelisted_keys = GetWhitelistedKeys::build();

        $this->assertTrue($get_whitelisted_keys->isKeyWhiteListed('sys_use_project_registration'));
    }

    public function testGetKeysAsArray(): void
    {
        $get_whitelisted_keys = GetWhitelistedKeys::build();

        $this->assertContains('sys_use_project_registration', $get_whitelisted_keys->getWhiteListedKeys());
    }
}
