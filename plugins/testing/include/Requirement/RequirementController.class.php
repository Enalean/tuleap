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
 * Controller for a Requirement resource
 */
class Testing_Requirement_RequirementController extends TestingController {

    const RENDER_PREFIX = 'Requirement/';

    public function __construct(
        Codendi_Request $request,
        Testing_Requirement_TestCaseAssociationDao $dao,
        Testing_Requirement_ReleaseAssociationDao $release_dao,
        Testing_Requirement_RequirementInfoCollectionPresenterFactory $collection_presenter_factory
    ) {
        parent::__construct('testing', $request);
        $this->testcase_association_dao     = $dao;
        $this->release_association_dao      = $release_dao;
        $this->collection_presenter_factory = $collection_presenter_factory;
    }

    /**
     * Fallback to prevent us implement dummy methods for the poc
     */
    public function __call($name, $arguments) {
        $presenter = new stdClass;
        $this->render(self::RENDER_PREFIX . $name, $presenter);
    }

    public function index() {
        $presenter = $this->collection_presenter_factory->getPresenter();
        $this->render(self::RENDER_PREFIX . __FUNCTION__, $presenter);
    }

    public function show() {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->request->get('id'));
        $requirement = new Testing_Requirement_Requirement($artifact->getId());

        $list_of_test_cases = array();
        foreach ($this->testcase_association_dao->searchByRequirementId($requirement->getId()) as $row) {
            $testcase = new Testing_TestCase_TestCase($row['testversion_id']);
            $list_of_test_cases[] = new Testing_Requirement_TestCasePresenter($this->getProject(), $testcase, $requirement);
        }

        $list_of_releases = array();
        foreach ($this->release_association_dao->searchByRequirementId($requirement->getId()) as $row) {
            $release = new Testing_Release_ArtifactRelease($row['release_id']);
            $list_of_releases[] = new Testing_Requirement_ReleasePresenter($this->getProject(), $release, $requirement);
        }

        $release_tracker = $this->getReleaseTracker();
        $list_of_available_releases = array();
        foreach ($this->release_association_dao->searchForAvailablesByRequirementId($release_tracker->getId(), $requirement->getId()) as $row) {
            $release = new Testing_Release_ArtifactRelease($row['release_id']);
            $list_of_available_releases[] = new Testing_Requirement_ReleasePresenter($this->getProject(), $release, $requirement);
        }

        $tracker = $this->getTestCaseTracker();
        $list_of_available_test_cases = array();
        foreach ($this->testcase_association_dao->searchForAvailablesByRequirementId($tracker->getId(), $requirement->getId()) as $row) {
            $testcase = new Testing_TestCase_TestCase($row['testversion_id']);
            $list_of_available_test_cases[] = new Testing_Requirement_TestCasePresenter($this->getProject(), $testcase, $requirement);
        }
        $create_requirement_form = new TestingFacadeTrackerCreationPresenter($tracker);

        $presenter = new Testing_Requirement_RequirementPresenter(
            $this->getProject(),
            $requirement,
            $list_of_test_cases,
            $list_of_releases,
            $list_of_available_test_cases,
            $list_of_available_releases,
            $create_requirement_form
        );
        $this->render(self::RENDER_PREFIX . __FUNCTION__, $presenter);
    }

    public function create() {
        $tracker = $this->getTracker();
        $user = $this->getCurrentUser();
        $email = null;
        $fields_data = $this->request->get('artifact');
        $tracker->augmentDataFromRequest($fields_data);

        $artifact = Tracker_ArtifactFactory::instance()->createArtifact($tracker, $fields_data, $user, $email);
        if ($artifact) {
            $GLOBALS['Response']->addFeedback('info', 'The requirement has been successfuly created');
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Error while creating the requirement. Please try again.');
        }

        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'requirement'
            )
        );
    }

    public function linkTestCase() {
        $requirement_id = $this->request->get('id');
        $testcase_id    = $this->request->get('testcase_id');
        $this->testcase_association_dao->create($requirement_id, $testcase_id);
        $GLOBALS['Response']->addFeedback('info', 'The testcase has been successfuly linked to the current requirement');
        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'requirement',
                'action'   => 'show',
                'id'       => $requirement_id
            )
        );
    }

    /**
     * Both create a new test case and link it to the current requirement
     */
    public function addTestCase() {
        $tracker = $this->getTestCaseTracker();
        $user = $this->getCurrentUser();
        $email = null;
        $fields_data = $this->request->get('artifact');
        $tracker->augmentDataFromRequest($fields_data);

        $artifact = Tracker_ArtifactFactory::instance()->createArtifact($tracker, $fields_data, $user, $email);
        if ($artifact) {
            $GLOBALS['Response']->addFeedback('info', 'The testcase has been successfuly created');
            $requirement_id = $this->request->get('id');
            $this->testcase_association_dao->create($requirement_id, $artifact->getId());
            $GLOBALS['Response']->addFeedback('info', 'The testcase has been successfuly linked to the current requirement');
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Error while creating the requirement. Please try again.');
        }

        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'requirement',
                'action'   => 'show',
                'id'       => $requirement_id
            )
        );
    }

    public function unlinkTestCase() {
        $requirement_id = $this->request->get('id');
        $testcase_id    = $this->request->get('testcase_id');
        $this->testcase_association_dao->delete($requirement_id, $testcase_id);
        $GLOBALS['Response']->addFeedback('info', 'The testcase has been successfuly unlinked to the current requirement');
        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'requirement',
                'action'   => 'show',
                'id'       => $requirement_id
            )
        );
    }

    public function linkRelease() {
        $requirement_id = $this->request->get('id');
        $release_id     = $this->request->get('release_id');
        $this->release_association_dao->create($requirement_id, $release_id);
        $GLOBALS['Response']->addFeedback('info', 'The release has been successfuly linked to the current requirement');
        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'requirement',
                'action'   => 'show',
                'id'       => $requirement_id
            )
        );
    }

    public function unlinkRelease() {
        $requirement_id = $this->request->get('id');
        $release_id     = $this->request->get('release_id');
        $this->release_association_dao->delete($requirement_id, $release_id);
        $GLOBALS['Response']->addFeedback('info', 'The release has been successfuly unlinked to the current requirement');
        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'requirement',
                'action'   => 'show',
                'id'       => $requirement_id
            )
        );
    }

    private function getTracker() {
        $conf = new TestingConfiguration($this->getProject());
        return $conf->getRequirementTracker();
    }

    private function getReleaseTracker() {
        $conf = new TestingConfiguration($this->getProject());
        return $conf->getReleaseTracker();
    }

    private function getTestCaseTracker() {
        $conf = new TestingConfiguration($this->getProject());
        return $conf->getTestCaseTracker();
    }
}
