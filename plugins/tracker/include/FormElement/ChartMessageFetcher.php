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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_HierarchyFactory;
use Tuleap\Tracker\FormElement\Event\ExternalTrackerChartConfigurationWarningMessage;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Tracker;
use Tuleap\User\ProvideCurrentUser;

final readonly class ChartMessageFetcher
{
    public function __construct(
        private Tracker_HierarchyFactory $hierarchy_factory,
        private ChartConfigurationFieldRetriever $configuration_field_retriever,
        private EventDispatcherInterface $event_manager,
        private ProvideCurrentUser $current_user_provider,
    ) {
    }

    public function fetchWarnings(TrackerField $field, ChartFieldUsage $usage): ChartConfigurationWarningCollection
    {
        $tracker = $field->getTracker();
        assert($tracker instanceof Tracker);
        $user = $this->current_user_provider->getCurrentUser();

        $warnings = new ChartConfigurationWarningCollection();
        if ($usage->uses_start_date) {
            try {
                $this->configuration_field_retriever->getStartDateField($tracker, $user);
            } catch (Tracker_FormElement_Chart_Field_Exception $e) {
                $warnings->addWarning(ChartConfigurationWarning::fromMessage($e->getMessage()));
            }
        }

        if ($usage->uses_duration) {
            try {
                $this->configuration_field_retriever->getDurationField($tracker, $user);
            } catch (Tracker_FormElement_Chart_Field_Exception $exception_duration) {
                try {
                    $this->configuration_field_retriever->getEndDateField($tracker, $user);
                } catch (Tracker_FormElement_Chart_Field_Exception $exception_end_date) {
                    $warnings->addWarning(ChartConfigurationWarning::fromMessage($exception_duration->getMessage()));
                    $warnings->addWarning(ChartConfigurationWarning::fromMessage($exception_end_date->getMessage()));
                }
            }
        }

        if ($usage->uses_capacity) {
            $this->fetchMissingCapacityFieldWarning(
                $warnings,
                $tracker,
            );
        }

        if ($usage->uses_remaining_effort) {
            $this->fetchMissingRemainingEffortWarning($warnings, $tracker);
        }

        $event = new ExternalTrackerChartConfigurationWarningMessage($warnings, $user, $field);
        $this->event_manager->dispatch($event);

        return $warnings;
    }

    public function fetchMissingCapacityFieldWarning(ChartConfigurationWarningCollection $warnings, Tracker $tracker): void
    {
        try {
            $this->configuration_field_retriever->getCapacityField($tracker);
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            $warnings->addWarning(ChartConfigurationWarning::fromMessage($e->getMessage()));
        }
    }

    private function fetchMissingRemainingEffortWarning(ChartConfigurationWarningCollection $warnings, Tracker $tracker): void
    {
        $tracker_links = $this->getLinksToChildTrackersWithoutRemainingEffort($tracker);
        if (count($tracker_links) === 0) {
            return;
        }

        $warnings->addWarning(
            ChartConfigurationWarningWithLinks::fromMessageAndLinks(
                dgettext('tuleap-tracker', 'Some child trackers don\'t have a "remaining_effort" Integer or Float or Computed field:'),
                ...$tracker_links,
            )
        );
    }

    /**
     * @return ChartConfigurationWarningLink[]
     */
    private function getLinksToChildTrackersWithoutRemainingEffort(Tracker $tracker): array
    {
        return array_map(
            function (Tracker $tracker): ChartConfigurationWarningLink {
                return $this->getLinkToTracker($tracker);
            },
            $this->getChildTrackersWithoutRemainingEffort($tracker)
        );
    }

    /**
     * @return Tracker[]
     */
    private function getChildTrackersWithoutRemainingEffort(Tracker $tracker): array
    {
        return array_filter(
            $this->getChildTrackers($tracker),
            fn(Tracker $child_tracker) => ! $this->configuration_field_retriever->doesRemainingEffortFieldExists($child_tracker),
        );
    }

    /**
     * @return Tracker[]
     */
    protected function getChildTrackers(Tracker $tracker): array
    {
        return $this->hierarchy_factory->getChildren($tracker->getId());
    }

    private function getLinkToTracker(Tracker $tracker): ChartConfigurationWarningLink
    {
        $tracker_id   = $tracker->getId();
        $tracker_name = $tracker->getName();
        $tracker_url  = \trackerPlugin::TRACKER_BASE_URL . '/?' . http_build_query(['tracker' => $tracker_id, 'func' => 'admin-formElements']);

        return new ChartConfigurationWarningLink(
            $tracker_url,
            $tracker_name,
        );
    }
}
