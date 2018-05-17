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

use Tracker_FormElement_Chart_Field_Exception;
use TuleapTestCase;

require_once __DIR__.'/../../bootstrap.php';

class ChartConfigurationFieldRetriverTest extends TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();
        $this->form_element_field_factoy = partial_mock(
            'Tracker_FormElementFactory',
            array(
                'getUsedFieldByName',
                'getComputableFieldByNameForUser'
            )
        );

        $this->tracker = mock('Tracker');
        stub($this->tracker)->getId()->returns(101);

        $this->artifact = aMockArtifact()->withTracker($this->tracker)->build();
        $this->user     = aUser()->build();

        $this->field_duration         = mock('Tracker_FormElement_Field_Integer');
        $this->field_capacity         = mock('Tracker_FormElement_Field_Integer');
        $this->field_remaining_effort = mock('Tracker_FormElement_Field_Integer');

        $this->configuration_retriever = new ChartConfigurationFieldRetriever(
            $this->form_element_field_factoy,
            mock('Logger')
        );
    }

    public function itThrowsAnExceptionWhenDurationFieldDoesNotExist()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'duration',
            array('int', 'float', 'computed')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->artifact->getTracker()->getId(),
            'duration'
        )->returns(null);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
            )
        );

        $this->configuration_retriever->getDurationField($this->artifact, $this->user);
    }

    public function itThrowsAnExceptionWhenDurationFieldExistsButUserCannotReadIt()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'duration',
            array('int', 'float', 'computed')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->artifact->getTracker()->getId(),
            'duration'
        )->returns($this->field_duration);

        stub($this->field_duration)->userCanRead()->returns(false);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_duration_warning')
            )
        );

        $this->configuration_retriever->getDurationField($this->artifact, $this->user);
    }

    public function itThrowsAnExceptionWhenDurationFieldIsNotANumericField()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'duration',
            array('int', 'float', 'computed')
        )->returns(true);

        stub($this->field_duration)->userCanRead()->returns(false);


        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_duration_warning')
            )
        );

        $this->configuration_retriever->getDurationField($this->artifact, $this->user);
    }

    public function itReturnsDurationFieldWhenDurationFieldExistsAnUserCanReadIt()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'duration',
            array('int', 'float', 'computed')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->artifact->getTracker()->getId(),
            'duration'
        )->returns($this->field_duration);

        stub($this->field_duration)->userCanRead()->returns(true);

        $this->assertEqual(
            $this->configuration_retriever->getDurationField($this->artifact, $this->user),
            $this->field_duration
        );
    }

    public function itThrowsAnExceptionWhenStartDateFieldDoesNotExist()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'start_date',
            array('date')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->artifact->getTracker()->getId(),
            'start_date'
        )->returns(null);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
            )
        );

        $this->configuration_retriever->getStartDateField($this->artifact, $this->user);
    }

    public function itThrowsAnExceptionWhenStartDateFieldExistsButUserCannotReadIt()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'start_date',
            array('date')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->artifact->getTracker()->getId(),
            'start_date'
        )->returns($this->field_duration);

        stub($this->field_duration)->userCanRead()->returns(false);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
            )
        );

        $this->configuration_retriever->getStartDateField($this->artifact, $this->user);
    }

    public function itThrowsAnExceptionWhenStartDateFieldIsNotADateField()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'duration',
            array('date')
        )->returns(false);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
            )
        );

        $this->configuration_retriever->getDurationField($this->artifact, $this->user);
    }

    public function itReturnsStartDateFieldWhenStartDateFieldExistsAnUserCanReadIt()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'start_date',
            array('date')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->artifact->getTracker()->getId(),
            'start_date'
        )->returns($this->field_duration);

        stub($this->field_duration)->userCanRead()->returns(true);

        $this->assertEqual(
            $this->configuration_retriever->getStartDateField($this->artifact, $this->user),
            $this->field_duration
        );
    }

    public function itThrowsAnExceptionWhenCapacityFieldDoesNotExist()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'capacity',
            array('int', 'float', 'computed')
        )->returns(true);


        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->tracker->getId(),
            'capacity'
        )->returns(null);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_capacity_warning')
            )
        );
        stub($this->field_duration)->userCanRead()->returns(true);

        $this->assertEqual($this->configuration_retriever->getCapacityField($this->tracker, $this->user), null);
    }

    public function itThrowsAnExceptionWhenCapacityFieldIsNotANumericField()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'capacity',
            array('int', 'float', 'computed')
        )->returns(false);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_capacity_warning')
            )
        );

        $this->configuration_retriever->getDurationField($this->artifact, $this->user);
    }

    public function itReturnsCapacityFieldWhenFieldExist()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'capacity',
            array('int', 'float', 'computed')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->tracker->getId(),
            'capacity'
        )->returns($this->field_capacity);
        stub($this->field_capacity)->userCanRead()->returns(true);

        $this->assertEqual(
            $this->configuration_retriever->getCapacityField($this->tracker, $this->user),
            $this->field_capacity
        );
    }

    public function itReturnsNullWhenRemainingEffortFieldDoesNotExist()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'remaining_effort',
            array('int', 'float', 'computed')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->tracker->getId(),
            'remaining_effort'
        )->returns(null);
        stub($this->field_duration)->userCanRead()->returns(true);

        $this->assertEqual(
            $this->configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user),
            null
        );
    }

    public function itReturnsNullWhenRemainingEffortFieldExistsAndUserCanNotReadIt()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'remaining_effort',
            array('int', 'float', 'computed')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->tracker->getId(),
            'remaining_effort'
        )->returns($this->field_remaining_effort);

        stub($this->form_element_field_factoy)->getComputableFieldByNameForUser(
            $this->artifact->getTracker()->getId(),
            'remaining_effort',
            $this->user
        )->returns(false);

        $this->assertEqual(
            $this->configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user),
            null
        );
    }

    public function itThrowsAnExceptionWhenRemainingEffortFieldIsNotANumericField()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'remaining_effort',
            array('int', 'float', 'computed')
        )->returns(false);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_remaining_effort_warning')
            )
        );

        $this->configuration_retriever->getDurationField($this->artifact, $this->user);
    }

    public function itReturnsFieldWhenRemainingEffortFieldExistsAndUserCanReadIt()
    {
        stub($this->tracker)->hasFormElementWithNameAndType(
            'remaining_effort',
            array('int', 'float', 'computed')
        )->returns(true);

        stub($this->form_element_field_factoy)->getUsedFieldByName(
            $this->tracker->getId(),
            'remaining_effort'
        )->returns($this->field_remaining_effort);
        stub($this->field_remaining_effort)->userCanRead()->returns(true);

        stub($this->form_element_field_factoy)->getComputableFieldByNameForUser(
            $this->artifact->getTracker()->getId(),
            'remaining_effort',
            $this->user
        )->returns(true);

        $this->assertEqual(
            $this->configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user),
            $this->field_remaining_effort
        );
    }
}
