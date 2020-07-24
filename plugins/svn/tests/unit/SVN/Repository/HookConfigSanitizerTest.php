<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use PHPUnit\Framework\TestCase;

class HookConfigSanitizerTest extends TestCase
{
    public function testItFilterImproperValuesForHookConfig(): void
    {
        $hook_config = [
            'an_incorrect_key'              => 'value',
            HookConfig::MANDATORY_REFERENCE => true
        ];

        $hook_config_sanitizer = new HookConfigSanitizer();
        $this->assertEquals(
            [HookConfig::MANDATORY_REFERENCE => true],
            $hook_config_sanitizer->sanitizeHookConfigArray($hook_config)
        );
    }

    public function testItReturnsACorrectHookConfiguration(): void
    {
        $hook_config = [
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
            HookConfig::MANDATORY_REFERENCE       => false
        ];

        $hook_config_sanitizer = new HookConfigSanitizer();
        $this->assertEquals(
            [
                HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
                HookConfig::MANDATORY_REFERENCE       => false
            ],
            $hook_config_sanitizer->sanitizeHookConfigArray($hook_config)
        );
    }
}
