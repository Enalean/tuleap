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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Mockery;
use Tuleap\XML\MappingsRegistry;

final class WidgetKanbanXMLImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var MappingsRegistry
     */
    private $registry;
    /**
     * @var Mockery
     */
    private $widget;

    public function setUp(): void
    {
        $kanban = new \AgileDashboard_Kanban(
            20001,
            101,
            'Kanban name'
        );

        $this->registry = new MappingsRegistry();
        $this->registry->addReference('K123', $kanban);

        $this->widget = Mockery::mock(\Widget::class);
    }

    public function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        Mockery::close();
        parent::tearDown();
    }

    public function testItGetReferenceFromRegistry()
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <widget name="plugin_agiledashboard_projects_kanban">
                <preference name="kanban">
                    <reference name="id" REF="K123"/>
                </preference>
              </widget>'
        );

        $this->widget->shouldReceive('create')->with(\Mockery::on(function (\Codendi_Request $request) {
            if (
                $request->get('kanban') &&
                $request->getInArray('kanban', 'id') === 20001 &&
                $request->getInArray('kanban', 'title') === ''
            ) {
                return true;
            }
            return false;
        }))->once()->andReturn(30003);

        $event = new ConfigureAtXMLImport($this->widget, $xml, $this->registry, ProjectTestBuilder::aProject()->build());

        $importer = new WidgetKanbanXMLImporter();
        $importer->configureWidget($event);

        $this->assertTrue($event->isWidgetConfigured());
        $this->assertEquals(30003, $event->getContentId());
    }

    public function testItShouldThrowExceptionsWhenIDIsNotPresent()
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <widget name="plugin_agiledashboard_projects_kanban">
                <preference name="kanban">
                    <value name="title">Da kanban</value>
                </preference>
              </widget>'
        );

        $this->widget->shouldNotReceive('create');

        $event = new ConfigureAtXMLImport($this->widget, $xml, $this->registry, ProjectTestBuilder::aProject()->build());

        $importer = new WidgetKanbanXMLImporter();

        $this->expectException(\RuntimeException::class);
        $importer->configureWidget($event);
    }

    public function testItShouldThrowExceptionsWhenKanbanIsNotReferenced()
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <widget name="plugin_agiledashboard_projects_kanban">
                <preference name="kanban">
                    <reference name="id" REF="K2222"/>
                </preference>
              </widget>'
        );

        $this->widget->shouldNotReceive('create');

        $event = new ConfigureAtXMLImport($this->widget, $xml, $this->registry, ProjectTestBuilder::aProject()->build());

        $importer = new WidgetKanbanXMLImporter();

        $this->expectException(\RuntimeException::class);

        $importer->configureWidget($event);
    }
}
