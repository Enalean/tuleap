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

use Codendi_HTMLPurifier;
use EventManager;
use Tracker;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field;
use Tracker_HierarchyFactory;
use Tuleap\Tracker\FormElement\Event\MessageFetcherAdditionalWarnings;
use Tuleap\User\ProvideCurrentUser;

class ChartMessageFetcher
{
    public function __construct(
        private readonly Tracker_HierarchyFactory $hierarchy_factory,
        private readonly ChartConfigurationFieldRetriever $configuration_field_retriever,
        private readonly EventManager $event_manager,
        private readonly ProvideCurrentUser $current_user_provider,
    ) {
    }

    /**
     * @return string
     */
    public function fetchWarnings(Tracker_FormElement_Field $field, ChartFieldUsage $usage)
    {
        $tracker = $field->getTracker();
        assert($tracker instanceof Tracker);
        $user = $this->current_user_provider->getCurrentUser();

        $warnings = [];
        if ($usage->getUseStartDate()) {
            try {
                $this->configuration_field_retriever->getStartDateField($tracker, $user);
            } catch (Tracker_FormElement_Chart_Field_Exception $e) {
                $warnings[] = '<li>' . $e->getMessage() . '</li>';
            }
        }

        if ($usage->getUseDuration()) {
            try {
                $this->configuration_field_retriever->getDurationField($tracker, $user);
            } catch (Tracker_FormElement_Chart_Field_Exception $exception_duration) {
                try {
                    $this->configuration_field_retriever->getEndDateField($tracker, $user);
                } catch (Tracker_FormElement_Chart_Field_Exception $exception_end_date) {
                    $warnings[] = '<li>' . $exception_duration->getMessage() . '</li>';
                    $warnings[] = '<li>' . $exception_end_date->getMessage() . '</li>';
                }
            }
        }

        if ($usage->getUseCapacity()) {
            $warning_message = $this->fetchMissingCapacityFieldWarning(
                $tracker,
                ChartConfigurationFieldRetriever::CAPACITY_FIELD_NAME,
                ['int', 'computed']
            );
            if ($warning_message !== null) {
                $warnings[] = $warning_message;
            }
        }

        if ($usage->getUseRemainingEffort()) {
            $warning_message = $this->fetchMissingRemainingEffortWarning($tracker);
            if ($warning_message !== null) {
                $warnings[] = $warning_message;
            }
        }

        $event = new MessageFetcherAdditionalWarnings($user, $field);
        $this->event_manager->processEvent($event);

        $warnings = array_merge($warnings, $event->getWarnings());

        if (count($warnings) > 0) {
            return '<ul class="feedback_warning">' . implode('', $warnings) . '</ul>';
        }

        return null;
    }

    public function fetchMissingCapacityFieldWarning(Tracker $tracker, string $name, array $type): ?string
    {
        if (! $tracker->hasFormElementWithNameAndType($name, $type)) {
            $warning = dgettext('tuleap-tracker', 'The tracker doesn\'t have a "capacity" Integer or Computed field or you don\'t have the permission to access it.');

            return '<li>' . $warning . '</li>';
        }

        return null;
    }

    /**
     * @return String
     */
    private function fetchMissingRemainingEffortWarning(Tracker $tracker)
    {
        $tracker_links = implode(', ', $this->getLinksToChildTrackersWithoutRemainingEffort($tracker));
        if ($tracker_links) {
            $warning = dgettext('tuleap-tracker', 'Some child trackers don\'t have a "remaining_effort" Integer or Float or Computed field:');

            return "<li>$warning $tracker_links.</li>";
        }

        return null;
    }

    /**
     * @return array of String
     */
    private function getLinksToChildTrackersWithoutRemainingEffort(Tracker $tracker)
    {
        return array_map(
            function (Tracker $tracker): string {
                return $this->getLinkToTracker($tracker);
            },
            $this->getChildTrackersWithoutRemainingEffort($tracker)
        );
    }

    /**
     * @return array of Tracker
     */
    private function getChildTrackersWithoutRemainingEffort(Tracker $tracker)
    {
        return array_filter(
            $this->getChildTrackers($tracker),
            [$this->configuration_field_retriever, 'doesRemainingEffortFieldExists']
        );
    }

    /**
     * @return array of Tracker
     */
    protected function getChildTrackers(Tracker $tracker)
    {
        return $this->hierarchy_factory->getChildren($tracker->getId());
    }

    /**
     * Renders a link to the given tracker.
     *
     * @return String
     */
    private function getLinkToTracker(Tracker $tracker)
    {
        $tracker_id   = $tracker->getId();
        $tracker_name = $tracker->getName();
        $tracker_url  = TRACKER_BASE_URL . "/?tracker=$tracker_id&func=admin-formElements";

        $hp = Codendi_HTMLPurifier::instance();

        return '<a href="' . $tracker_url . '">' . $hp->purify($tracker_name) . '</a>';
    }
}
