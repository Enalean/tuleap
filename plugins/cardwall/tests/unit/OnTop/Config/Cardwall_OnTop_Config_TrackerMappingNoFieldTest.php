<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config;

use Cardwall_OnTop_Config_TrackerMappingNoField;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class Cardwall_OnTop_Config_TrackerMappingNoFieldTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testItHasAnEmptyValueMappings(): void
    {
        $tracker          = TrackerTestBuilder::aTracker()->build();
        $available_fields = [];
        $mapping          = new Cardwall_OnTop_Config_TrackerMappingNoField($tracker, $available_fields);
        self::assertEquals([], $mapping->getValueMappings());
    }

    public function testItsFieldIsNull(): void
    {
        $tracker          = TrackerTestBuilder::aTracker()->build();
        $available_fields = [];
        $mapping          = new Cardwall_OnTop_Config_TrackerMappingNoField($tracker, $available_fields);
        self::assertNull($mapping->getField());
    }
}
