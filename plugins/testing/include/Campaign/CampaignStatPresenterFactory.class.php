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

class Testing_Campaign_CampaignStatPresenterFactory {

    /** @return Testing_Campaign_CampaignStatPresenter */
    public function getPresenter(Testing_Campaign_Campaign $campaign) {
        $nb_not_run       = 0;
        $nb_pass          = 0;
        $nb_fail          = 0;
        $nb_not_completed = 0;
        $sum_statuses = array(
            'not_run'       => &$nb_not_run,
            'pass'          => &$nb_pass,
            'fail'          => &$nb_fail,
            'not_completed' => &$nb_not_completed
        );

        array_walk($campaign->getListOfTestExecutions(), array($this, 'sumStatus'), $sum_statuses);

        return new Testing_Campaign_CampaignStatPresenter($nb_not_run, $nb_pass, $nb_fail, $nb_not_completed);
    }

    private function sumStatus(Testing_TestExecution_TestExecution $execution, $index, $sum_statuses) {
        $last_result = $execution->getLastTestResult();
        switch ($last_result->getStatus()) {
            case Testing_TestResult_TestResult::PASS:
                $sum_statuses['pass']++;
                break;
            case Testing_TestResult_TestResult::FAIL:
                $sum_statuses['fail']++;
                break;
            case Testing_TestResult_TestResult::NOT_RUN:
                $sum_statuses['not_run']++;
                break;
            case Testing_TestResult_TestResult::NOT_COMPLETED:
                $sum_statuses['not_completed']++;
                break;
            default:
                break;
        }
    }
}
