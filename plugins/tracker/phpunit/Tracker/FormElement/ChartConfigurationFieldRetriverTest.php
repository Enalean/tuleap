<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Chart_Field_Exception;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

require_once __DIR__ . '/../../bootstrap.php';

class ChartConfigurationFieldRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_field_factoy;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $configuration_retriever;

    /**
     * @var \Tracker_Artifact
     */
    private $artifact;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    private $field_duration;

    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    private $field_capacity;

    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    private $field_remaining_effort;

    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;

    private $field_start_date;

    private $logger;

    protected function setUp() : void
    {
        parent::setUp();

        $this->form_element_field_factoy = \Mockery::mock(\Tracker_FormElementFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->tracker = \Mockery::mock(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(101);
        $this->tracker->shouldReceive('getName')->andReturn("Scrum");

        $this->artifact = \Mockery::mock(\Tracker_Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->user     = \Mockery::mock(\PFUser::class);

        $this->field_duration         = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $this->field_capacity         = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $this->field_remaining_effort = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $this->field_start_date       = \Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->field_duration->shouldReceive('getName')->andReturn('duration');
        $this->field_capacity->shouldReceive('getName')->andReturn('capacity');
        $this->field_remaining_effort->shouldReceive('getName')->andReturn('remaining_effort');
        $this->field_start_date->shouldReceive('getName')->andReturn('start_date');

        $this->semantic_timeframe_builder = \Mockery::mock(SemanticTimeframeBuilder::class);

        $this->logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->configuration_retriever = new ChartConfigurationFieldRetriever(
            $this->form_element_field_factoy,
            $this->semantic_timeframe_builder,
            $this->logger
        );
    }

    public function testItThrowsAnExceptionWhenDurationFieldDoesNotExist()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'duration',
            ['int', 'float', 'computed']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getNumericFieldByNameForUser')->with(
            $this->artifact->getTracker(),
            $this->user,
            'duration'
        )->andReturn(null);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->andReturn(
            new SemanticTimeframe(
                $this->tracker,
                $this->field_start_date,
                null,
                null
            )
        );

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);
        $this->expectExceptionMessage(
            "The tracker doesn't have a \"duration\" Integer field or you don't have the permission to access it."
        );

        $this->configuration_retriever->getDurationField($this->tracker, $this->user);
    }

    public function testItThrowsAnExceptionWhenDurationFieldExistsButUserCannotReadIt()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'duration',
            ['int', 'float', 'computed']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getNumericFieldByNameForUser')->with(
            $this->artifact->getTracker(),
            $this->user,
            'duration'
        )->andReturn(null);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->andReturn(
            new SemanticTimeframe(
                $this->tracker,
                $this->field_start_date,
                $this->field_duration,
                null
            )
        );

        $this->field_duration->shouldReceive('userCanRead')->andReturn(false);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->expectExceptionMessage(
            "The tracker doesn't have a \"duration\" Integer field or you don't have the permission to access it."
        );

        $this->configuration_retriever->getDurationField($this->tracker, $this->user);
    }

    public function testItReturnsDurationFieldWhenDurationFieldExistsAnUserCanReadIt()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'duration',
            ['int', 'float', 'computed']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getNumericFieldByNameForUser')->with(
            $this->artifact->getTracker(),
            $this->user,
            'duration'
        )->andReturn($this->field_duration);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->andReturn(
            new SemanticTimeframe(
                $this->tracker,
                $this->field_start_date,
                $this->field_duration,
                null
            )
        );

        $this->field_duration->shouldReceive('userCanRead')->andReturn(true);

        $this->assertSame(
            $this->configuration_retriever->getDurationField($this->tracker, $this->user),
            $this->field_duration
        );
    }

    public function testItThrowsAnExceptionWhenStartDateFieldDoesNotExist()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'start_date',
            ['date']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getDateFieldByNameForUser')->with(
            $this->artifact->getTracker(),
            $this->user,
            'start_date'
        )->andReturn(null);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->andReturn(
            new SemanticTimeframe(
                $this->tracker,
                null,
                $this->field_duration,
                null
            )
        );

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->expectExceptionMessage(
            "The tracker doesn't have a \"start_date\" Date field or you don't have the permission to access it."
        );

        $this->configuration_retriever->getStartDateField($this->tracker, $this->user);
    }

    public function testItThrowsAnExceptionWhenStartDateFieldExistsButUserCannotReadIt()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'start_date',
            ['date']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getDateFieldByNameForUser')->with(
            $this->artifact->getTracker(),
            $this->user,
            'start_date'
        )->andReturn(null);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->andReturn(
            new SemanticTimeframe(
                $this->tracker,
                $this->field_start_date,
                $this->field_duration,
                null
            )
        );

        $this->field_start_date->shouldReceive('userCanRead')->andReturn(false);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->expectExceptionMessage(
            "The tracker doesn't have a \"start_date\" Date field or you don't have the permission to access it."
        );

        $this->configuration_retriever->getStartDateField($this->tracker, $this->user);
    }

    public function testItReturnsStartDateFieldWhenStartDateFieldExistsAnUserCanReadIt()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'start_date',
            ['date']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getDateFieldByNameForUser')->with(
            $this->artifact->getTracker(),
            $this->user,
            'start_date'
        )->andReturn($this->field_start_date);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->andReturn(
            new SemanticTimeframe(
                $this->tracker,
                $this->field_start_date,
                $this->field_duration,
                null
            )
        );

        $this->field_start_date->shouldReceive('userCanRead')->andReturn(true);

        $this->assertSame(
            $this->field_start_date,
            $this->configuration_retriever->getStartDateField($this->tracker, $this->user)
        );
    }

    public function testItThrowsAnExceptionWhenCapacityFieldDoesNotExist()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'capacity',
            ['int', 'float', 'computed']
        )->andReturn(false);

        $this->form_element_field_factoy->shouldReceive('getNumericFieldByName')->with(
            $this->tracker,
            'capacity'
        )->andReturn(null);

        $this->logger->shouldReceive('info');

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->expectExceptionMessage(
            'The tracker doesn\'t have a "capacity" Integer or Float or Computed field or you don\'t have the permission to access it.'
        );

        $this->configuration_retriever->getCapacityField($this->tracker);
    }

    public function testItReturnsCapacityFieldWhenFieldExist()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'capacity',
            ['int', 'float', 'computed']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getNumericFieldByName')->with(
            $this->tracker,
            'capacity'
        )->andReturn($this->field_capacity);

        $this->assertSame(
            $this->configuration_retriever->getCapacityField($this->tracker),
            $this->field_capacity
        );
    }

    public function testItReturnsNullWhenRemainingEffortFieldDoesNotExist()
    {
        $form_element_factory = \Tracker_FormElementFactory::instance();

        $configuration_retriever = new ChartConfigurationFieldRetriever(
            $form_element_factory,
            $this->semantic_timeframe_builder,
            $this->logger
        );

        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'remaining_effort',
            ['int', 'float', 'computed']
        )->andReturn(false);

        $this->assertNull($configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user));
    }

    public function testItReturnsNullWhenRemainingEffortFieldExistsAndUserCanNotReadIt()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'remaining_effort',
            ['int', 'float', 'computed']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getNumericFieldByName')->with(
            $this->tracker,
            'remaining_effort'
        )->andReturn($this->field_remaining_effort);

        $this->form_element_field_factoy->shouldReceive('getUsedFieldByName')->with(
            $this->tracker->getId(),
            'remaining_effort'
        )->andReturn($this->field_remaining_effort);

        $this->field_remaining_effort->shouldReceive("userCanRead")->andReturn(false);

        $this->assertNull($this->configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user));
    }

    public function testItReturnsFieldWhenRemainingEffortFieldExistsAndUserCanReadIt()
    {
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')->with(
            'remaining_effort',
            ['int', 'float', 'computed']
        )->andReturn(true);

        $this->form_element_field_factoy->shouldReceive('getNumericFieldByName')->with(
            $this->tracker,
            'remaining_effort'
        )->andReturn($this->field_remaining_effort);

        $this->form_element_field_factoy->shouldReceive('getUsedFieldByName')->with(
            $this->tracker->getId(),
            'remaining_effort'
        )->andReturn($this->field_remaining_effort);

        $this->field_remaining_effort->shouldReceive("userCanRead")->andReturn(true);

        $this->assertSame(
            $this->field_remaining_effort,
            $this->configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user)
        );
    }
}
