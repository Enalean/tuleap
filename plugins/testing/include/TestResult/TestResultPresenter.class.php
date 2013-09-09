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

class Testing_TestResult_TestResultPresenter {

    public function __construct(Testing_TestResult_TestResult $result) {
        $hp = Codendi_HTMLPurifier::instance();
        $this->executed_by = UserHelper::instance()->getDisplayNameFromUser($result->getExecutedBy());
        $this->executed_on = DateHelper::formatForLanguage($GLOBALS['Language'], $result->getExecutedOn());
        if (! $this->executed_on) {
            //be sure that we don't get a null for mustache
            $this->executed_on = false;
        }
        $project_id = 1; //TODO: get the real project
        $this->message = $hp->purify($result->getMessage(), CODENDI_PURIFIER_BASIC, $project_id);

        $this->is_passed        = $result->getStatus() == Testing_TestResult_TestResult::PASS;
        $this->is_failed        = $result->getStatus() == Testing_TestResult_TestResult::FAIL;
        $this->is_not_run       = $result->getStatus() == Testing_TestResult_TestResult::NOT_RUN;
        $this->is_not_completed = $result->getStatus() == Testing_TestResult_TestResult::NOT_COMPLETED;

        $this->passed_value        = Testing_TestResult_TestResult::PASS;
        $this->failed_value        = Testing_TestResult_TestResult::FAIL;
        $this->not_run_value       = Testing_TestResult_TestResult::NOT_RUN;
        $this->not_completed_value = Testing_TestResult_TestResult::NOT_COMPLETED;
    }
}
