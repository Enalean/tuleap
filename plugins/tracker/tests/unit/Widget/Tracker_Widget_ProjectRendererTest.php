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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Widget;

use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ReportTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Widget_ProjectRendererTest extends TestCase
{
    private const CURRENT_PROJECT_ID = 1001;
    private const ANOTHER_PROJECT_ID = 1002;

    private \Tracker_Widget_ProjectRenderer $widget;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_Report_RendererFactory
     */
    private $renderer_factory;

    protected function setUp(): void
    {
        \HTTPRequest::setInstance(
            HTTPRequestBuilder::get()->withParam('group_id', self::CURRENT_PROJECT_ID)->build()
        );

        $this->renderer_factory = $this->createStub(\Tracker_Report_RendererFactory::class);
        $this->widget           = new \Tracker_Widget_ProjectRenderer($this->renderer_factory);
    }

    protected function tearDown(): void
    {
        \HTTPRequest::clearInstance();
    }

    public function testExportsWidgetWhenRendererExist(): void
    {
        $project  = ProjectTestBuilder::aProject()->withId(self::CURRENT_PROJECT_ID)->build();
        $tracker  = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $report   = ReportTestBuilder::aPublicReport()->inTracker($tracker)->build();
        $renderer = new \Tracker_Report_Renderer_Table(123, $report, 'Table', '', 0, 15, false);
        $this->renderer_factory->method('getReportRendererById')->willReturn($renderer);
        $this->widget->renderer_id = 200;
        $xml                       = $this->widget->exportAsXML();

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
                        <widget name="plugin_tracker_projectrenderer">
                          <preference name="renderer">
                            <value name="title"/>
                            <reference REF="R200" name="id"/>
                          </preference>
                        </widget>',
            $xml->asXML()
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRenderer')]
    public function testDoesNotExportWidgetWhenRendererDoesNotExist(?\Tracker_Report_Renderer $renderer): void
    {
        $this->renderer_factory->method('getReportRendererById')->willReturn($renderer);

        self::assertNull($this->widget->exportAsXML());
    }

    public static function getRenderer(): iterable
    {
        yield 'renderer does not exist' => [null];

        $project        = ProjectTestBuilder::aProject()->withId(self::CURRENT_PROJECT_ID)->build();
        $tracker        = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $private_report = ReportTestBuilder::aPrivateReport()->inTracker($tracker)->build();
        yield 'report is not public' => [self::getTable($private_report)];

        $project         = ProjectTestBuilder::aProject()->withId(self::CURRENT_PROJECT_ID)->build();
        $deleted_tracker = TrackerTestBuilder::aTracker()->withDeletionDate(1234567890)->withProject($project)->build();
        $report          = ReportTestBuilder::aPublicReport()->inTracker($deleted_tracker)->build();
        yield 'tracker is deleted' => [self::getTable($report)];

        $another_project = ProjectTestBuilder::aProject()->withId(self::ANOTHER_PROJECT_ID)->build();
        $tracker         = TrackerTestBuilder::aTracker()->withProject($another_project)->build();
        $report          = ReportTestBuilder::aPublicReport()->inTracker($tracker)->build();
        yield 'widget targets a renderer in another project' => [self::getTable($report)];
    }

    private static function getTable(\Tracker_Report $report): \Tracker_Report_Renderer
    {
        return new \Tracker_Report_Renderer_Table(
            123,
            $report,
            'Table',
            '',
            0,
            15,
            false,
        );
    }
}
