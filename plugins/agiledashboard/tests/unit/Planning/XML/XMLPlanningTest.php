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

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

final class XMLPlanningTest extends TestCase
{
    public function testItExportsPlanningInXML(): void
    {
        $plannings_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_planning = (new XMLPlanning(
            "Sprint plan",
            "Sprint plan",
            "T411",
            "Backlog",
            [
                10000,
                'T412',
                10506
            ]
        ))
            ->export($plannings_xml);

        $this->assertSame("planning", $xml_planning->getName());
        $this->assertSame("Sprint plan", (string) $xml_planning['name']);
        $this->assertSame("Sprint plan", (string) $xml_planning['plan_title']);
        $this->assertSame("Backlog", (string) $xml_planning['backlog_title']);
        $this->assertSame("T411", (string) $xml_planning['planning_tracker_id']);

        $this->assertTrue(isset($xml_planning->backlogs));
        $this->assertCount(3, $xml_planning->backlogs->children());
        $this->assertSame("10000", (string) $xml_planning->backlogs->backlog[0]);
        $this->assertSame("T412", (string) $xml_planning->backlogs->backlog[1]);
        $this->assertSame("10506", (string) $xml_planning->backlogs->backlog[2]);
    }
}
