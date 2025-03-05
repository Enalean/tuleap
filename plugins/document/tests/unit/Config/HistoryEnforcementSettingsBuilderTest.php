<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Document\Config;

use Tuleap\ForgeConfigSandbox;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HistoryEnforcementSettingsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItProvidesDefaultWhenNotSet(): void
    {
        $settings = (new HistoryEnforcementSettingsBuilder())->build();

        self::assertEquals(false, $settings->isChangelogProposedAfterDragAndDrop());
    }

    public function testItProvidesOptionState(): void
    {
        \ForgeConfig::set(HistoryEnforcementSettings::IS_CHANGELOG_PROPOSED_AFTER_DND, 1);

        $settings = (new HistoryEnforcementSettingsBuilder())->build();

        self::assertEquals(true, $settings->isChangelogProposedAfterDragAndDrop());
    }
}
