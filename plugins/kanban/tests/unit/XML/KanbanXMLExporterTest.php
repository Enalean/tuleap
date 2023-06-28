<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Kanban\XML;

use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\Stubs\Legacy\LegacyKanbanRetrieverStub;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class KanbanXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportsNothingIfNoKanban(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $xml_data    = '<?xml version="1.0" encoding="UTF-8"?>
                 <kanban_list />';
        $xml_element = new \SimpleXMLElement($xml_data);

        $kanban_export = new KanbanXMLExporter(
            LegacyKanbanRetrieverStub::withoutActivatedKanban(),
            $this->createMock(KanbanFactory::class),
        );
        $kanban_export->export($xml_element, $project);

        self::assertEquals(new \SimpleXMLElement($xml_data), $xml_element);
    }

    public function testItExportsKanban(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $kanban1 = new Kanban(10, 1, 'Alice task');
        $kanban2 = new Kanban(20, 2, 'Bob task');

        $kanban_factory = $this->createMock(KanbanFactory::class);
        $kanban_factory->method('getKanbanTrackerIds')->willReturn([1, 2]);
        $kanban_factory->method('getKanbanByTrackerId')->willReturnCallback(
            static fn(int $tracker_id): ?Kanban => match ($tracker_id) {
                1 => $kanban1,
                2 => $kanban2,
            }
        );

        $xml_data    = '<?xml version="1.0" encoding="UTF-8"?>
                 <kanban_list />';
        $xml_element = new \SimpleXMLElement($xml_data);

        $kanban_export = new KanbanXMLExporter(
            LegacyKanbanRetrieverStub::withActivatedKanban(),
            $kanban_factory,
        );
        $kanban_export->export($xml_element, $project);

        $kanban_list_node = KanbanXMLExporter::NODE_KANBAN_LST;

        $kanban1_attributes = $xml_element->$kanban_list_node->kanban[0]->attributes();
        $this->assertEquals('T1', (string) $kanban1_attributes->tracker_id);
        $this->assertEquals('Alice task', (string) $kanban1_attributes->name);
        $this->assertEquals('K10', (string) $kanban1_attributes->ID);

        $kanban2_attributes = $xml_element->$kanban_list_node->kanban[1]->attributes();
        $this->assertEquals('T2', (string) $kanban2_attributes->tracker_id);
        $this->assertEquals('Bob task', (string) $kanban2_attributes->name);
        $this->assertEquals('K20', (string) $kanban2_attributes->ID);
    }
}
