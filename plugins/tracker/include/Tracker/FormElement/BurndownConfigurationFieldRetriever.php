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
use Tracker_FormElement_InvalidFieldException;
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
    /**
     * @var \Logger
     */
    private $logger;

    public function __construct(Tracker_FormElementFactory $form_element_field_factory, \Logger $logger)
    {
        $this->form_element_field_factory = $form_element_field_factory;
        $this->logger                     = $logger;
    }

    /**
     * @param Tracker $tracker
     * @return Tracker_FormElement_Field
     */
    public function getCapacityField(Tracker $tracker)
    {
        $field = $this->form_element_field_factory->getNumericFieldByName(
            $tracker,
            self::CAPACITY_FIELD_NAME
        );

        if (! $field) {
            $this->logger->info("Tracker " . $tracker->getName() . " doesn't have a capacity field (or field is not properly set)");
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_capacity_warning')
            );
        }

        return $field;
    }

    /**
     * @param Tracker_Artifact $artifact
     * @param PFUser $user
     * @return Tracker_FormElement_Field
     */
    public function getBurndownDurationField(Tracker_Artifact $artifact, PFUser $user)
    {
        $field = $this->form_element_field_factory->getNumericFieldByNameForUser(
            $artifact->getTracker(),
            $user,
            self::DURATION_FIELD_NAME
        );

        if (! $field) {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_duration_warning')
            );
        }

        return $field;
    }

    /**
     * @param Tracker_Artifact $artifact
     * @param PFUser $user
     * @return bool|Tracker_FormElement_Field
     */
    public function getBurndownStartDateField(Tracker_Artifact $artifact, PFUser $user)
    {
        $field = $this->form_element_field_factory->getDateFieldByNameForUser(
            $artifact->getTracker(),
            $user,
            self::START_DATE_FIELD_NAME
        );

        if (! $field) {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
            );
        }

        return $field;
    }

    /**
     * @param Tracker_Artifact $artifact
     * @param PFUser $user
     * @return bool|Tracker_FormElement_Field
     */
    public function getBurndownRemainingEffortField(Tracker_Artifact $artifact, PFUser $user)
    {
        return $this->form_element_field_factory->getNumericFieldByNameForUser(
            $artifact->getTracker(),
            $user,
            self::REMAINING_EFFORT_FIELD_NAME
        );
    }

    /**
     * @param Tracker $tracker
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
