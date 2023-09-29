<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\Widget;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\XML\MappingsRegistry;

final class WidgetKanbanXMLImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MappingsRegistry $registry;
    /**
     * @var \Widget&MockObject
     */
    private $widget;

    public function setUp(): void
    {
        $kanban = new \Tuleap\Kanban\Kanban(
            20001,
            TrackerTestBuilder::aTracker()->build(),
            false,
            'Kanban name'
        );

        $this->registry = new MappingsRegistry();
        $this->registry->addReference('K123', $kanban);

        $this->widget = $this->createMock(\Widget::class);
    }

    public function testItGetReferenceFromRegistry(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <widget name="plugin_agiledashboard_projects_kanban">
                <preference name="kanban">
                    <reference name="id" REF="K123"/>
                </preference>
              </widget>'
        );

        $this->widget->expects(self::once())->method('create')->with(self::callback(function (\Codendi_Request $request): bool {
            if (
                $request->get('kanban') &&
                $request->getInArray('kanban', 'id') === 20001 &&
                $request->getInArray('kanban', 'title') === ''
            ) {
                return true;
            }
            return false;
        }))->willReturn(30003);

        $event = new ConfigureAtXMLImport($this->widget, $xml, $this->registry, ProjectTestBuilder::aProject()->build());

        $importer = new WidgetKanbanXMLImporter();
        $importer->configureWidget($event);

        self::assertTrue($event->isWidgetConfigured());
        self::assertEquals(30003, $event->getContentId());
    }

    public function testItShouldThrowExceptionsWhenIDIsNotPresent(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <widget name="plugin_agiledashboard_projects_kanban">
                <preference name="kanban">
                    <value name="title">Da kanban</value>
                </preference>
              </widget>'
        );

        $this->widget->expects(self::never())->method('create');

        $event = new ConfigureAtXMLImport($this->widget, $xml, $this->registry, ProjectTestBuilder::aProject()->build());

        $importer = new WidgetKanbanXMLImporter();

        $this->expectException(\RuntimeException::class);
        $importer->configureWidget($event);
    }

    public function testItShouldThrowExceptionsWhenKanbanIsNotReferenced(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <widget name="plugin_agiledashboard_projects_kanban">
                <preference name="kanban">
                    <reference name="id" REF="K2222"/>
                </preference>
              </widget>'
        );

        $this->widget->expects(self::never())->method('create');

        $event = new ConfigureAtXMLImport($this->widget, $xml, $this->registry, ProjectTestBuilder::aProject()->build());

        $importer = new WidgetKanbanXMLImporter();

        $this->expectException(\RuntimeException::class);

        $importer->configureWidget($event);
    }
}
