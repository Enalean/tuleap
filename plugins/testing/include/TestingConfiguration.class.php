<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

/**
 * Routes request to the desired controller
 */
class TestingConfiguration {

    /** @var Project */
    private $project;

    public function __construct(Project $project) {
        $this->project = $project;
    }

    public function getDefectTracker() {
        return $this->getTracker('defect');
    }

    public function getRequirementTracker() {
        return $this->getTracker('requirement');
    }

    public function getTestCaseTracker() {
        return $this->getTracker('testcase');
    }

    public function getReleaseTracker() {
        return $this->getTracker('releases');
    }

    private function getTracker($item_name) {
        $tracker = TrackerFactory::instance()->getTrackerByItemName($this->project, $item_name);
        if (! $tracker) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                'Configuration error: '. $item_name .' is missing'
            );
        }

        return $tracker;
    }
}
