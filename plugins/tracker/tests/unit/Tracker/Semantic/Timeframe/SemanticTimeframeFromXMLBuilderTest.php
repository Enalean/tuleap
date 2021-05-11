<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

class SemanticTimeframeFromXMLBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var SemanticTimeframeFromXMLBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new SemanticTimeframeFromXMLBuilder();
    }

    public function testBuildsSemanticTimeframeWithDurationFromXML(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <start_date_field REF="F201"/>
              <duration_field REF="F202"/>
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [
                'F201' => $this->getMockedField(\Tracker_FormElement_Field_Date::class),
                'F202' => $this->getMockedField(\Tracker_FormElement_Field_Integer::class)
            ],
            $this->getMockedTracker()
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticTimeframe::class, $semantic);

        $this->assertTrue(
            $semantic->isDefined()
        );
    }

    public function testBuildsSemanticTimeframeWithEndDateFromXML(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <start_date_field REF="F201"/>
              <end_date_field REF="F202"/>
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [
                'F201' => $this->getMockedField(\Tracker_FormElement_Field_Date::class),
                'F202' => $this->getMockedField(\Tracker_FormElement_Field_Date::class)
            ],
            $this->getMockedTracker()
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticTimeframe::class, $semantic);

        $this->assertTrue(
            $semantic->isDefined()
        );
    }

    /**
     * @dataProvider getDataForNullReturnsTests
     */
    public function testItReturnsNullWhenFieldsNotFoundInMapping(\SimpleXMLElement $xml, array $mapping): void
    {
        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            $mapping,
            $this->getMockedTracker()
        );
        $this->assertNull($semantic);
    }

    public function getDataForNullReturnsTests(): array
    {
        $xml_with_end_date = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <start_date_field REF="F201"/>
              <end_date_field REF="F202"/>
            </semantic>
        '
        );

        $xml_with_duration = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <start_date_field REF="F201"/>
              <duration_field REF="F202"/>
            </semantic>
        '
        );

        return [
            [
                'xml' => $xml_with_end_date,
                'mapping' => [
                    'F202' => $this->getMockedField(\Tracker_FormElement_Field_Date::class)
                ]
            ], [
                'xml' => $xml_with_end_date,
                'mapping' => [
                    'F201' => $this->getMockedField(\Tracker_FormElement_Field_Date::class)
                ]
            ], [
                'xml' => $xml_with_duration,
                'mapping' => [
                    'F202' => $this->getMockedField(\Tracker_FormElement_Field_Integer::class)
                ]
            ], [
                'xml' => $xml_with_duration,
                'mapping' => [
                    'F201' => $this->getMockedField(\Tracker_FormElement_Field_Date::class)
                ]
            ],
        ];
    }

    private function getMockedField(string $type): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder($type)->disableOriginalConstructor()->getMock();
    }

    private function getMockedTracker(): \Tracker
    {
        $mock = $this->getMockBuilder(\Tracker::class)->disableOriginalConstructor()->getMock();
        $mock->expects(self::any())->method('getId')->will(self::returnValue(113));
        return $mock;
    }
}
