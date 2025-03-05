<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\XML;

use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ExtractXMLConfigStub;
use Tuleap\ProgramManagement\Tests\Stub\ParseXMLConfigStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramManagementXMLConfigTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFromXML(): void
    {
        $config = ProgramManagementXMLConfig::fromXML(
            ParseXMLConfigStub::buildWithConfigFile(),
            ExtractXMLConfigStub::buildWithConfigToImport(
                10,
                [12, 13],
                ['101_3'],
                "Crémants d'Alsace",
                'Crémant',
                null,
                null,
                null
            ),
            ProgramForAdministrationIdentifierBuilder::build(),
            'path/to/xml',
            []
        );

        self::assertEquals(10, $config->increments_source_tracker_id);
        self::assertEquals([12, 13], $config->increments_plannable_trackers_ids);
        self::assertEquals(['101_3'], $config->ugroups_that_can_prioritize_increments);
        self::assertEquals("Crémants d'Alsace", $config->program_increments_section_name);
        self::assertEquals('Crémant', $config->program_increments_milestones_name);
        self::assertNull($config->iterations_source_tracker_id);
        self::assertNull($config->iterations_section_name);
        self::assertNull($config->iterations_milestones_name);
    }

    public function testFromXMLWithIterationsConfig(): void
    {
        $config = ProgramManagementXMLConfig::fromXML(
            ParseXMLConfigStub::buildWithConfigFile(),
            ExtractXMLConfigStub::buildWithConfigToImport(
                10,
                [12, 13],
                ['101_3'],
                "Crémants d'Alsace",
                'Crémant',
                14,
                'Rations de survie',
                'ration'
            ),
            ProgramForAdministrationIdentifierBuilder::build(),
            'path/to/xml',
            []
        );

        self::assertEquals(10, $config->increments_source_tracker_id);
        self::assertEquals([12, 13], $config->increments_plannable_trackers_ids);
        self::assertEquals(['101_3'], $config->ugroups_that_can_prioritize_increments);
        self::assertEquals("Crémants d'Alsace", $config->program_increments_section_name);
        self::assertEquals('Crémant', $config->program_increments_milestones_name);
        self::assertEquals(14, $config->iterations_source_tracker_id);
        self::assertEquals('Rations de survie', $config->iterations_section_name);
        self::assertEquals('ration', $config->iterations_milestones_name);
    }
}
