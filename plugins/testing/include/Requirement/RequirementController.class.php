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

    public function __construct(Codendi_Request $request) {
        parent::__construct('testing', $request);
        $this->testcase_association_dao = new Testing_Requirement_TestCaseAssociationDao();
    }

    /**
     * Fallback to prevent us implement dummy methods for the poc
     */
    public function __call($name, $arguments) {
        $presenter = new stdClass;
        $this->render(self::RENDER_PREFIX . $name, $presenter);
    }

    public function index() {
        $tracker = $this->getTracker();
        $create_requirement_form = new TestingFacadeTrackerCreationPresenter($tracker);

        $presenter = new Testing_Requirement_RequirementInfoCollectionPresenter(
            $this->getProject(),
            $this->getListOfRequirementInfoPresenters($tracker),
            $create_requirement_form
        );
        $this->render(self::RENDER_PREFIX . __FUNCTION__, $presenter);
    }

    public function show() {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->request->get('id'));
        $requirement = new Testing_Requirement_Requirement($artifact->getId());
        $i = 1;
        foreach ($artifact->getChildrenForUser($this->getCurrentUser()) as $subartifact) {
            $requirement_version = new Testing_Requirement_RequirementVersion($subartifact->getId(), $requirement, $i++);
        }

        $list_of_test_cases = array();
        foreach ($this->testcase_association_dao->searchByRequirementId($artifact->getId()) as $row) {
            $testcase = new Testing_TestCase_TestCase($row['testversion_id']);
            $testcase_version = new Testing_TestCase_TestCaseVersion($row['testversion_id'], $testcase);
            $list_of_test_cases[] = new Testing_Requirement_TestCaseVersionPresenter($this->getProject(), $testcase_version);
        }

        $presenter = new Testing_Requirement_RequirementVersionPresenter($this->getProject(), $requirement_version, $list_of_test_cases);
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

    private function getTracker() {
        $conf = new TestingConfiguration($this->getProject());
        return $conf->getRequirementTracker();
    }

    private function getListOfRequirementInfoPresenters(Tracker $tracker) {
        $list_of_requirement_info_presenters = array();
        foreach(Tracker_ArtifactFactory::instance()->getArtifactsByTrackerId($tracker->getId()) as $artifact) {
            $requirement = new Testing_Requirement_Requirement($artifact->getId());
            $i = 1;
            foreach ($artifact->getChildrenForUser($this->getCurrentUser()) as $subartifact) {
                $requirement_version = new Testing_Requirement_RequirementVersion($subartifact->getId(), $requirement, $i++);
            }
            $list_of_requirement_info_presenters[] = new Testing_Requirement_RequirementVersionInfoPresenter($this->getProject(), $requirement_version);
        }
        return $list_of_requirement_info_presenters;
    }
}
