<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Widget;

use Codendi_Request;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SimpleXMLElement;
use Tracker_Report_Renderer;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\XML\MappingsRegistry;
use Widget;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectRendererWidgetXMLImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Widget&MockObject $widget;

    private MappingsRegistry $mapping_registry;

    private ProjectRendererWidgetXMLImporter $importer;

    private Tracker_Report_Renderer&MockObject $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->widget           = $this->createMock(Widget::class);
        $this->mapping_registry = new MappingsRegistry();

        $this->renderer = $this->createMock(Tracker_Report_Renderer::class);

        $this->importer = new ProjectRendererWidgetXMLImporter();
    }

    public function testItImportsTheWidget(): void
    {
        $widget_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <widget name="plugin_tracker_projectrenderer">
            <preference name="renderer">
              <reference name="id" REF="R1"></reference>
              <value name="title">Imported</value>
            </preference>
          </widget>
        ');

        $this->mapping_registry->addReference('R1', $this->renderer);
        $this->renderer->expects($this->once())->method('getId')->willReturn(456);

        $this->assertWidgetCreateWithParams(456, 'Imported');

        $event = new ConfigureAtXMLImport($this->widget, $widget_xml, $this->mapping_registry, ProjectTestBuilder::aProject()->build());

        $this->importer->import($event);
    }

    public function testItThrownAnExceptionIfRendererNotFoundInMapping(): void
    {
        $widget_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <widget name="plugin_tracker_projectrenderer">
            <preference name="renderer">
              <reference name="id" REF="R1"></reference>
              <value name="title">Imported</value>
            </preference>
          </widget>
        ');

        $this->widget->expects($this->never())->method('create');

        $this->expectException(RuntimeException::class);

        $event = new ConfigureAtXMLImport($this->widget, $widget_xml, $this->mapping_registry, ProjectTestBuilder::aProject()->build());

        $this->importer->import($event);
    }

    public function testItImportsTheWidgetWithoutInfoIfNoPreferenceProvided(): void
    {
        $widget_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <widget name="plugin_tracker_projectrenderer">
            </widget>
        ');

        $this->assertWidgetCreateWithDefault();

        $event = new ConfigureAtXMLImport($this->widget, $widget_xml, $this->mapping_registry, ProjectTestBuilder::aProject()->build());

        $this->importer->import($event);
    }

    public function testItImportsTheWidgetWithoutInfoIfPreferenceProvidedIsNotKnown(): void
    {
        $widget_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <widget name="plugin_tracker_projectrenderer">
                <preference name="notknown">
                    <reference name="id" REF="R1"></reference>
                    <value name="title">Imported</value>
                </preference>
            </widget>
        ');

        $this->assertWidgetCreateWithDefault();

        $event = new ConfigureAtXMLImport($this->widget, $widget_xml, $this->mapping_registry, ProjectTestBuilder::aProject()->build());

        $this->importer->import($event);
    }

    private function assertWidgetCreateWithDefault(): void
    {
        $this->widget
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(static function (Codendi_Request $request) {
                $renderer_request_date = $request->get('renderer');
                return match (true) {
                    count($renderer_request_date) > 0 &&
                        $renderer_request_date['renderer_id'] === null &&
                        $renderer_request_date['title'] === null => true
                };
            });
    }

    private function assertWidgetCreateWithParams(int $renderer_id, string $title): void
    {
        $this->widget
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(static function (Codendi_Request $request) use ($renderer_id, $title) {
                $renderer_request_date = $request->get('renderer');
                return match (true) {
                    count($renderer_request_date) > 0 &&
                        $renderer_request_date['renderer_id'] === $renderer_id &&
                        $renderer_request_date['title'] === $title => true,
                };
            });
    }
}
