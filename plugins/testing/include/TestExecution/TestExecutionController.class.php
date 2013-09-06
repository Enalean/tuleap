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

require_once 'common/mvc2/PluginController.class.php';

/**
 * Controller for a test execution resource
 */
class Testing_TestExecution_TestExecutionController extends MVC2_PluginController {

    const RENDER_PREFIX = 'TestExecution/';

    /** @var Testing_TestExecution_TestExecutionManager */
    private $execution_manager;

    /** @var Testing_Campaign_CampaignInfoPresenterFactory */
    private $campaign_info_presenter_factory;

    public function __construct(
        Codendi_Request $request,
        Testing_TestExecution_TestExecutionManager $execution_manager,
        Testing_Campaign_CampaignInfoPresenterFactory $campaign_info_presenter_factory
    ) {
        parent::__construct('testing', $request);
        $this->execution_manager               = $execution_manager;
        $this->campaign_info_presenter_factory = $campaign_info_presenter_factory;
    }

    /**
     * Fallback to prevent us implement dummy methods for the poc
     */
    public function __call($name, $arguments) {
        $presenter = new stdClass;
        $this->render(self::RENDER_PREFIX . $name, $presenter);
    }

    public function show() {
        $execution = $this->execution_manager->getTestExecution($this->request->getProject(), $this->request->get('id'));
        $campaign_info_presenter = $this->campaign_info_presenter_factory->getPresenter($execution->getCampaign());
        $last_result_presenter = new Testing_TestResult_TestResultPresenter($execution->getLastTestResult());
        $presenter = new Testing_TestExecution_TestExecutionPresenter($execution, $campaign_info_presenter, $last_result_presenter);
        $this->render(self::RENDER_PREFIX . 'show', $presenter);
    }
}
