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

class Testing_TestExecution_TestExecution {

    /** @var int */
    private $id;

    /** @var Testing_Campaign_Campaign */
    private $campaign;

    /** @var PFUser */
    private $assignee;

    /** @var Testing_TestResult_TestResultCollection */
    private $list_of_test_results;

    public function __construct($id, Testing_Campaign_Campaign $campaign, PFUser $assignee, array $list_of_test_results) {
        $this->id                   = $id;
        $this->campaign             = $campaign;
        $this->assignee             = $assignee;
        $this->list_of_test_results = $list_of_test_results;
    }

    public function getId() { return $this->id; }
    public function getAssignee() { return $this->assignee; }
    public function getCampaign() { return $this->campaign; }
    public function getLastTestResult() { return end($this->list_of_test_results); }
}
