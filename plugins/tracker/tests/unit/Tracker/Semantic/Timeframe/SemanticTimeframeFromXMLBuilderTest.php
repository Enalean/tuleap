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

use Tracker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;

class SemanticTimeframeFromXMLBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var SemanticTimeframeFromXMLBuilder
     */
    private $builder;
    private \TrackerFactory $tracker_factory;
    private SemanticTimeframeBuilder $semantic_timeframe_builder;
    private Tracker $mocked_tracker;
    private Tracker $mocked_implied_from_tracker;
    private const TRACKER_MAPPING = [
        "T11" => 111,
        "T12" => 112,
        "T13" => 113,
    ];

    protected function setUp(): void
    {
        $this->tracker_factory             = $this->createMock(\TrackerFactory::class);
        $this->semantic_timeframe_builder  = $this->createMock(SemanticTimeframeBuilder::class);
        $this->mocked_tracker              = $this->createMock(Tracker::class);
        $this->mocked_implied_from_tracker = $this->createMock(Tracker::class);

        $this->builder = new SemanticTimeframeFromXMLBuilder(
            $this->createMock(ArtifactLinkFieldValueDao::class),
            $this->tracker_factory,
            $this->semantic_timeframe_builder,
        );
    }

    protected function tearDown(): void
    {
        \Mockery::close();
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
                'F202' => $this->getMockedField(\Tracker_FormElement_Field_Integer::class),
            ],
            $this->getMockedTracker(),
            self::TRACKER_MAPPING
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
                'F202' => $this->getMockedField(\Tracker_FormElement_Field_Date::class),
            ],
            $this->getMockedTracker(),
            self::TRACKER_MAPPING
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticTimeframe::class, $semantic);

        $this->assertTrue(
            $semantic->isDefined()
        );
    }

    public function testItBuildsSemanticTimeframeImpliedFromAnotherTrackerFromXML(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <inherited_from_tracker id="T11"/>
             </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $timeframe_with_duration = \Mockery::mock(TimeframeWithDuration::class);
        $semantic_timeframe      = new SemanticTimeframe($this->mocked_tracker, $timeframe_with_duration);

        $this->tracker_factory
            ->expects(self::any())
            ->method("getTrackerById")
            ->with(111)
            ->will(self::returnValue($this->mocked_implied_from_tracker));

        $this->semantic_timeframe_builder
            ->expects(self::any())
            ->method("getSemantic")
            ->with($this->mocked_implied_from_tracker)
            ->will(self::returnValue($semantic_timeframe));

        $timeframe_with_duration->shouldReceive('getName');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [],
            $this->mocked_tracker,
            self::TRACKER_MAPPING
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticTimeframe::class, $semantic);

        $this->assertTrue(
            $semantic->isDefined()
        );
    }

    public function testItBuildsSemanticTimeframeNotConfiguredFromXMLWhenImpliedFromTrackerNotFoundInDao(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <inherited_from_tracker id="T11"/>
             </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $timeframe_not_configured = \Mockery::mock(TimeframeNotConfigured::class);
        $implied_semantic         = new SemanticTimeframe($this->mocked_tracker, $timeframe_not_configured);

        $timeframe_not_configured->shouldReceive("isDefined")->andReturn(false);

        $this->tracker_factory
            ->expects(self::any())
            ->method("getTrackerById")
            ->with(111)
            ->will(self::returnValue(null));

        $timeframe_not_configured->shouldReceive('getName');

        $this->semantic_timeframe_builder
            ->expects(self::any())
            ->method("buildTimeframeSemanticNotConfigured")
            ->will(self::returnValue($implied_semantic));

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [],
            $this->mocked_tracker,
            self::TRACKER_MAPPING
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticTimeframe::class, $semantic);

        $this->assertFalse(
            $semantic->isDefined()
        );
    }

    public function testItBuildsSemanticTimeframeNotConfiguredFromXMLWhenImpliedFromTrackerTimeframeIsNotConfigured(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <inherited_from_tracker id="T11"/>
             </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $timeframe_implied               = \Mockery::mock(TimeframeNotConfigured::class);
        $implied_semantic_not_configured = new SemanticTimeframe($this->mocked_tracker, $timeframe_implied);
        $semantic_not_configured         = new SemanticTimeframe($this->mocked_tracker, $timeframe_implied);

        $timeframe_implied->shouldReceive("isDefined")->andReturn(false);

        $this->tracker_factory
            ->expects(self::any())
            ->method("getTrackerById")
            ->with(111)
            ->will(self::returnValue($this->mocked_implied_from_tracker));

        $this->semantic_timeframe_builder
            ->expects(self::any())
            ->method("getSemantic")
            ->with($this->mocked_implied_from_tracker)
            ->will(self::returnValue($implied_semantic_not_configured));

        $timeframe_implied
            ->shouldReceive('getName')
            ->andReturn('timeframe-not-configured');


        $this->semantic_timeframe_builder
            ->expects(self::any())
            ->method("buildTimeframeSemanticNotConfigured")
            ->will(self::returnValue($semantic_not_configured));

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [],
            $this->mocked_tracker,
            self::TRACKER_MAPPING
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticTimeframe::class, $semantic);

        $this->assertFalse(
            $semantic->isDefined()
        );
    }

    public function testItBuildsSemanticTimeframeNotConfiguredFromXMLWhenImpliedFromTrackerTimeframeIsTimeframeImpliedFromAnotherTracker(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <inherited_from_tracker id="T11"/>
             </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $timeframe_implied        = \Mockery::mock(TimeframeImpliedFromAnotherTracker::class);
        $implied_semantic         = new SemanticTimeframe($this->mocked_implied_from_tracker, $timeframe_implied);
        $timeframe_not_configured = \Mockery::mock(TimeframeNotConfigured::class);
        $semantic_not_configured  = new SemanticTimeframe($this->mocked_tracker, $timeframe_not_configured);

        $timeframe_not_configured->shouldReceive("isDefined")->andReturn(false);

        $this->tracker_factory
            ->expects(self::any())
            ->method("getTrackerById")
            ->with(111)
            ->will(self::returnValue($this->mocked_implied_from_tracker));

        $this->semantic_timeframe_builder
            ->expects(self::any())
            ->method("getSemantic")
            ->with($this->mocked_implied_from_tracker)
            ->will(self::returnValue($implied_semantic));

        $timeframe_implied
            ->shouldReceive('getName')
            ->andReturn('timeframe-implied-from-another-tracker');

        $this->semantic_timeframe_builder
            ->expects(self::any())
            ->method("buildTimeframeSemanticNotConfigured")
            ->will(self::returnValue($semantic_not_configured));

        $this->tracker_factory->expects(self::any())->method("getTrackerById")->with(111)->will(self::returnValue($this->mocked_implied_from_tracker));

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [],
            $this->mocked_tracker,
            self::TRACKER_MAPPING
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticTimeframe::class, $semantic);

        $this->assertFalse(
            $semantic->isDefined()
        );
    }

    public function testItDoesNotBuildSemanticTimeframeWithImpliedFromAnotherTrackerWhenTrackerMappingIsEmpty(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic>
              <inherited_from_tracker id="T11"/>
             </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [],
            $this->mocked_tracker,
            []
        );

        $this->assertNull($semantic);
    }

    public function testItReturnsNullWhenNoStartDateNorImpliedFromTrackerFieldsDefinedInXml(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                  <end_date_field REF="F202"/>
            '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [],
            $this->getMockedTracker(),
            []
        );
        $this->assertNull($semantic);
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
            $this->getMockedTracker(),
            []
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
                    'F202' => $this->getMockedField(\Tracker_FormElement_Field_Date::class),
                ],
            ], [
                'xml' => $xml_with_end_date,
                'mapping' => [
                    'F201' => $this->getMockedField(\Tracker_FormElement_Field_Date::class),
                ],
            ], [
                'xml' => $xml_with_duration,
                'mapping' => [
                    'F202' => $this->getMockedField(\Tracker_FormElement_Field_Integer::class),
                ],
            ], [
                'xml' => $xml_with_duration,
                'mapping' => [
                    'F201' => $this->getMockedField(\Tracker_FormElement_Field_Date::class),
                ],
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
