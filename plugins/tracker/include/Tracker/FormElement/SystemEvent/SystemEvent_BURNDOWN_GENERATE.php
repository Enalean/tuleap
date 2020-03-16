<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use DateTime;
use Psr\Log\LoggerInterface;
use SystemEvent;
use TimePeriodWithoutWeekEnd;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_BurndownDao;
use Tracker_FormElement_Field_ComputedDaoCache;
use Tuleap\Tracker\FormElement\BurndownCacheDateRetriever;
use Tuleap\Tracker\FormElement\FieldCalculator;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

class SystemEvent_BURNDOWN_GENERATE extends SystemEvent // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const NAME = 'SystemEvent_BURNDOWN_GENERATE';

    /**
     * @var Tracker_FormElement_Field_BurndownDao
     */
    private $burndown_dao;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var  FieldCalculator
     */
    private $field_calculator;

    /**
     * @var Tracker_FormElement_Field_ComputedDaoCache
     */
    private $cache_dao;

    /**
     * @var BurndownCacheDateRetriever
     */
    private $date_retriever;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;

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
        Tracker_ArtifactFactory $artifact_factory,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        Tracker_FormElement_Field_BurndownDao $burndown_dao,
        FieldCalculator $field_calculator,
        Tracker_FormElement_Field_ComputedDaoCache $cache_dao,
        LoggerInterface $logger,
        BurndownCacheDateRetriever $date_retriever
    ) {
        $this->artifact_factory           = $artifact_factory;
        $this->semantic_timeframe_builder = $semantic_timeframe_builder;
        $this->burndown_dao               = $burndown_dao;
        $this->logger                     = $logger;
        $this->field_calculator           = $field_calculator;
        $this->cache_dao                  = $cache_dao;
        $this->date_retriever             = $date_retriever;
    }

    public function process()
    {
        $artifact_id           = $this->getArtifactIdFromParameters();
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact === null) {
            $this->warning("Unable to find artifact " . $artifact_id);

            return false;
        }

        $burndown_informations = null;
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($artifact->getTracker());
        $start_date_field   = $semantic_timeframe->getStartDateField();
        $end_date_field     = $semantic_timeframe->getEndDateField();
        $duration_field     = $semantic_timeframe->getDurationField();
        if ($start_date_field !== null && $duration_field !== null) {
            $burndown_informations = $this->burndown_dao->getBurndownInformationBasedOnDuration(
                $artifact_id,
                $start_date_field->getId(),
                $duration_field->getId()
            );
        } elseif ($start_date_field !== null && $end_date_field !== null) {
            $burndown_informations = $this->burndown_dao->getBurndownInformationBasedOnEndDate(
                $artifact_id,
                $start_date_field->getId(),
                $end_date_field->getId()
            );
        }

        $this->logger->debug("Calculating burndown for artifact #" . $artifact_id);
        if ($burndown_informations) {
            if (empty($burndown_informations['duration'])) {
                $burndown = TimePeriodWithoutWeekEnd::buildFromEndDate(
                    $burndown_informations['start_date'],
                    $burndown_informations['end_date'],
                    $this->logger
                );
            } else {
                $burndown = TimePeriodWithoutWeekEnd::buildFromDuration(
                    $burndown_informations['start_date'],
                    $burndown_informations['duration']
                );
            }

            $yesterday = new DateTime();
            $yesterday->setTime(0, 0, 0);

            $this->cache_dao->deleteArtifactCacheValue(
                $burndown_informations['id'],
                $burndown_informations['remaining_effort_field_id']
            );

            $yesterday = new DateTime();
            $yesterday->setTime(0, 0, 0);

            foreach ($this->date_retriever->getWorkedDaysToCacheForPeriod($burndown, $yesterday) as $worked_day) {
                $this->logger->debug("Day " . date("Y-m-d H:i:s", $worked_day));

                $value = $this->field_calculator->calculate(
                    array($burndown_informations['id']),
                    $worked_day,
                    true,
                    'remaining_effort',
                    $burndown_informations['remaining_effort_field_id']
                );

                $this->logger->debug("Caching value $value for artifact #" . $burndown_informations['id']);
                $this->cache_dao->saveCachedFieldValueAtTimestamp(
                    $burndown_informations['id'],
                    $burndown_informations['remaining_effort_field_id'],
                    $worked_day,
                    $value
                );
            }
        } else {
            $this->logger->debug("Can't generate cache for artifact #" . $artifact_id . ". Please check your burndown configuration");
        }

        $this->logger->debug("End calculs for artifact #" . $artifact_id);
        $this->done();

        return true;
    }
}
