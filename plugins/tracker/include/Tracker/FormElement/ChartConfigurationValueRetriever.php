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

use Logger;
use PFUser;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_FormElement_Chart_Field_Exception;

class ChartConfigurationValueRetriever
{
    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $configuration_field_retriever;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(ChartConfigurationFieldRetriever $configuration_field_retriever, Logger $logger)
    {
        $this->configuration_field_retriever = $configuration_field_retriever;
        $this->logger                        = $logger;
    }

    /**
     * @param Tracker_Artifact $artifact
     *
     * @return null|int
     */
    public function getCapacity(Tracker_Artifact $artifact, PFUser $user)
    {
        try {
            $field = $this->configuration_field_retriever->getCapacityField($artifact->getTracker());
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            $this->logger->info("Artifact " . $artifact->getId() . " no capacity retrieved");

            return null;
        }

        $artifact_list = array($artifact->getId());

        return $field->getComputedValue($user, $artifact, null, $artifact_list, true);
    }

    /**
     * @return TimePeriodWithoutWeekEnd
     *
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getTimePeriod(Tracker_Artifact $artifact, PFUser $user) : TimePeriodWithoutWeekEnd
    {
        $start_date = $this->getStartDate($artifact, $user);
        $duration = $this->getDuration($artifact, $user);

        return new TimePeriodWithoutWeekEnd($start_date, $duration);
    }

    /**
     * @return int
     *
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getDuration(Tracker_Artifact $artifact, PFUser $user)
    {
        $field          = $this->configuration_field_retriever->getDurationField($artifact, $user);
        $duration_value = $artifact->getValue($field);

        if ($duration_value === null) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_duration_warning')
            );
        }

        assert($duration_value instanceof \Tracker_Artifact_ChangesetValue_Numeric);

        $duration = $duration_value->getValue();

        if ($duration <= 0) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_duration_warning')
            );
        }

        if ($duration === 1) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_duration_too_short')
            );
        }

        return (int) $duration;
    }

    /**
     * @param Tracker_Artifact $artifact
     *
     * @return int|null
     *
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getStartDate(Tracker_Artifact $artifact, PFUser $user)
    {
        $start_date_field = $this->configuration_field_retriever->getStartDateField($artifact, $user);
        $start_date_value = $artifact->getValue($start_date_field);

        if (! $start_date_value) {
            return null;
        }

        assert($start_date_value instanceof Tracker_Artifact_ChangesetValue_Date);

        $timestamp = $start_date_value->getTimestamp();

        if (! $timestamp) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_start_date_warning')
            );
        }

        return (int) $timestamp;
    }
}
