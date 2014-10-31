<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class SystemEvent_FULLTEXTSEARCH_TRACKER_REINDEX_PROJECT extends SystemEvent {

    const NAME = 'FULLTEXTSEARCH_TRACKER_REINDEX_PROJECT';

    /**
     * @var FullTextSearchTrackerActions
     */
    private $actions;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function injectDependencies(FullTextSearchTrackerActions $actions, TrackerFactory $tracker_factory) {
        $this->actions         = $actions;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * Process the system event
     *
     * @return Boolean
     */
    public function process() {
        try {
            $project_id = (int)$this->getRequiredParameter(0);
            $trackers   = $this->tracker_factory->getTrackersByGroupId($project_id);

            $this->actions->reIndexProjectArtifacts($trackers);

            $this->done();
            return true;
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        return false;
    }

    private function action($project_id) {

    }

    /**
     * @return string a human readable representation of parameters
     */
    public function verbalizeParameters($withLink) {
        $txt = '';

        try {
            $group_id = (int)$this->getRequiredParameter(0);
            $txt      = 'Project: '. $this->verbalizeProjectId($group_id, $withLink);

        } catch (Exception $e) {
            return $e->getMessage();
        }

        return $txt;
    }
}
