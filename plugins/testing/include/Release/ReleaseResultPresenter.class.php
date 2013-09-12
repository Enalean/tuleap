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

class Testing_Release_ReleaseResultPresenter {

    public function __construct(Project $project, Testing_Release_Release $release, $nb_of_tests) {
        $this->id          = $release->getId();
        $this->name        = $release->getName();
        $this->nb_of_tests = $nb_of_tests;
        $this->show_uri    = '/plugins/testing/?group_id='. $project->getId() .'&resource=release&action=show&id='. $this->id;
        $this->has_one_not_run       = 0;
        $this->has_one_passed        = 0;
        $this->has_one_failed        = 0;
        $this->has_one_not_completed = 0;
    }

    public function is_failed() {
        return $this->has_one_failed > 0;
    }

    public function is_not_completed() {
        return ! $this->is_failed() && $this->has_one_not_completed > 0;
    }

    public function is_not_run() {
        return ! $this->is_failed() && ! $this->is_not_completed() && $this->has_one_not_run > 0;
    }

    public function is_passed() {
        return ! $this->is_failed() && ! $this->is_not_completed() && ! $this->is_not_completed() && $this->has_one_passed > 0;
    }
}
