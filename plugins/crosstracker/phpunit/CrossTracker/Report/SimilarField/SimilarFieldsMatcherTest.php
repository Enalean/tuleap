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
    /**
     * @var Mockery\MockInterface
     */
    private $user;
    /** @var Mockery\MockInterface */
    private $report;

    protected function setUp()
    {
        parent::setUp();
        $this->similar_fields_dao   = Mockery::mock(SupportedFieldsDao::class);
        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->report               = Mockery::mock(CrossTrackerReport::class);
        $this->user                 = Mockery::mock(\PFUser::class);
        $this->matcher              = new SimilarFieldsMatcher($this->similar_fields_dao, $this->form_element_factory);
    }

    public function testMatchingFieldsAreRetrieved()
    {
        $this->report->shouldReceive('getTrackerIds')->andReturn([91, 26]);
        $first_field_row  = ['formElement_type' => 'string'];
        $second_field_row = ['formElement_type' => 'string'];
        $this->similar_fields_dao->shouldReceive('searchByTrackerIds')
            ->andReturn(
                [
                    $first_field_row,
                    $second_field_row
                ]
            );

        $first_field  = \Mockery::mock(\Tracker_FormElement_Field::class);
        $first_field->shouldReceive('getName')->andReturn('field_name');
        $first_field->shouldReceive('userCanRead')->andReturn(true);
        $second_field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $second_field->shouldReceive('getName')->andReturn('field_name');
        $second_field->shouldReceive('userCanRead')->andReturn(true);
        $this->form_element_factory->shouldReceive('getCachedInstanceFromRow')
            ->andReturn($first_field, $second_field);

        $this->assertCount(2, $this->matcher->getSimilarFieldsCollection($this->report, $this->user));
    }

    public function testMatchingFieldsWithoutEnoughPermissionsAreLeftOut()
    {
        $this->report->shouldReceive('getTrackerIds')->andReturn([91, 26]);
        $first_field_row  = ['formElement_type' => 'string'];
        $second_field_row = ['formElement_type' => 'string'];
        $this->similar_fields_dao->shouldReceive('searchByTrackerIds')
            ->andReturn(
                [
                    $first_field_row,
                    $second_field_row
                ]
            );

        $first_field  = \Mockery::mock(\Tracker_FormElement_Field::class);
        $first_field->shouldReceive('getName')->andReturn('field_name');
        $first_field->shouldReceive('userCanRead')->andReturn(true);
        $second_field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $second_field->shouldReceive('getName')->andReturn('field_name');
        $second_field->shouldReceive('userCanRead')->andReturn(false);
        $this->form_element_factory->shouldReceive('getCachedInstanceFromRow')
            ->andReturn($first_field, $second_field);

        $this->assertCount(0, $this->matcher->getSimilarFieldsCollection($this->report, $this->user));
    }
}
