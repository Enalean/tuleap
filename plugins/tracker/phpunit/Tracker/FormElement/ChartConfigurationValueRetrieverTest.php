<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Chart_Field_Exception;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

require_once __DIR__.'/../../bootstrap.php';

class ChartConfigurationValueRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * @var ChartConfigurationValueRetriever
     */
    private $field_retriever;

    /**
     * @var \Tracker_FormElement_Field_Date
     */
    private $start_date_field;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var \Tracker_Artifact
     */
    private $artifact_sprint;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Tracker_Artifact_ChangesetValue_Date
     */
    private $start_date_value;

    /**
     * @var ChartConfigurationValueRetriever
     */
    private $configuration_value_retriever;

    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    private $capacity_value;

    /**
     * @var \Tracker_Artifact_ChangesetValue_Integer
     */
    private $capacity_field;

    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    private $duration_field;

    /**
     * @var \Tracker_Artifact_ChangesetValue_Integer
     */
    private $duration_value;

    private $start_date;
    private $capacity;
    private $duration;


    protected function setUp() : void
    {
        parent::setUp();

        $this->field_retriever = \Mockery::mock(\Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever::class);
        $this->tracker         = \Mockery::mock(\Tracker::class);
        $this->artifact_sprint = \Mockery::mock(\Tracker_Artifact::class);
        $this->user            = \Mockery::mock(\PFUser::class);

        $this->artifact_sprint->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->artifact_sprint->shouldReceive('getId')->andReturn(201);

        $this->start_date_field = \Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $this->start_date_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $this->start_date       = mktime(23, 59, 59, 01, 9, 2016);

        $this->capacity_field = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $this->capacity_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $this->capacity       = 20;

        $this->duration_field = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $this->duration_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $this->duration       = 5;

        $this->configuration_value_retriever = new ChartConfigurationValueRetriever(
            $this->field_retriever,
            Mockery::mock(TimeframeBuilder::class),
            \Mockery::mock(\Logger::class)
        );
    }

    public function testItReturnsNullWhenCapacityIsEmpty()
    {
        $this->field_retriever->shouldReceive('getCapacityField')
            ->with($this->tracker)
            ->andReturn($this->capacity_field);

        $this->artifact_sprint->shouldReceive('getValue')
            ->with($this->capacity_field)
            ->andReturn($this->capacity_value);

        $this->capacity_field->shouldReceive('getComputedValue')->andReturnNull();

        $this->assertNull($this->configuration_value_retriever->getCapacity($this->artifact_sprint, $this->user));
    }

    public function testItReturnsCapacityWhenCapacityIsSet()
    {
        $this->field_retriever->shouldReceive('getCapacityField')
            ->with($this->tracker)
            ->andReturn($this->capacity_field);

        $this->capacity_field->shouldReceive('getComputedValue')->andReturn($this->capacity);

        $this->assertSame(
            $this->configuration_value_retriever->getCapacity($this->artifact_sprint, $this->user),
            $this->capacity
        );
    }
}
