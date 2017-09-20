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

use Codendi_HTMLPurifier;
use Tracker;
use Tracker_FormElement_Field_Burndown;
use Tracker_HierarchyFactory;

class BurndownMessageFetcher
{
    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    /**
     * @var BurndownConfigurationValueChecker
     */
    private $configuration_field_retriever;

    public function __construct(
        Tracker_HierarchyFactory $hierarchy_factory,
        BurndownConfigurationFieldRetriever $configuration_field_retriever
    ) {
        $this->hierarchy_factory             = $hierarchy_factory;
        $this->configuration_field_retriever = $configuration_field_retriever;
    }

    /**
     * @return String
     */
    public function fetchWarnings(Tracker $tracker)
    {
        $warnings = '';
        $warnings .= $this->fetchMissingFieldWarning(
            $tracker,
            Tracker_FormElement_Field_Burndown::START_DATE_FIELD_NAME,
            'date'
        );
        $warnings .= $this->fetchMissingFieldWarning(
            $tracker,
            Tracker_FormElement_Field_Burndown::DURATION_FIELD_NAME,
            'int'
        );
        $warnings .= $this->fetchMissingRemainingEffortWarning($tracker);

        if ($warnings) {
            return '<ul class="feedback_warning">' . $warnings . '</ul>';
        }

        return '';
    }

    /**
     * @return String
     */
    private function fetchMissingFieldWarning(Tracker $tracker, $name, $type)
    {
        if (! $tracker->hasFormElementWithNameAndType($name, $type)) {
            $key     = "burndown_missing_${name}_warning";
            $warning = $GLOBALS['Language']->getText('plugin_tracker', $key);

            return '<li>' . $warning . '</li>';
        }

        return '';
    }

    /**
     * @return String
     */
    private function fetchMissingRemainingEffortWarning(Tracker $tracker)
    {
        $tracker_links = implode(', ', $this->getLinksToChildTrackersWithoutRemainingEffort($tracker));

        if ($tracker_links) {
            $warning = $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_remaining_effort_warning');

            return "<li>$warning $tracker_links.</li>";
        }

        return '';
    }

    /**
     * @return array of String
     */
    private function getLinksToChildTrackersWithoutRemainingEffort(Tracker $tracker)
    {
        return array_map(
            array($this, 'getLinkToTracker'),
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
            array($this->configuration_field_retriever, 'doesRemainingEffortFieldExists')
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
     * @param Tracker $tracker
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
