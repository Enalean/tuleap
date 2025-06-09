<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-present. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tracker_SemanticFactory;
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributor;
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributorFactory;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitleFactory;
use Tuleap\Tracker\Semantic\Tooltip\SemanticTooltip;
use Tuleap\Tracker\Semantic\Tooltip\SemanticTooltipFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_SemanticFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testGetInstanceFromXml(): void
    {
        $xml_title               = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticTitleTest.xml'));
        $xml_status              = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticStatusTest.xml'));
        $xml_tooltip             = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticTooltipTest.xml'));
        $xml_contributor         = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticContributorTest.xml'));
        $semantic_status         = $this->createMock(TrackerSemanticStatus::class);
        $semantic_title          = $this->createMock(TrackerSemanticTitle::class);
        $semantic_contributor    = $this->createMock(TrackerSemanticContributor::class);
        $semantic_tooltip        = $this->createMock(SemanticTooltip::class);
        $semantic_status_factory = $this->createMock(TrackerSemanticStatusFactory::class);
        $semantic_status_factory->method('getInstanceFromXML')->willReturn($semantic_status);
        $semantic_title_factory = $this->createMock(TrackerSemanticTitleFactory::class);
        $semantic_title_factory->method('getInstanceFromXML')->willReturn($semantic_title);
        $semantic_tooltip_factory = $this->createMock(SemanticTooltipFactory::class);
        $semantic_tooltip_factory->method('getInstanceFromXML')->willReturn($semantic_tooltip);
        $semantic_contributor_factory = $this->createMock(TrackerSemanticContributorFactory::class);
        $semantic_contributor_factory->method('getInstanceFromXML')->willReturn($semantic_contributor);

        $tsf = $this->createPartialMock(
            Tracker_SemanticFactory::class,
            [
                'getSemanticStatusFactory',
                'getSemanticTitleFactory',
                'getSemanticTooltipFactory',
                'getSemanticContributorFactory',
            ]
        );
        $tsf->method('getSemanticStatusFactory')->willReturn($semantic_status_factory);
        $tsf->method('getSemanticTitleFactory')->willReturn($semantic_title_factory);
        $tsf->method('getSemanticTooltipFactory')->willReturn($semantic_tooltip_factory);
        $tsf->method('getSemanticContributorFactory')->willReturn($semantic_contributor_factory);

        $tracker = $this->createMock(\Tracker::class);

        $mapping = [
            'F8'  => 108,
            'F9'  => 109,
            'F16' => 116,
            'F14' => 114,
        ];

        //Title
        $title = $tsf->getInstanceFromXML($xml_title, $xml_title, $mapping, $tracker, []);
        $this->assertEquals($semantic_title, $title);

        //Status
        $status = $tsf->getInstanceFromXML($xml_status, $xml_status, $mapping, $tracker, []);
        $this->assertEquals($semantic_status, $status);

        //Tooltip
        $tooltip = $tsf->getInstanceFromXML($xml_tooltip, $xml_tooltip, $mapping, $tracker, []);
        $this->assertEquals($semantic_tooltip, $tooltip);

        //Contributor
        $contributor = $tsf->getInstanceFromXML($xml_contributor, $xml_contributor, $mapping, $tracker, []);
        $this->assertEquals($semantic_contributor, $contributor);
    }
}
