<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\SimilarField;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_String;
use Tuleap\CrossTracker\CrossTrackerReport;

class SimilarFieldsMatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $similar_fields_dao;
    /** @var Mockery\MockInterface */
    private $form_element_factory;
    /** @var SimilarFieldsMatcher */
    private $matcher;
    /** @var Mockery\MockInterface */
    private $report;

    protected function setUp()
    {
        parent::setUp();
        $this->similar_fields_dao   = Mockery::mock(SupportedFieldsDao::class);
        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->report               = Mockery::mock(CrossTrackerReport::class);
        $this->matcher              = new SimilarFieldsMatcher($this->similar_fields_dao, $this->form_element_factory);
    }

    public function testItKeepsFieldsWithTheSameNameAndTypeInAtLeastTwoTrackers()
    {
        $this->report->shouldReceive('getTrackerIds')->andReturn([91, 26, 32]);
        $first_row   = ['tracker_id' => 91, 'field_id' => 712, 'name' => 'alternator', 'type' => 'string'];
        $second_row  = ['tracker_id' => 36, 'field_id' => 994, 'name' => 'alternator', 'type' => 'string'];
        $third_row   = ['tracker_id' => 32, 'field_id' => 685, 'name' => 'alternator', 'type' => 'string'];
        $fourth_row  = ['tracker_id' => 91, 'field_id' => 343, 'name' => 'acroscleriasis', 'type' => 'sb'];
        $fifth_row   = ['tracker_id' => 32, 'field_id' => 285, 'name' => 'acroscleriasis', 'type' => 'sb'];
        $this->similar_fields_dao->shouldReceive('searchByTrackerIds')
            ->andReturn(
                [
                    $first_row,
                    $second_row,
                    $third_row,
                    $fourth_row,
                    $fifth_row
                ]
            );
        $first_field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $first_field->shouldReceive('getName')->andReturn('alternator');
        $second_field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $second_field->shouldReceive('getName')->andReturn('alternator');
        $third_field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $third_field->shouldReceive('getName')->andReturn('alternator');
        $fourth_field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $fourth_field->shouldReceive('getName')->andReturn('acroscleriasis');
        $fifth_field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $fifth_field->shouldReceive('getName')->andReturn('acroscleriasis');
        $this->form_element_factory->shouldReceive('getFormElementFieldById')
            ->withArgs([712])->andReturn($first_field);
        $this->form_element_factory->shouldReceive('getFormElementFieldById')
            ->withArgs([994])->andReturn($second_field);
        $this->form_element_factory->shouldReceive('getFormElementFieldById')
            ->withArgs([685])->andReturn($third_field);
        $this->form_element_factory->shouldReceive('getFormElementFieldById')
            ->withArgs([343])->andReturn($fourth_field);
        $this->form_element_factory->shouldReceive('getFormElementFieldById')
            ->withArgs([285])->andReturn($fifth_field);

        $result = $this->matcher->getSimilarFieldsCollection($this->report);

        $expected = new SimilarFieldCollection(
            [
                'alternator'     => [
                    91 => $first_field,
                    36 => $second_field,
                    32 => $third_field
                ],
                'acroscleriasis' => [
                    91 => $fourth_field,
                    32 => $fifth_field
                ]
            ]
        );

        $this->assertEquals($expected, $result);
    }

    public function testItFiltersOutFieldsInOnlyOneTracker()
    {
        $this->report->shouldReceive('getTrackerIds')->andReturn([87, 85]);
        $first_row  = ['tracker_id' => 87, 'field_id' => 811, 'name' => 'archegonium', 'type' => 'int'];
        $second_row = ['tracker_id' => 85, 'field_id' => 398, 'name' => 'Cassiepeia', 'type' => 'sb'];
        $this->similar_fields_dao->shouldReceive('searchByTrackerIds')->andReturns([$first_row, $second_row]);

        $result = $this->matcher->getSimilarFieldsCollection($this->report);

        $expected = new SimilarFieldCollection([]);

        $this->assertEquals($expected, $result);
    }

    public function testItFiltersOutFieldsWithTheSameNameButNotTheSameType()
    {
        $this->report->shouldReceive('getTrackerIds')->andReturn([89,98]);
        $first_row = ['tracker_id' => 89, 'field_id' => 994, 'name' => 'floriculturally', 'type' => 'sb'];
        $second_row = ['tracker_id' => 98, 'field_id' => 811, 'name' => 'floriculturally', 'type' => 'rb'];
        $this->similar_fields_dao->shouldReceive('searchByTrackerIds')->andReturns([$first_row, $second_row]);

        $result = $this->matcher->getSimilarFieldsCollection($this->report);

        $expected = new SimilarFieldCollection([]);

        $this->assertEquals($expected, $result);
    }
}
