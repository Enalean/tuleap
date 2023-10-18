<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\Project\Kanban;

use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;

final class JiraKanbanActivatorTest extends TestCase
{
    public function testItAddsKanbanInProjectXMLContent(): void
    {
        $project_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');

        (new JiraKanbanActivator(new NullLogger()))->activateKanbanForProject($project_xml);

        self::assertNotNull($project_xml->agiledashboard);
        self::assertNotNull($project_xml->agiledashboard->kanban_list);
        $xml_kanban_list = $project_xml->agiledashboard->kanban_list;
        self::assertSame("Kanban", (string) $xml_kanban_list['title']);

        self::assertCount(1, $project_xml->agiledashboard->kanban_list->kanban);
        $kanban_xml = $project_xml->agiledashboard->kanban_list->kanban[0];
        self::assertNotNull($kanban_xml);
        self::assertSame("T1", (string) $kanban_xml['tracker_id']);
        self::assertSame("Issues", (string) $kanban_xml['name']);
        self::assertSame("K01", (string) $kanban_xml['ID']);
        self::assertSame("1", (string) $kanban_xml['is_promoted']);
    }
}
