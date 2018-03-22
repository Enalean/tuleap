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

use Logger;
use PFUser;
use Tracker_Artifact;
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
     * @return Integer
     *
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getDuration(Tracker_Artifact $artifact, PFUser $user)
    {
        $field = $this->configuration_field_retriever->getDurationField($artifact, $user);

        if ($artifact->getValue($field) === null) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_duration_warning')
            );
        }

        $duration = $artifact->getValue($field)->getValue();

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

        return $duration;
    }

    /**
     * @param Tracker_Artifact $artifact
     *
     * @return Integer
     *
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getStartDate(Tracker_Artifact $artifact, PFUser $user)
    {
        $start_date_field = $this->configuration_field_retriever->getStartDateField($artifact, $user);
        if (! $artifact->getValue($start_date_field)) {
            return;
        }
        $timestamp        = $artifact->getValue($start_date_field)->getTimestamp();

        if (! $timestamp) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_start_date_warning')
            );
        }

        return $timestamp;
    }
}
