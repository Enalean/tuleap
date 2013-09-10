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
 * Controller for a campaign resource
 */
class Testing_Campaign_CampaignController extends TestingController {

    const RENDER_PREFIX = 'Campaign/';

    /** @var Testing_Campaign_CampaignInfoPresenterCollectionFactory */
    private $pinfo_resenter_collection_factory;

    /** @var Testing_Campaign_CampaignInfoPresenterFactory */
    private $info_presenter_factory;

    /** @var Testing_Campaign_CampaignCreator */
    private $creator;

    /** @var Testing_Campaign_CampaignManager */
    private $manager;

    public function __construct(
        Codendi_Request $request,
        Testing_Campaign_CampaignInfoPresenterCollectionFactory $info_presenter_collection_factory,
        Testing_Campaign_CampaignCreator $creator,
        Testing_Campaign_CampaignDeletor $deletor,
        Testing_Campaign_CampaignManager $manager,
        Testing_Campaign_CampaignInfoPresenterFactory $info_presenter_factory,
        Testing_Campaign_CampaignPresenterFactory $presenter_factory,
        Testing_TestCase_TestCaseInfoPresenterCollectionFactory $test_case_info_presenter_collection_factory,
        Testing_Requirement_RequirementInfoCollectionPresenterFactory $requirement_info_collection_presenter_factory
    ) {
        parent::__construct('testing', $request);
        $this->info_presenter_collection_factory = $info_presenter_collection_factory;
        $this->creator                           = $creator;
        $this->deletor                           = $deletor;
        $this->manager                           = $manager;
        $this->info_presenter_factory            = $info_presenter_factory;
        $this->presenter_factory                 = $presenter_factory;
        $this->test_case_info_presenter_collection_factory   = $test_case_info_presenter_collection_factory;
        $this->requirement_info_collection_presenter_factory = $requirement_info_collection_presenter_factory;
    }

    /**
     * Fallback to prevent us implement dummy methods for the poc
     */
    public function __call($name, $arguments) {
        $presenter = new stdClass;
        $this->render(self::RENDER_PREFIX . $name, $presenter);
    }

    public function index() {
        $presenter = new Testing_Campaign_CampaignInfoCollectionPresenter(
            $this->info_presenter_collection_factory->getListOfCampaignInfoPresenters($this->request->getProject())
        );
        $this->render(self::RENDER_PREFIX .'index', $presenter);
    }

    public function show() {
        $campaign  = $this->manager->getCampaign($this->request->getProject(), $this->request->get('id'));
        $presenter = $this->presenter_factory->getPresenter($campaign);
        $this->render(self::RENDER_PREFIX .'show', $presenter);
    }

    public function neue() {
        $presenter = new Testing_Campaign_CampaignCreationPresenter(
            $this->getProject(),
            $this->test_case_info_presenter_collection_factory->getPresenter(),
            $this->requirement_info_collection_presenter_factory->getListOfRequirementInfoPresenters()
        );
        $this->render(self::RENDER_PREFIX .'neue', $presenter);
    }

    /**
     * @todo csrf
     */
    public function create() {
        $data = $this->request->get('campaign');
        $this->creator->create(
            $this->getProject(),
            $data['name'],
            $this->request->get('test_cases')
        );
        $GLOBALS['Response']->addFeedback('info', 'The campaign has been successfuly created');
        $this->redirect(array('group_id' => $this->getProject()->getId()));
    }

    /**
     * @todo csrf, check project, ...
     */
    public function delete() {
        $this->deletor->delete($this->request->get('id'));
        $GLOBALS['Response']->addFeedback('info', 'The campaign has been successfuly deleted');
        $this->redirect(array('group_id' => $this->getProject()->getId()));
    }
}
