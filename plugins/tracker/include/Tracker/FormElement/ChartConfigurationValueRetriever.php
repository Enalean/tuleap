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
use Psr\Log\LoggerInterface;
use Tracker_FormElement_Chart_Field_Exception;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;

class ChartConfigurationValueRetriever
{
    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $configuration_field_retriever;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var IComputeTimeframes
     */
    private $timeframe_calculator;

    public function __construct(
        ChartConfigurationFieldRetriever $configuration_field_retriever,
        IComputeTimeframes $timeframe_calculator,
        LoggerInterface $logger,
    ) {
        $this->configuration_field_retriever = $configuration_field_retriever;
        $this->timeframe_calculator          = $timeframe_calculator;
        $this->logger                        = $logger;
    }

    /**
     *
     * @return null|int
     */
    public function getCapacity(Artifact $artifact, PFUser $user)
    {
        try {
            $field = $this->configuration_field_retriever->getCapacityField($artifact->getTracker());
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            $this->logger->info("Artifact " . $artifact->getId() . " no capacity retrieved");

            return null;
        }

        $artifact_list = [$artifact->getId()];

        return $field->getComputedValue($user, $artifact, null, $artifact_list, true);
    }

    /**
     *
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getDatePeriod(Artifact $artifact, PFUser $user): DatePeriodWithoutWeekEnd
    {
        return $this->timeframe_calculator->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );
    }
}
