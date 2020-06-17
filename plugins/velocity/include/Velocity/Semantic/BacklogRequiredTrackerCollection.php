<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use Tracker;

class BacklogRequiredTrackerCollection
{
    /**
     * @var bool
     */
    private $is_a_top_backlog_planning = false;
    /**
     * @var int
     */
    private $nb_trackers_without_done_semantic = 0;
    /**
     * @var int
     */
    private $nb_trackers_without_initial_effort_semantic = 0;
    /**
     * @var RequiredTrackerPresenter[]
     */
    private $backlog_trackers = [];
    /**
     * @var Tracker[]
     */
    private $misconfigured_backlog_trackers = [];
    /**
     * @var BacklogRequiredTrackerCollectionFormatter
     */
    private $formatter;

    public function __construct(BacklogRequiredTrackerCollectionFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function areAllBacklogTrackersMisconfigured()
    {
        return $this->areAllBacklogTrackersMissingDoneSemantic()
            || $this->areAllBacklogTrackersMissingInitialEffortSemantic();
    }

    public function addBacklogRequiredTracker(BacklogRequiredTracker $required_tracker)
    {
        $misconfigured = [];
        $is_misconfigured = false;

        if ($required_tracker->isDoneSemanticMissing()) {
            $is_misconfigured = true;
            $misconfigured[] = $this->formatter->formatTrackerWithoutDoneSemantic($required_tracker->getTracker());
            $this->nb_trackers_without_done_semantic++;
        }

        if ($required_tracker->isInitialEffortSemanticMissing()) {
            $is_misconfigured = true;
            $misconfigured[] = $this->formatter->formatTrackerWithoutInitialEffortSemantic($required_tracker->getTracker());
            $this->nb_trackers_without_initial_effort_semantic++;
        }

        $tracker_presenter = new RequiredTrackerPresenter();
        $tracker_presenter->build($required_tracker->getTracker(), $misconfigured);

        $this->backlog_trackers[] = $tracker_presenter;

        if ($is_misconfigured) {
            $this->misconfigured_backlog_trackers[] = $tracker_presenter;
        }
    }

    public function getMisconfiguredBacklogTrackers()
    {
        return $this->misconfigured_backlog_trackers;
    }

    /**
     * @return RequiredTrackerPresenter[]
     */
    public function getBacklogRequiredTrackers()
    {
        return $this->backlog_trackers;
    }

    public function getSemanticMisconfiguredForAllTrackers()
    {
        $misconfigured_semantic_names = [];
        if ($this->areAllBacklogTrackersMissingDoneSemantic()) {
            $misconfigured_semantic_names[] = dgettext('tuleap-agiledashboard', 'Done');
        }

        if ($this->areAllBacklogTrackersMissingInitialEffortSemantic()) {
            $misconfigured_semantic_names[] = dgettext('tuleap-agiledashboard', 'Initial Effort');
        }

        return $misconfigured_semantic_names;
    }

    /**
     * @return bool
     */
    private function areAllBacklogTrackersMissingDoneSemantic()
    {
        return count($this->backlog_trackers) === $this->nb_trackers_without_done_semantic;
    }

    /**
     * @return bool
     */
    private function areAllBacklogTrackersMissingInitialEffortSemantic()
    {
        return count($this->backlog_trackers) === $this->nb_trackers_without_initial_effort_semantic;
    }

    public function getNbMisconfiguredTrackers()
    {
        return count($this->misconfigured_backlog_trackers);
    }

    /**
     * @return bool
     */
    public function isATopBacklogPlanning()
    {
        return $this->is_a_top_backlog_planning;
    }

    /**
     * @param bool $is_a_top_backlog_planning
     */
    public function setIsATopBacklogPlanning($is_a_top_backlog_planning)
    {
        $this->is_a_top_backlog_planning = $is_a_top_backlog_planning;
    }
}
