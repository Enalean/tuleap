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

class SemanticTimeframeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $tracker;

    protected function setUp() : void
    {
        $this->tracker = Mockery::mock(Tracker::class);
        parent::setUp();
    }

    public function testIsDefined(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration   = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, null, null))->isDefined()
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, null))->isDefined()
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, $duration))->isDefined()
        );
    }

    public function testIsUsedInSemantic(): void
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date->shouldReceive(['getId' => 42]);
        $duration = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $duration->shouldReceive(['getId' => 43]);
        $a_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $a_field->shouldReceive(['getId' => 44]);
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, null, null))->isUsedInSemantics($a_field)
        );
        $this->assertFalse(
            (new SemanticTimeframe($this->tracker, $start_date, $duration))->isUsedInSemantics($a_field)
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, $duration))->isUsedInSemantics($start_date)
        );
        $this->assertTrue(
            (new SemanticTimeframe($this->tracker, $start_date, $duration))->isUsedInSemantics($duration)
        );
    }

    public function testItDoesNotExportToXMLIfThereIsNoField(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
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
            Mockery::mock(\Tracker_FormElement_Field_Numeric::class)
        ))->exportToXml($root, []);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLIfThereIsNoDuration(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            Mockery::mock(\Tracker_FormElement_Field_Date::class),
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
            Mockery::mock(\Tracker_FormElement_Field_Numeric::class)
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
            $duration_field
        ))->exportToXml($root, [
            'F101' => 101
        ]);

        $this->assertCount(0, $root->children());
    }

    public function testItExportToXML(): void
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn(101);

        $duration_field = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('getId')->andReturn(102);

        (new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            $start_date_field,
            $duration_field
        ))->exportToXml($root, [
            'F101' => 101,
            'F102' => 102
        ]);

        $this->assertCount(1, $root->children());
        $this->assertEquals('timeframe', (string) $root->semantic['type']);
        $this->assertEquals('F101', (string) $root->semantic->start_date_field['REF']);
        $this->assertEquals('F102', (string) $root->semantic->duration_field['REF']);
    }
}
