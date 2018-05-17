<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use TuleapTestCase;

require_once __DIR__.'/../../bootstrap.php';

class ChartConfigurationValueRetrieverTest extends TuleapTestCase
{
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
     * @var ChartConfigurationFieldRetriever
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


    public function setUp()
    {
        parent::setUp();

        $this->field_retriever = mock('Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever');
        $this->tracker         = mock('Tracker');
        $this->artifact_sprint = mock('Tracker_Artifact');
        $this->user            = mock('PFUser');
        stub($this->artifact_sprint)->getTracker()->returns($this->tracker);

        $this->start_date_field = mock('Tracker_FormElement_Field_Date');
        $this->start_date_value = mock('Tracker_Artifact_ChangesetValue_Date');
        $this->start_date       = mktime(23, 59, 59, 01, 9, 2016);

        $this->capacity_field = mock('Tracker_FormElement_Field_Integer');
        $this->capacity_value = mock('Tracker_Artifact_ChangesetValue_Integer');
        $this->capacity       = 20;

        $this->duration_field = mock('Tracker_FormElement_Field_Integer');
        $this->duration_value = mock('Tracker_Artifact_ChangesetValue_Integer');
        $this->duration       = 5;

        $this->configuration_value_retriever = new ChartConfigurationValueRetriever(
            $this->field_retriever,
            mock('Logger')
        );
    }

    public function itReturnsTimestampWhenStartDateIsSet()
    {
        stub($this->field_retriever)->getStartDateField($this->artifact_sprint, $this->user)->returns(
            $this->start_date_field
        );

        stub($this->artifact_sprint)->getValue($this->start_date_field)->returns($this->start_date_value);
        stub($this->start_date_value)->getTimestamp()->returns($this->start_date);

        $this->assertEqual(
            $this->configuration_value_retriever->getStartDate($this->artifact_sprint, $this->user),
            $this->start_date
        );
    }

    public function itThrowsAnExceptionWhenStartDateIsEmpty()
    {
        stub($this->field_retriever)->getStartDateField($this->artifact_sprint, $this->user)->returns(
            $this->start_date_field
        );

        stub($this->artifact_sprint)->getValue($this->start_date_field)->returns($this->start_date_value);
        stub($this->start_date_value)->getTimestamp()->returns(null);

        $this->expectException('Tracker_FormElement_Chart_Field_Exception');
        $this->configuration_value_retriever->getStartDate($this->artifact_sprint, $this->user);
    }

    public function itReturnsZeroWhenCapacityIsEmpty()
    {
        stub($this->field_retriever)->getCapacityField($this->tracker)->returns(
            $this->capacity_field
        );

        stub($this->artifact_sprint)->getValue($this->capacity_field)->returns($this->capacity_value);
        stub($this->capacity_value)->getValue()->returns(null);

        $this->assertEqual(
            $this->configuration_value_retriever->getCapacity($this->artifact_sprint, $this->user),
            0
        );
    }

    public function itReturnsCapacityWhenCapacityIsSet()
    {
        stub($this->field_retriever)->getCapacityField($this->tracker)->returns(
            $this->capacity_field
        );

        stub($this->artifact_sprint)->getValue($this->capacity_field)->returns($this->capacity_value);
        stub($this->capacity_field)->getComputedValue()->returns($this->capacity);

        $this->assertEqual(
            $this->configuration_value_retriever->getCapacity($this->artifact_sprint, $this->user),
            $this->capacity
        );
    }

    public function itReturnsValueWhenDurationIsSet()
    {
        stub($this->field_retriever)->getDurationField($this->artifact_sprint, $this->user)->returns(
            $this->duration_field
        );

        stub($this->artifact_sprint)->getValue($this->duration_field)->returns($this->duration_value);
        stub($this->duration_value)->getValue()->returns($this->duration);

        $this->assertEqual(
            $this->configuration_value_retriever->getDuration($this->artifact_sprint, $this->user),
            $this->duration
        );
    }

    public function itThrowsAnExceptionWhenDurationIsMinorThanZero()
    {
        stub($this->field_retriever)->getDurationField($this->artifact_sprint, $this->user)->returns(
            $this->duration_field
        );

        stub($this->artifact_sprint)->getValue($this->duration_field)->returns($this->duration_value);
        stub($this->duration_value)->getValue()->returns(0);

        $this->expectException('Tracker_FormElement_Chart_Field_Exception');
        $this->configuration_value_retriever->getDuration($this->artifact_sprint, $this->user);
    }

    public function itThrowsAnExceptionWhenDurationIsEqualToOne()
    {
        stub($this->field_retriever)->getDurationField($this->artifact_sprint, $this->user)->returns(
            $this->duration_field
        );

        stub($this->artifact_sprint)->getValue($this->duration_field)->returns($this->duration_value);
        stub($this->duration_value)->getValue()->returns(1);

        $this->expectException('Tracker_FormElement_Chart_Field_Exception');
        $this->configuration_value_retriever->getDuration($this->artifact_sprint, $this->user);
    }
}
