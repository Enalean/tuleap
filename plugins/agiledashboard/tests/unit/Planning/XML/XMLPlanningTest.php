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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning\XML;

use SimpleXMLElement;

final class XMLPlanningTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportsPlanningInXML(): void
    {
        $plannings_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_planning = (new XMLPlanning(
            'Sprint plan',
            'Sprint plan',
            'T411',
            'Backlog',
            [
                10000,
                'T412',
                10506,
            ]
        ))
            ->export($plannings_xml);

        self::assertSame('planning', $xml_planning->getName());
        self::assertSame('Sprint plan', (string) $xml_planning['name']);
        self::assertSame('Sprint plan', (string) $xml_planning['plan_title']);
        self::assertSame('Backlog', (string) $xml_planning['backlog_title']);
        self::assertSame('T411', (string) $xml_planning['planning_tracker_id']);

        $this->assertTrue(isset($xml_planning->backlogs));
        $this->assertCount(3, $xml_planning->backlogs->children());
        self::assertSame('10000', (string) $xml_planning->backlogs->backlog[0]);
        self::assertSame('T412', (string) $xml_planning->backlogs->backlog[1]);
        self::assertSame('10506', (string) $xml_planning->backlogs->backlog[2]);
    }

    public function testItExportsPlanningWithPermissionsInXML(): void
    {
        $plannings_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_planning = (new XMLPlanning(
            'Sprint plan',
            'Sprint plan',
            'T411',
            'Backlog',
            [
                10000,
                'T412',
                10506,
            ]
        ))
            ->withPriorityChangePermission('ugroup1', 'ugroup2')
            ->export($plannings_xml);

        self::assertSame('planning', $xml_planning->getName());
        self::assertSame('Sprint plan', (string) $xml_planning['name']);
        self::assertSame('Sprint plan', (string) $xml_planning['plan_title']);
        self::assertSame('Backlog', (string) $xml_planning['backlog_title']);
        self::assertSame('T411', (string) $xml_planning['planning_tracker_id']);

        $this->assertTrue(isset($xml_planning->backlogs));
        $this->assertCount(3, $xml_planning->backlogs->children());
        self::assertSame('10000', (string) $xml_planning->backlogs->backlog[0]);
        self::assertSame('T412', (string) $xml_planning->backlogs->backlog[1]);
        self::assertSame('10506', (string) $xml_planning->backlogs->backlog[2]);

        $this->assertTrue(isset($xml_planning->permissions));
        $this->assertCount(2, $xml_planning->permissions->children());
        self::assertSame('ugroup1', (string) $xml_planning->permissions->permission[0]['ugroup']);
        self::assertSame('ugroup2', (string) $xml_planning->permissions->permission[1]['ugroup']);
        self::assertSame(
            'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE',
            (string) $xml_planning->permissions->permission[0]['type']
        );
        self::assertSame(
            'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE',
            (string) $xml_planning->permissions->permission[1]['type']
        );
    }
}
