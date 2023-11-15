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

namespace Tuleap\AgileDashboard\FormElement\SystemEvent;

use DateTime;
use Psr\Log\LoggerInterface;
use SystemEvent;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDao;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDateRetriever;
use Tuleap\AgileDashboard\FormElement\BurnupCalculator;
use Tuleap\AgileDashboard\FormElement\BurnupDataDAO;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class SystemEvent_BURNUP_GENERATE extends SystemEvent // @codingStandardsIgnoreLine
{
    public BurnupCalculator $burnup_calculator;
    private BurnupDataDAO $burnup_dao;
    private LoggerInterface $logger;
    private BurnupCacheDao $cache_dao;
    private BurnupCacheDateRetriever $date_retriever;
    private Tracker_ArtifactFactory $artifact_factory;
    private SemanticTimeframeBuilder $semantic_timeframe_builder;
    private CountElementsCalculator $burnup_count_elements_calculator;
    private CountElementsCacheDao $count_elements_cache_dao;
    private \PlanningFactory $planning_factory;
    private PlanningDao $planning_dao;

    public function injectDependencies(
        Tracker_ArtifactFactory $artifact_factory,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        BurnupDataDAO $burnup_dao,
        BurnupCalculator $burnup_calculator,
        CountElementsCalculator $burnup_count_elements_calculator,
        BurnupCacheDao $cache_dao,
        CountElementsCacheDao $count_elements_cache_dao,
        LoggerInterface $logger,
        BurnupCacheDateRetriever $date_retriever,
        PlanningDao $planning_dao,
        \PlanningFactory $planning_factory,
    ): void {
        $this->artifact_factory                 = $artifact_factory;
        $this->semantic_timeframe_builder       = $semantic_timeframe_builder;
        $this->burnup_dao                       = $burnup_dao;
        $this->logger                           = $logger;
        $this->burnup_calculator                = $burnup_calculator;
        $this->burnup_count_elements_calculator = $burnup_count_elements_calculator;
        $this->cache_dao                        = $cache_dao;
        $this->count_elements_cache_dao         = $count_elements_cache_dao;
        $this->date_retriever                   = $date_retriever;
        $this->planning_dao                     = $planning_dao;
        $this->planning_factory                 = $planning_factory;
    }

    private function getArtifactIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();

        return $parameters[0];
    }

    public function verbalizeParameters($with_link)
    {
        return 'Artifact_id : ' . $this->getArtifactIdFromParameters();
    }

    public function process()
    {
        $artifact_id = $this->getArtifactIdFromParameters();
        $artifact    = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact === null) {
            $this->warning("Unable to find artifact " . $artifact_id);

            return false;
        }

        $burnup_information = null;
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($artifact->getTracker());
        $start_date_field   = $semantic_timeframe->getStartDateField();
        $duration_field     = $semantic_timeframe->getDurationField();
        $end_date_field     = $semantic_timeframe->getEndDateField();
        if ($start_date_field !== null && $duration_field !== null) {
            $burnup_information = $this->burnup_dao->getBurnupInformationBasedOnDuration(
                $artifact_id,
                $start_date_field->getId(),
                $duration_field->getId()
            );
        } elseif ($start_date_field !== null && $end_date_field !== null) {
            $burnup_information = $this->burnup_dao->getBurnupInformationBasedOnEndDate(
                $artifact_id,
                $start_date_field->getId(),
                $end_date_field->getId()
            );
        }

        $this->logger->debug("Calculating burnup for artifact #" . $artifact_id);
        if (! $burnup_information) {
            $warning = "Can't generate cache for artifact #" . $artifact_id . ". Please check your burnup configuration";
            $this->warning($warning);
            $this->logger->debug($warning);

            return false;
        }

        $burnup_period = null;
        if (isset($burnup_information['end_date'])) {
            $burnup_period = DatePeriodWithoutWeekEnd::buildFromEndDate(
                $burnup_information['start_date'],
                $burnup_information['end_date'],
                $this->logger
            );
        } elseif (isset($burnup_information['duration'])) {
            $burnup_period = DatePeriodWithoutWeekEnd::buildFromDuration(
                $burnup_information['start_date'],
                $burnup_information['duration']
            );
        }

        if ($burnup_period === null) {
            $warning = "Skipped cache for artifact #" . $artifact_id . ". Not able to compute burnup period.";
            $this->warning($warning);
            $this->logger->debug($warning);
            return false;
        }

        $yesterday = new DateTime();
        $yesterday->setTime(23, 59, 59);

        $this->cache_dao->deleteArtifactCacheValue(
            $burnup_information['id']
        );

        $planning_infos = $this->planning_dao->searchByMilestoneTrackerId($artifact->getTrackerId());
        if (! $planning_infos) {
            $warning = "Artifact artifact #" . $artifact_id . " does not belong to a planning";
            $this->warning($warning);
            $this->logger->debug($warning);
            return false;
        }

        $backlog_trackers_ids = $this->planning_factory->getBacklogTrackersIds($planning_infos['id']);
        foreach ($this->date_retriever->getWorkedDaysToCacheForPeriod($burnup_period, $yesterday) as $worked_day) {
            $this->logger->debug("Day " . date("Y-m-d H:i:s", $worked_day));

            $effort       = $this->burnup_calculator->getValue($burnup_information['id'], $worked_day, $backlog_trackers_ids);
            $team_effort  = $effort->getTeamEffort();
            $total_effort = $effort->getTotalEffort();

            $this->logger->debug("Caching value $team_effort/$total_effort for artifact #" . $burnup_information['id']);
            $this->cache_dao->saveCachedFieldValueAtTimestamp(
                $burnup_information['id'],
                $worked_day,
                $total_effort,
                $team_effort
            );


            $subelements_cache_info = $this->burnup_count_elements_calculator->getValue(
                $burnup_information['id'],
                $worked_day,
                $backlog_trackers_ids
            );

            $closed_subelements = $subelements_cache_info->getClosedElements();
            $total_subelements  = $subelements_cache_info->getTotalElements();

            $this->logger->debug("Caching subelements value $closed_subelements/$total_subelements for artifact #" . $burnup_information['id']);
            $this->count_elements_cache_dao->saveCachedFieldValueAtTimestampForSubelements(
                $burnup_information['id'],
                (int) $worked_day,
                (int) $total_subelements,
                (int) $closed_subelements
            );
        }

        $this->logger->debug("End calculs for artifact #" . $artifact_id);
        $this->done();

        return true;
    }
}
