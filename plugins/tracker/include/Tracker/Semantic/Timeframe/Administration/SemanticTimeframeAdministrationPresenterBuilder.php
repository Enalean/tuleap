<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Timeframe\Administration;

use Tracker;
use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;
use Tuleap\Tracker\Semantic\Timeframe\Events\DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent;
use Tuleap\Tracker\Semantic\Timeframe\Events\GetSemanticTimeframeUsageEvent;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever;

class SemanticTimeframeAdministrationPresenterBuilder
{
    public function __construct(
        private \Tracker_FormElementFactory $tracker_formelement_factory,
        private SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever $suitable_trackers_retriever,
        private \EventManager $event_manager,
        private readonly CheckEventShouldBeSentInNotification $calendar_event_config,
    ) {
    }

    public function build(
        \CSRFSynchronizerToken $csrf,
        Tracker $tracker,
        string $target_url,
        SemanticTimeframeCurrentConfigurationPresenter $configuration_presenter,
        IComputeTimeframes $timeframe,
    ): SemanticTimeframeAdministrationPresenter {
        $event = new GetSemanticTimeframeUsageEvent();
        $this->event_manager->processEvent($event);
        return new SemanticTimeframeAdministrationPresenter(
            $csrf,
            $tracker,
            $target_url,
            $this->doesTrackerHaveCharts($tracker),
            $this->hasTrackerAnArtifactLinkField($tracker),
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['date']),
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['int', 'float', 'computed']),
            $timeframe,
            $configuration_presenter,
            $this->getSuitableTrackersSelectBoxEntries($tracker),
            $event->getSemanticUsage(),
            $this->calendar_event_config->shouldSendEventInNotification($tracker->getId()),
        );
    }

    private function doesTrackerHaveCharts(Tracker $tracker): bool
    {
        $event = new DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent($tracker);

        $chart_fields = $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, [
            'burnup',
            'burndown',
        ]);

        return count($chart_fields) > 0 || $event->doesAPluginRenderAChartForTracker();
    }

    private function hasTrackerAnArtifactLinkField(Tracker $tracker): bool
    {
        $artifact_link_field = $this->tracker_formelement_factory->getUsedArtifactLinkFields($tracker);
        return ! empty($artifact_link_field);
    }

    /**
     * @psalm-return array<int, array{name: string, id: int}>
     */
    private function getSuitableTrackersSelectBoxEntries(Tracker $tracker): array
    {
        $select_box_entries = [];
        $suitable_trackers  = $this->suitable_trackers_retriever->getTrackersWeCanUseToImplyTheSemanticOfTheCurrentTrackerFrom($tracker);
        foreach ($suitable_trackers as $suitable_tracker) {
            $select_box_entries[] = [
                'name' => $suitable_tracker->getName(),
                'id'   => $suitable_tracker->getId(),
            ];
        }

        return $select_box_entries;
    }
}
