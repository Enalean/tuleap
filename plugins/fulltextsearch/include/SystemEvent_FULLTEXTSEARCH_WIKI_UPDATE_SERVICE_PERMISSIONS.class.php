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

class SystemEvent_FULLTEXTSEARCH_WIKI_UPDATE_SERVICE_PERMISSIONS extends SystemEvent {

    const NAME = 'FULLTEXTSEARCH_WIKI_UPDATE_SERVICE_PERMISSIONS';

    /**
     * @var FullTextSearchWikiActions
     */
    protected $actions;

    public function injectDependencies(FullTextSearchWikiActions $actions) {
        $this->actions = $actions;
    }

    /**
     * @return string a human readable representation of parameters
     */
    public function verbalizeParameters($with_link) {
        $group_id = (int)$this->getRequiredParameter(0);
        return 'project: '. $this->verbalizeProjectId($group_id, $with_link);
    }

    /**
     * Process the system event
     *
     * @return bool
     */
    public function process() {
        try {
            $project_id = (int) $this->getRequiredParameter(0);

            $this->actions->reIndexProjectWikiPages($project_id);

            $this->done();
            return true;

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        return false;
    }
}
