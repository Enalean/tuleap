<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\SystemEvent;

use BackendLogger;
use DateTime;
use SystemEvent;
use TimePeriodWithoutWeekEnd;
use Tracker_FormElement_Field_BurndownDao;
use Tracker_FormElement_Field_ComputedDaoCache;
use Tuleap\Tracker\FormElement\BurndownCalculator;

class SystemEvent_BURNDOWN_GENERATE extends SystemEvent
{
    const NAME = 'SystemEvent_BURNDOWN_GENERATE';

    /**
     * @var Tracker_FormElement_Field_BurndownDao
     */
    private $burndown_dao;

    /**
     * @var BackendLogger
     */
    private $logger;

    /**
     * @var  BurndownCalculator
     */
    private $burndown_calculator;

    /**
     * @var Tracker_FormElement_Field_ComputedDaoCache
     */
    private $cache_dao;

    private function getArtifactIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();

        return $parameters[0];
    }

    public function verbalizeParameters($with_link)
    {
        return 'Artifact_id : ' . $this->getArtifactIdFromParameters();
    }

    public function injectDependencies(
        Tracker_FormElement_Field_BurndownDao $burndown_dao,
        BurndownCalculator $burndown_calculator,
        Tracker_FormElement_Field_ComputedDaoCache $cache_dao,
        BackendLogger $logger
    ) {
        $this->burndown_dao        = $burndown_dao;
        $this->logger              = $logger;
        $this->burndown_calculator = $burndown_calculator;
        $this->cache_dao           = $cache_dao;
    }

    public function process()
    {
        $artifact_id           = $this->getArtifactIdFromParameters();
        $burndown_informations = $this->burndown_dao->getBurndownInformation($artifact_id);
        $burndown              = new TimePeriodWithoutWeekEnd(
            $burndown_informations['start_date'],
            $burndown_informations['duration']
        );

        $start_date = new DateTime();
        $start_date->setTimestamp((int) $burndown_informations['start_date']);
        $start_date->setTime(0, 0, 0);

        $end_date = new DateTime();
        $end_date->setTimestamp((int) $burndown_informations['timestamp_end_date']);

        $yesterday = new DateTime();
        $yesterday->setTime(0, 0, 0);

        $this->logger->debug("Calculating burndown for artifact #" . $artifact_id);

        $this->cache_dao->deleteArtifactCacheValue(
            $burndown_informations['id'],
            $burndown_informations['burndown_field_id']
        );

        while ($start_date->getTimestamp() <= $end_date->getTimestamp()
            && $start_date->getTimestamp() <= $yesterday->getTimestamp()
        ) {
            if ($burndown->isNotWeekendDay($start_date->getTimestamp())) {
                $this->logger->debug("Day " . $start_date->format("Y-m-d H:i:s"));

                $value = $this->burndown_calculator->calculateBurndownValueAtTimestamp(
                    $burndown_informations,
                    $start_date->getTimestamp() - 1
                );

                $this->logger->debug("Caching value $value for artifact #" . $burndown_informations['id']);
                $this->cache_dao->saveCachedFieldValueAtTimestamp(
                    $burndown_informations['id'],
                    $burndown_informations['burndown_field_id'],
                    $start_date->getTimestamp() - 1,
                    $value
                );
            }

            $start_date->modify('+1 day');
        }

        $this->logger->debug("End calculs for artifact #" . $artifact_id);
        $this->done();

        return true;
    }
}
