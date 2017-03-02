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
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_BurndownException;
use Tracker_FormElementFactory;

class BurndownConfigurationFieldRetriever
{
    const REMAINING_EFFORT_FIELD_NAME = 'remaining_effort';
    const DURATION_FIELD_NAME         = 'duration';
    const START_DATE_FIELD_NAME       = 'start_date';
    const CAPACITY_FIELD_NAME         = 'capacity';

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_field_factory;

    public function __construct(Tracker_FormElementFactory $form_element_field_factory)
    {
        $this->form_element_field_factory = $form_element_field_factory;
    }

    /**
     * @throws Tracker_FormElement_Field_BurndownException
     * @return Tracker_FormElement_Field
     */
    public function getCapacityField(Tracker $tracker)
    {
        $field = $this->form_element_field_factory->getUsedFieldByName(
            $tracker->getId(),
            self::CAPACITY_FIELD_NAME
        );

        if (! $field) {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_capacity_warning')
            );
        }

        return $field;
    }

    /**
     * @throws Tracker_FormElement_Field_BurndownException
     * @return Tracker_FormElement_Field
     */
    public function getBurndownDurationField(Tracker_Artifact $artifact, PFUser $user)
    {
        $field = $this->form_element_field_factory->getUsedFieldByNameForUser(
            $artifact->getTracker()->getId(),
            self::DURATION_FIELD_NAME,
            $user
        );

        if (! $field) {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_duration_warning')
            );
        }

        return $field;
    }

    /**
     * @throws Tracker_FormElement_Field_BurndownException
     */
    public function getBurndownStartDateField(Tracker_Artifact $artifact, PFUser $user)
    {
        $start_date_field = $this->form_element_field_factory->getUsedFieldByNameForUser(
            $artifact->getTracker()->getId(),
            self::START_DATE_FIELD_NAME,
            $user
        );

        if (! $start_date_field) {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
            );
        }

        return $start_date_field;
    }

    public function getBurndownRemainingEffortField(Tracker_Artifact $artifact, PFUser $user)
    {
        return $this->form_element_field_factory->getComputableFieldByNameForUser(
            $artifact->getTracker()->getId(),
            self::REMAINING_EFFORT_FIELD_NAME,
            $user
        );
    }

    /**
     * @return bool
     */
    public function doesCapacityFieldExist(Tracker $tracker)
    {
        try {
            $this->getCapacityField($tracker);
            return true;
        } catch (Tracker_FormElement_Field_BurndownException $e) {
            return false;
        }
    }
}
