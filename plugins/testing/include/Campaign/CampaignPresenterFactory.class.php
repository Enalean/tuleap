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

class Testing_Campaign_CampaignPresenterFactory {

    /** @var Testing_Campaign_CampaignStatPresenterFactory */
    private $stat_presenter_factory;

    /** @var Testing_TestExecution_TestExecutionInfoPresenterFactory */
    private $execution_info_presenter_factory;

    public function __construct(
        Testing_Campaign_CampaignStatPresenterFactory $stat_presenter_factory,
        Testing_TestExecution_TestExecutionInfoPresenterFactory $execution_info_presenter_factory
    ) {
        $this->stat_presenter_factory           = $stat_presenter_factory;
        $this->execution_info_presenter_factory = $execution_info_presenter_factory;
    }

    public function getPresenter(Testing_Campaign_Campaign $campaign) {
        $list_of_execution_presenters = array_map(
            array($this->execution_info_presenter_factory, 'getPresenter'),
            $campaign->getListOfTestExecutions()
        );
        $stat = $this->stat_presenter_factory->getPresenter($campaign);
        return new Testing_Campaign_CampaignPresenter($campaign, $stat, $list_of_execution_presenters);
    }
}
