<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_ContributorFactory;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Semantic_ContributorFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testImport()
    {
        $xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticContributorTest.xml')
        );

        $tracker = Mockery::mock(Tracker::class);

        $f1 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $f1->shouldReceive('getId')->andReturns(111);
        $f2 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $f2->shouldReceive('getId')->andReturns(112);
        $f3 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $f3->shouldReceive('getId')->andReturns(113);

        $mapping = [
                    'F9'  => $f1,
                    'F13'  => $f2,
                    'F16' => $f3
        ];
        $semantic_contributor = Tracker_Semantic_ContributorFactory::instance()->getInstanceFromXML($xml, $mapping, $tracker);

        $this->assertEquals('contributor', $semantic_contributor->getShortName());
        $this->assertEquals(112, $semantic_contributor->getFieldId());
    }
}
