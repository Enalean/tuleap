<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use Codendi_Request;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use SimpleXMLElement;
use Tuleap\CrossTracker\Tests\Stub\Widget\CrossTrackerSearchWidgetStub;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\XML\MappingsRegistry;

#[DisableReturnValueGenerationForTestDoubles]
final class WidgetCrossTrackerXMLImporterTest extends TestCase
{
    private CrossTrackerSearchWidgetStub $widget;

    protected function setUp(): void
    {
        $this->widget = CrossTrackerSearchWidgetStub::withDefault();
    }

    /**
     * @throws \Tuleap\Project\XML\Import\ImportNotValidException
     */
    private function configureWidget(SimpleXMLElement $widget_xml): void
    {
        $import_configuration = new ConfigureAtXMLImport(
            $this->widget,
            $widget_xml,
            new MappingsRegistry(),
            ProjectTestBuilder::aProject()->build()
        );

        (new WidgetCrossTrackerXMLImporter())->configureWidget($import_configuration);
    }

    public function testItBuildsTheRequestParametersAccordingToXML(): void
    {
        $this->widget = CrossTrackerSearchWidgetStub::withIdAndCallback(
            10,
            function (Codendi_Request $request) {
                $queries = $request->get('queries');
                self::assertNotNull($queries);
                self::assertIsArray($queries);
                $expected_queries = [
                    [
                        'title'       => 'i30n',
                        'description' => '2.0 L4',
                        'tql'         => "SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270",
                        'is_default'  => false,
                    ],
                    [
                        'title'       => 'i20n',
                        'description' => '1.6 L4',
                        'tql'         => "SELECT @pretty_title FROM @project='self' WHERE power > 200",
                        'is_default'  => true,
                    ],
                ];
                self::assertSame(
                    $expected_queries,
                    $queries
                );
            }
        );

        $widget_xml = new SimpleXMLElement(
            <<<XML
                <widget name="crosstrackersearch">
                    <preference name="query">
                        <value name="is-default">0</value>
                        <value name="title"><![CDATA[i30n]]></value>
                        <value name="description"><![CDATA[2.0 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270]]></value>
                    </preference>
                    <preference name="query">
                        <value name="is-default">1</value>
                        <value name="title"><![CDATA[i20n]]></value>
                        <value name="description"><![CDATA[1.6 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @pretty_title FROM @project='self' WHERE power > 200]]></value>
                    </preference>
                  </widget>
XML
        );

        $this->configureWidget($widget_xml);
    }

    public function testItThrowsAnExceptionIfTheTitleIsEmpty(): void
    {
        $widget_xml = new SimpleXMLElement(
            <<<XML
                <widget name="crosstrackersearch">
                    <preference name="query">
                        <value name="is-default">0</value>
                         <value name="title"></value>
                        <value name="description"><![CDATA[2.0 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270]]></value>
                    </preference>
                  </widget>
XML
        );

        $this->expectException(ImportNotValidException::class);

        $this->configureWidget($widget_xml);
    }

    public function testItThrowsAnExceptionIfTheTitleIsMissing(): void
    {
        $widget_xml = new SimpleXMLElement(
            <<<XML
                <widget name="crosstrackersearch">
                    <preference name="query">
                        <value name="is-default">0</value>
                        <value name="description"><![CDATA[2.0 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270]]></value>
                    </preference>
                  </widget>
XML
        );

        $this->expectException(ImportNotValidException::class);

        $this->configureWidget($widget_xml);
    }

    public function testItThrowsAnExceptionIfTheDescriptionIsMissing(): void
    {
        $widget_xml = new SimpleXMLElement(
            <<<XML
               <widget name="crosstrackersearch">
                    <preference name="query">
                        <value name="is-default">0</value>
                        <value name="title"><![CDATA[i30n]]></value>
                        <value name="tql"><![CDATA[SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270]]></value>
                    </preference>
                  </widget>
XML
        );

        $this->expectException(ImportNotValidException::class);

        $this->configureWidget($widget_xml);
    }

    public function testItThrowsAnExceptionIfTheTqlQueryIsEmpty(): void
    {
        $widget_xml = new SimpleXMLElement(
            <<<XML
                <widget name="crosstrackersearch">
                    <preference name="query">
                        <value name="is-default">0</value>
                        <value name="title"><![CDATA[i30n]]></value>
                        <value name="description"><![CDATA[2.0 L4]]></value>
                        <value name="tql"></value>
                    </preference>
                  </widget>
XML
        );

        $this->expectException(ImportNotValidException::class);

        $this->configureWidget($widget_xml);
    }

    public function testItThrowsAnExceptionIfTheTqlQueryIsMissing(): void
    {
        $widget_xml = new SimpleXMLElement(
            <<<XML
                <widget name="crosstrackersearch">
                    <preference name="query">
                        <value name="is-default">0</value>
                        <value name="title"><![CDATA[i30n]]></value>
                        <value name="description"><![CDATA[2.0 L4]]></value>
                    </preference>
                  </widget>
XML
        );

        $this->expectException(ImportNotValidException::class);

        $this->configureWidget($widget_xml);
    }

    public function testItThrowsAnExceptionIfTheIsDefaultValueIsMissing(): void
    {
        $widget_xml = new SimpleXMLElement(
            <<<XML
                <widget name="crosstrackersearch">
                    <preference name="query">
                        <value name="title"><![CDATA[i30n]]></value>
                        <value name="description"><![CDATA[2.0 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270]]></value>
                    </preference>
                  </widget>
XML
        );
        $this->expectException(ImportNotValidException::class);

        $this->configureWidget($widget_xml);
    }

    public function testItThrowsAnExceptionIfThereAreSeveralDefaultQueries(): void
    {
        $widget_xml = new SimpleXMLElement(
            <<<XML
                <widget name="crosstrackersearch">
                     <preference name="query">
                        <value name="is-default">1</value>
                        <value name="title"><![CDATA[i30n]]></value>
                        <value name="description"><![CDATA[2.0 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270]]></value>
                    </preference>
                    <preference name="query">
                        <value name="is-default">1</value>
                        <value name="title"><![CDATA[i20n]]></value>
                        <value name="description"><![CDATA[1.6 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @pretty_title FROM @project='self' WHERE power > 200]]></value>
                    </preference>
                  </widget>
XML
        );
        $this->expectException(ImportNotValidException::class);

        $this->configureWidget($widget_xml);
    }
}
