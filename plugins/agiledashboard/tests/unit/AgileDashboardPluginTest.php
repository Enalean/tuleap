<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

class AgileDashboardPluginTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private $parameters;

    protected function setUp(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
<semantic type="initial_effort">
 <shortname>effort</shortname>
 <label>Effort</label>
 <description>Define the effort of an artifact</description>
 <field REF="F13"/>
</semantic>'
        );

        $xml_mapping = ['F13' => $this->createStub(Tracker_FormElement_Field_Float::class)];
        $tracker     = $this->createStub(Tracker::class);
        $semantic    = null;
        $type        = AgileDashBoard_Semantic_InitialEffort::NAME;

        $this->parameters = [
            'xml'               => $xml,
            'full_semantic_xml' => $xml,
            'xml_mapping'       => $xml_mapping,
            'tracker'           => $tracker,
            'semantic'          => &$semantic,
            'type'              => $type,
        ];
    }

    /**
     * Not exactly a unit test but, then again, we are testing a plugin!
     */
    public function testItCreatesSemantic(): void
    {
        $effort_factory = AgileDashboard_Semantic_InitialEffortFactory::instance();

        $plugin = $this->createPartialMock(AgileDashboardPlugin::class, ['getSemanticInitialEffortFactory']);
        $plugin->method('getSemanticInitialEffortFactory')->willReturn($effort_factory);

        $plugin->tracker_event_semantic_from_xml($this->parameters);

        $this->assertNotNull($this->parameters['semantic']);
        $this->assertInstanceOf(AgileDashBoard_Semantic_InitialEffort::class, $this->parameters['semantic']);
    }
}
