<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tuleap\Tracker\REST\SemanticTimeframeWithDurationRepresentation;
use Tuleap\Tracker\REST\SemanticTimeframeWithEndDateRepresentation;

class SemanticTimeframeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $tracker;

    protected function setUp(): void
    {
        $this->tracker = Mockery::mock(Tracker::class);
        parent::setUp();
    }

    public function testIsDefined(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration   = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $end_date   = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, null, null, null))->isDefined()
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, null, null))->isDefined()
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isDefined()
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, null, $end_date))->isDefined()
        );
    }

    public function testItThrowsAnErrorIfThereIsBothADurationAndAnEndDateField(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration   = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $end_date   = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->tracker->shouldReceive('getId')->andReturn(34);

        $this->expectException(TimeframeBrokenConfigurationException::class);
        new SemanticTimeframe($this->tracker, $start_date, $duration, $end_date);
    }

    public function testIsUsedInSemantic(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date->shouldReceive('getId')->andReturn(42);
        $duration = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $duration->shouldReceive('getId')->andReturn(43);
        $a_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $a_field->shouldReceive('getId')->andReturn(44);
        $end_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date->shouldReceive('getId')->andReturn(45);

        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, null, null, null))->isUsedInSemantics($a_field)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isUsedInSemantics($a_field)
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isUsedInSemantics($start_date)
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isUsedInSemantics($duration)
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, null, $end_date))->isUsedInSemantics($end_date)
        );
    }

    public function testIsStartDateField(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date->shouldReceive('getId')->andReturn(42);
        $duration = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $duration->shouldReceive('getId')->andReturn(43);
        $a_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $a_field->shouldReceive('getId')->andReturn(44);
        $end_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date->shouldReceive('getId')->andReturn(45);

        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, null, null, null))->isStartDateField($a_field)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isStartDateField($a_field)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isStartDateField($duration)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, null, $end_date))->isStartDateField($end_date)
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isStartDateField($start_date)
        );
    }

    public function testIsDurationField(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date->shouldReceive('getId')->andReturn(42);
        $duration = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $duration->shouldReceive('getId')->andReturn(43);
        $a_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $a_field->shouldReceive('getId')->andReturn(44);
        $end_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date->shouldReceive('getId')->andReturn(45);

        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, null, null, null))->isDurationField($a_field)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isDurationField($a_field)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isDurationField($start_date)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, null, $end_date))->isDurationField($end_date)
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isDurationField($duration)
        );
    }

    public function testIsEndDateField(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date->shouldReceive('getId')->andReturn(42);
        $duration = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $duration->shouldReceive('getId')->andReturn(43);
        $a_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $a_field->shouldReceive('getId')->andReturn(44);
        $end_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date->shouldReceive('getId')->andReturn(45);

        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, null, null, null))->isEndDateField($a_field)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isEndDateField($a_field)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isEndDateField($start_date)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->isEndDateField($duration)
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, null, $end_date))->isEndDateField($end_date)
        );
    }

    public function testItDoesNotExportToXMLIfThereIsNoField(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            null,
            null,
            null
        ))->exportToXml($root, []);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLIfThereIsNoStartDate(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            null,
            Mockery::mock(\Tracker_FormElement_Field_Numeric::class),
            null
        ))->exportToXml($root, []);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLIfThereIsNoDurationAndNoEndDate(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            Mockery::mock(\Tracker_FormElement_Field_Date::class),
            null,
            null
        ))->exportToXml($root, []);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLIfStartDateIsNotInFieldMapping(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn(101);

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            $start_date_field,
            Mockery::mock(\Tracker_FormElement_Field_Numeric::class),
            null
        ))->exportToXml($root, []);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLIfDurationIsNotInFieldMapping(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn(101);

        $duration_field = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('getId')->andReturn(102);

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            $start_date_field,
            $duration_field,
            null
        ))->exportToXml($root, [
            'F101' => 101
        ]);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLIfEndDateIsNotInFieldMapping(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn(101);

        $end_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('getId')->andReturn(102);

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            $start_date_field,
            null,
            $end_date_field
        ))->exportToXml($root, [
            'F101' => 101
        ]);

        $this->assertCount(0, $root->children());
    }

    public function testItExportsToXMLWithDuration(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn(101);

        $duration_field = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('getId')->andReturn(102);

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            $start_date_field,
            $duration_field,
            null
        ))->exportToXml($root, [
            'F101' => 101,
            'F102' => 102
        ]);

        $this->assertCount(1, $root->children());
        $this->assertEquals('timeframe', (string) $root->semantic['type']);
        $this->assertEquals('F101', (string) $root->semantic->start_date_field['REF']);
        $this->assertEquals('F102', (string) $root->semantic->duration_field['REF']);
    }

    public function testItExportsToXMLWithEndDate(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn(101);

        $end_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('getId')->andReturn(102);

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            $start_date_field,
            null,
            $end_date_field
        ))->exportToXml($root, [
            'F101' => 101,
            'F102' => 102
        ]);

        $this->assertCount(1, $root->children());
        $this->assertEquals('timeframe', (string) $root->semantic['type']);
        $this->assertEquals('F101', (string) $root->semantic->start_date_field['REF']);
        $this->assertEquals('F102', (string) $root->semantic->end_date_field['REF']);
    }

    public function testItBuildsARESTRepresentationWithDurationIfDurationIsSet(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration   = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $user       = Mockery::mock(\PFUser::class);

        $start_date->shouldReceive('getId')->andReturn('23');
        $duration->shouldReceive('getId')->andReturn('24');

        $representation = (new SemanticTimeframe($this->tracker, $start_date, $duration, null))->exportToREST($user);

        $this->assertInstanceOf(SemanticTimeframeWithDurationRepresentation::class, $representation);
        $this->assertEquals(23, $representation->start_date_field_id);
        $this->assertEquals(24, $representation->duration_field_id);
    }

    public function testItBuildsARESTRepresentationWithEndDateIfEndDateIsSet(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $user = Mockery::mock(\PFUser::class);

        $start_date->shouldReceive('getId')->andReturn('23');
        $end_date->shouldReceive('getId')->andReturn('24');

        $representation = (new SemanticTimeframe($this->tracker, $start_date, null, $end_date))->exportToREST($user);

        $this->assertInstanceOf(SemanticTimeframeWithEndDateRepresentation::class, $representation);
        $this->assertEquals(23, $representation->start_date_field_id);
        $this->assertEquals(24, $representation->end_date_field_id);
    }
}
