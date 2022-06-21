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

use Tracker_Report_Renderer;
use Tuleap\Test\PHPUnit\TestCase;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Widget_ProjectRendererTest extends TestCase
{
    private \Tracker_Widget_ProjectRenderer $widget;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_Report_RendererFactory
     */
    private $renderer_factory;

    protected function setUp(): void
    {
        $this->renderer_factory = $this->createStub(\Tracker_Report_RendererFactory::class);
        $this->widget           = new \Tracker_Widget_ProjectRenderer($this->renderer_factory);
    }

    public function testExportsWidgetWhenRendererExist(): void
    {
        $this->renderer_factory->method('getReportRendererById')->willReturn($this->createStub(Tracker_Report_Renderer::class));
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

    public function testDoesNotExportWidgetWhenRendererDoesNotExist(): void
    {
        $this->renderer_factory->method('getReportRendererById')->willReturn(null);

        self::assertNull($this->widget->exportAsXML());
    }
}
