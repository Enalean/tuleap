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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticTimeframeFromXMLBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
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
        'T11' => 111,
        'T12' => 112,
        'T13' => 113,
    ];

    #[\Override]
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
                'F201' => $this->getMockedField(\Tuleap\Tracker\FormElement\Field\Date\DateField::class),
                'F202' => $this->getMockedField(\Tuleap\Tracker\FormElement\Field\Integer\IntegerField::class),
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
                'F201' => $this->getMockedField(\Tuleap\Tracker\FormElement\Field\Date\DateField::class),
                'F202' => $this->getMockedField(\Tuleap\Tracker\FormElement\Field\Date\DateField::class),
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

        $timeframe_with_duration = $this->createMock(TimeframeWithDuration::class);
        $semantic_timeframe      = new SemanticTimeframe($this->mocked_tracker, $timeframe_with_duration);

        $this->tracker_factory
            ->expects($this->any())
            ->method('getTrackerById')
            ->with(111)
            ->willReturn($this->mocked_implied_from_tracker);

        $this->semantic_timeframe_builder
            ->expects($this->any())
            ->method('getSemantic')
            ->with($this->mocked_implied_from_tracker)
            ->willReturn($semantic_timeframe);

        $timeframe_with_duration->method('getName');

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

        $timeframe_not_configured = $this->createMock(TimeframeNotConfigured::class);
        $implied_semantic         = new SemanticTimeframe($this->mocked_tracker, $timeframe_not_configured);

        $timeframe_not_configured->method('isDefined')->willReturn(false);

        $this->tracker_factory
            ->expects($this->any())
            ->method('getTrackerById')
            ->with(111)
            ->willReturn(null);

        $timeframe_not_configured->method('getName');

        $this->semantic_timeframe_builder
            ->expects($this->any())
            ->method('buildTimeframeSemanticNotConfigured')
            ->willReturn($implied_semantic);

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

        $timeframe_implied               = $this->createMock(TimeframeNotConfigured::class);
        $implied_semantic_not_configured = new SemanticTimeframe($this->mocked_tracker, $timeframe_implied);
        $semantic_not_configured         = new SemanticTimeframe($this->mocked_tracker, $timeframe_implied);

        $timeframe_implied->method('isDefined')->willReturn(false);

        $this->tracker_factory
            ->expects($this->any())
            ->method('getTrackerById')
            ->with(111)
            ->willReturn($this->mocked_implied_from_tracker);

        $this->semantic_timeframe_builder
            ->expects($this->any())
            ->method('getSemantic')
            ->with($this->mocked_implied_from_tracker)
            ->willReturn($implied_semantic_not_configured);

        $timeframe_implied
            ->method('getName')
            ->willReturn('timeframe-not-configured');


        $this->semantic_timeframe_builder
            ->expects($this->any())
            ->method('buildTimeframeSemanticNotConfigured')
            ->willReturn($semantic_not_configured);

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

        $timeframe_implied        = $this->createMock(TimeframeImpliedFromAnotherTracker::class);
        $implied_semantic         = new SemanticTimeframe($this->mocked_implied_from_tracker, $timeframe_implied);
        $timeframe_not_configured = $this->createMock(TimeframeNotConfigured::class);
        $semantic_not_configured  = new SemanticTimeframe($this->mocked_tracker, $timeframe_not_configured);

        $timeframe_not_configured->method('isDefined')->willReturn(false);

        $this->tracker_factory
            ->expects($this->any())
            ->method('getTrackerById')
            ->with(111)
            ->willReturn($this->mocked_implied_from_tracker);

        $this->semantic_timeframe_builder
            ->expects($this->any())
            ->method('getSemantic')
            ->with($this->mocked_implied_from_tracker)
            ->willReturn($implied_semantic);

        $timeframe_implied
            ->method('getName')
            ->willReturn('timeframe-implied-from-another-tracker');

        $this->semantic_timeframe_builder
            ->expects($this->any())
            ->method('buildTimeframeSemanticNotConfigured')
            ->willReturn($semantic_not_configured);

        $this->tracker_factory->expects($this->any())->method('getTrackerById')->with(111)->willReturn($this->mocked_implied_from_tracker);

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

    #[\PHPUnit\Framework\Attributes\DataProvider('getDataForNullReturnsTests')]
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

    public static function getDataForNullReturnsTests(): array
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
                    'F202' => new \Tuleap\Tracker\FormElement\Field\Date\DateField(202, 113, 2, 'name', 'label', 'description', true, '', false, [], 1),
                ],
            ], [
                'xml' => $xml_with_end_date,
                'mapping' => [
                    'F201' => new \Tuleap\Tracker\FormElement\Field\Date\DateField(201, 113, 2, 'name', 'label', 'description', true, '', false, [], 1),
                ],
            ], [
                'xml' => $xml_with_duration,
                'mapping' => [
                    'F202' => new \Tuleap\Tracker\FormElement\Field\Integer\IntegerField(202, 113, 2, 'name', 'label', 'description', true, '', false, [], 1),
                ],
            ], [
                'xml' => $xml_with_duration,
                'mapping' => [
                    'F201' => new \Tuleap\Tracker\FormElement\Field\Date\DateField(201, 113, 2, 'name', 'label', 'description', true, '', false, [], 1),
                ],
            ],
        ];
    }

    private function getMockedField(string $type): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder($type)->disableOriginalConstructor()->getMock();
    }

    private function getMockedTracker(): \Tuleap\Tracker\Tracker
    {
        $mock = $this->getMockBuilder(\Tuleap\Tracker\Tracker::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->any())->method('getId')->willReturn(113);
        return $mock;
    }
}
