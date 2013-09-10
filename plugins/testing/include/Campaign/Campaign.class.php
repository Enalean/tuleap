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

class Testing_Campaign_Campaign {

    /** @var int */
    private $id;

    /** @var Project */
    private $project;

    /** @var string */
    private $name;

    /** @var Testing_Release_Release */
    private $release;

    /** @var Testing_TestExecution_TestExecutionCollection */
    private $list_of_test_executions;

    public function __construct(
        $id,
        Project $project,
        $name,
        Testing_Release_Release $release,
        Testing_TestExecution_TestExecutionCollection $list_of_test_executions
    ) {
        $this->id                      = $id;
        $this->project                 = $project;
        $this->name                    = $name;
        $this->release                 = $release;
        $this->list_of_test_executions = $list_of_test_executions;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getRelease() { return $this->release; }
    public function getProjectId() { return $this->project->getId(); }
    public function getListOfTestExecutions() { return $this->list_of_test_executions; }
}
