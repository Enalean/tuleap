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

use PFUser;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_BurndownException;

class BurndownConfigurationValueChecker
{
    /**
     * @var BurndownConfigurationFieldRetriever
     */
    private $configuration_field_retriever;
    /**
     * @var BurndownConfigurationValueRetriever
     */
    private $configuration_value_retriever;

    public function __construct(
        BurndownConfigurationFieldRetriever $configuration_field_retriever,
        BurndownConfigurationValueRetriever $configuration_value_retriever
    ) {
        $this->configuration_field_retriever = $configuration_field_retriever;
        $this->configuration_value_retriever = $configuration_value_retriever;
    }

    public function hasStartDate(Tracker_Artifact $artifact, PFUser $user)
    {
        $start_date_field = $this->configuration_field_retriever->getBurndownStartDateField($artifact, $user);
        $artifact_value   = $artifact->getValue($start_date_field);

        if ($artifact_value === null) {
            return false;
        }

        $timestamp = $artifact_value->getTimestamp();

        return $timestamp !== null;
    }

    private function hasFieldChanged(
        Tracker_Artifact_Changeset $new_changeset,
        Tracker_FormElement_Field $field
    ) {
        return $new_changeset->getValue($field) && $new_changeset->getValue($field)->hasChanged();
    }

    public function areBurndownFieldsCorrectlySet(Tracker_Artifact $artifact, PFUser $user)
    {
        try {
            return $this->configuration_value_retriever->getBurndownDuration($artifact, $user) !== null
                && $this->configuration_value_retriever->getBurndownStartDate($artifact, $user) !== null;
        } catch (Tracker_FormElement_Field_BurndownException $e) {
            return false;
        }
    }

    public function hasConfigurationChange(
        Tracker_Artifact $artifact,
        PFUser $user,
        Tracker_Artifact_Changeset $new_changeset
    ) {
        $start_date_field = $this->configuration_field_retriever->getBurndownStartDateField($artifact, $user);
        $duration_field   = $this->configuration_field_retriever->getBurndownDurationField($artifact, $user);

        return $this->hasFieldChanged($new_changeset, $start_date_field)
            || $this->hasFieldChanged($new_changeset, $duration_field);
    }

    /**
     * @return Boolean
     */
    public function doesUserCanReadRemainingEffort(Tracker_Artifact $artifact, PFUser $user)
    {
        $remaining_effort_field = $this->configuration_field_retriever->getBurndownRemainingEffortField(
            $artifact,
            $user
        );

        return $remaining_effort_field !== null;
    }
}
