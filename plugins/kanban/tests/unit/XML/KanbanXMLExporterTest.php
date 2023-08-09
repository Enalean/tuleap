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
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use XML_RNGValidator;

final class KanbanXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportsNothingIfNoKanban(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $xml_data    = '<?xml version="1.0" encoding="UTF-8"?>
                 <project></project>';
        $xml_element = new \SimpleXMLElement($xml_data);

        $kanban_export = new KanbanXMLExporter(
            LegacyKanbanRetrieverStub::withoutActivatedKanban(),
            $this->createMock(KanbanFactory::class),
            $this->createMock(XML_RNGValidator::class),
        );
        $kanban_export->export($xml_element, $project);

        self::assertEquals(new \SimpleXMLElement($xml_data), $xml_element);
    }

    public function testItExportsKanban(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $kanban1 = new Kanban(10, TrackerTestBuilder::aTracker()->withId(1)->build(), 'Alice task');
        $kanban2 = new Kanban(20, TrackerTestBuilder::aTracker()->withId(2)->build(), 'Bob task');

        $kanban_factory = $this->createMock(KanbanFactory::class);
        $kanban_factory->method('getKanbanTrackerIds')->willReturn([1, 2]);
        $kanban_factory->method('getKanbanByTrackerId')->willReturnCallback(
            static fn(int $tracker_id): ?Kanban => match ($tracker_id) {
                1 => $kanban1,
                2 => $kanban2,
            }
        );

        $xml_data    = '<?xml version="1.0" encoding="UTF-8"?>
                 <project></project>';
        $xml_element = new \SimpleXMLElement($xml_data);

        $xml_validator = $this->createMock(XML_RNGValidator::class);
        $xml_validator->expects(self::once())->method('validate');

        $kanban_export = new KanbanXMLExporter(
            LegacyKanbanRetrieverStub::withActivatedKanban(),
            $kanban_factory,
            $xml_validator,
        );
        $kanban_export->export($xml_element, $project);

        $kanban1_attributes = $xml_element->agiledashboard->kanban_list->kanban[0]->attributes();
        $this->assertEquals('T1', (string) $kanban1_attributes->tracker_id);
        $this->assertEquals('Alice task', (string) $kanban1_attributes->name);
        $this->assertEquals('K10', (string) $kanban1_attributes->ID);

        $kanban2_attributes = $xml_element->agiledashboard->kanban_list->kanban[1]->attributes();
        $this->assertEquals('T2', (string) $kanban2_attributes->tracker_id);
        $this->assertEquals('Bob task', (string) $kanban2_attributes->name);
        $this->assertEquals('K20', (string) $kanban2_attributes->ID);
    }

    public function testItUsesAlreadyCreatedAgiledashboardNode(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $kanban = new Kanban(10, TrackerTestBuilder::aTracker()->withId(1)->build(), 'Alice task');

        $kanban_factory = $this->createMock(KanbanFactory::class);
        $kanban_factory->method('getKanbanTrackerIds')->willReturn([1]);
        $kanban_factory->method('getKanbanByTrackerId')->willReturn($kanban);

        $xml_data    = '<?xml version="1.0" encoding="UTF-8"?>
                 <project><agiledashboard /></project>';
        $xml_element = new \SimpleXMLElement($xml_data);

        $xml_validator = $this->createMock(XML_RNGValidator::class);
        $xml_validator->expects(self::once())->method('validate');

        $kanban_export = new KanbanXMLExporter(
            LegacyKanbanRetrieverStub::withActivatedKanban(),
            $kanban_factory,
            $xml_validator,
        );
        $kanban_export->export($xml_element, $project);

        self::assertCount(1, $xml_element->agiledashboard);
        self::assertCount(1, $xml_element->agiledashboard->kanban_list->kanban);
    }
}
