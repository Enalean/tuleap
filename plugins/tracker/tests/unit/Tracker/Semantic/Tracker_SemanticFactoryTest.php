<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-present. All rights reserved
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Semantic_Contributor;
use Tracker_Semantic_ContributorFactory;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusFactory;
use Tracker_Semantic_Title;
use Tracker_Semantic_TitleFactory;
use Tracker_SemanticFactory;
use Tracker_Tooltip;
use Tracker_TooltipFactory;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_SemanticFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetInstanceFromXml()
    {
        $xml_title       = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticTitleTest.xml'));
        $xml_status      = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticStatusTest.xml'));
        $xml_tooltip     = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticTooltipTest.xml'));
        $xml_contributor = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticContributorTest.xml'));
        $semantic_status  = Mockery::mock(Tracker_Semantic_Status::class);
        $semantic_title   = Mockery::mock(Tracker_Semantic_Title::class);
        $semantic_contributor = Mockery::mock(Tracker_Semantic_Contributor::class);
        $semantic_tooltip = Mockery::mock(Tracker_Tooltip::class);
        $semantic_status_factory  = Mockery::mock(Tracker_Semantic_StatusFactory::class);
        $semantic_status_factory->shouldReceive('getInstanceFromXML')->andReturn($semantic_status);
        $semantic_title_factory   = Mockery::mock(Tracker_Semantic_TitleFactory::class);
        $semantic_title_factory->shouldReceive('getInstanceFromXML')->andReturn($semantic_title);
        $semantic_tooltip_factory = Mockery::mock(Tracker_TooltipFactory::class);
        $semantic_tooltip_factory->shouldReceive('getInstanceFromXML')->andReturn($semantic_tooltip);
        $semantic_contributor_factory = Mockery::mock(Tracker_Semantic_ContributorFactory::class);
        $semantic_contributor_factory->shouldReceive('getInstanceFromXML')->andReturn($semantic_contributor);

        $tsf = Mockery::mock(Tracker_SemanticFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tsf->shouldReceive('getSemanticStatusFactory')->andReturns($semantic_status_factory);
        $tsf->shouldReceive('getSemanticTitleFactory')->andReturns($semantic_title_factory);
        $tsf->shouldReceive('getSemanticTooltipFactory')->andReturns($semantic_tooltip_factory);
        $tsf->shouldReceive('getSemanticContributorFactory')->andReturns($semantic_contributor_factory);

        $tracker = Mockery::mock(\Tracker::class);

        $mapping = [
            'F8'  => 108,
            'F9'  => 109,
            'F16' => 116,
            'F14' => 114
        ];

        //Title
        $title = $tsf->getInstanceFromXML($xml_title, $xml_title, $mapping, $tracker);
        $this->assertEquals($semantic_title, $title);

        //Status
        $status = $tsf->getInstanceFromXML($xml_status, $xml_status, $mapping, $tracker);
        $this->assertEquals($semantic_status, $status);

        //Tooltip
        $tooltip = $tsf->getInstanceFromXML($xml_tooltip, $xml_tooltip, $mapping, $tracker);
        $this->assertEquals($semantic_tooltip, $tooltip);

        //Contributor
        $contributor = $tsf->getInstanceFromXML($xml_contributor, $xml_contributor, $mapping, $tracker);
        $this->assertEquals($semantic_contributor, $contributor);
    }
}
