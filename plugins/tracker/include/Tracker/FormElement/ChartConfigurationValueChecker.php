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

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Artifact\Artifact;

class ChartConfigurationValueChecker
{
    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $configuration_field_retriever;
    /**
     * @var ChartConfigurationValueRetriever
     */
    private $configuration_value_retriever;

    public function __construct(
        ChartConfigurationFieldRetriever $configuration_field_retriever,
        ChartConfigurationValueRetriever $configuration_value_retriever,
    ) {
        $this->configuration_field_retriever = $configuration_field_retriever;
        $this->configuration_value_retriever = $configuration_value_retriever;
    }

    public function hasStartDate(Artifact $artifact, PFUser $user)
    {
        try {
            $start_date_field = $this->configuration_field_retriever->getStartDateField($artifact->getTracker(), $user);
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            return false;
        }
        $artifact_value = $artifact->getValue($start_date_field);

        if ($artifact_value === null) {
            return false;
        }

        $timestamp = $artifact_value->getTimestamp();

        return $timestamp !== null;
    }

    private function hasFieldChanged(
        Tracker_Artifact_Changeset $new_changeset,
        Tracker_FormElement_Field $field,
    ) {
        return $new_changeset->getValue($field) && $new_changeset->getValue($field)->hasChanged();
    }

    /**
     * @return bool
     */
    public function areBurndownFieldsCorrectlySet(Artifact $artifact, PFUser $user)
    {
        try {
            $date_period = $this->configuration_value_retriever->getDatePeriod($artifact, $user);
            return $date_period->getStartDate() !== null && $date_period->getDuration() !== null;
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            return false;
        }
    }

    public function hasConfigurationChange(
        Artifact $artifact,
        PFUser $user,
        Tracker_Artifact_Changeset $new_changeset,
    ): bool {
        if (! $this->areBurndownFieldsCorrectlySet($artifact, $user)) {
            return false;
        }

        $start_date_field = $this->configuration_field_retriever->getStartDateField($artifact->getTracker(), $user);
        if ($this->hasFieldChanged($new_changeset, $start_date_field)) {
            return true;
        }

        if ($this->configuration_field_retriever->doesEndDateFieldExist($artifact->getTracker(), $user)) {
            return $this->hasFieldChanged(
                $new_changeset,
                $this->configuration_field_retriever->getEndDateField($artifact->getTracker(), $user)
            );
        }

        $duration_field = $this->configuration_field_retriever->getDurationField($artifact->getTracker(), $user);
        return $this->hasFieldChanged($new_changeset, $duration_field);
    }

    /**
     * @return bool
     */
    public function doesUserCanReadRemainingEffort(Artifact $artifact, PFUser $user)
    {
        $remaining_effort_field = $this->configuration_field_retriever->getBurndownRemainingEffortField(
            $artifact,
            $user
        );

        return $remaining_effort_field !== null;
    }
}
