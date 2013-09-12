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
 * Controller for a TestCase resource
 */
class Testing_TestCase_TestCaseController extends TestingController {

    const RENDER_PREFIX = 'TestCase/';

    public function __construct(
        Codendi_Request $request,
        Testing_Requirement_TestCaseAssociationDao $dao,
        TestingConfiguration $conf
    ) {
        parent::__construct('testing', $request);
        $this->testcase_association_dao = $dao;
        $this->requirement_tracker      = $conf->getRequirementTracker();
    }

    /**
     * Fallback to prevent us implement dummy methods for the poc
     */
    public function __call($name, $arguments) {
        $presenter = new stdClass;
        $this->render(self::RENDER_PREFIX . $name, $presenter);
    }

    public function show() {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->request->get('id'));
        $testcase = new Testing_TestCase_TestCase($artifact->getId());

        $list_of_requirements = array();
        foreach ($this->testcase_association_dao->searchByTestVersionId($testcase->getId()) as $row) {
            $requirement = new Testing_Requirement_Requirement($row['requirement_id']);
            $list_of_requirements[] = new Testing_TestCase_RequirementPresenter($this->getProject(), $requirement, $testcase);
        }

        $list_of_available_requirements = array();
        foreach ($this->testcase_association_dao->searchForAvailablesByTestVersionId($this->requirement_tracker->getId(), $testcase->getId()) as $row) {
            $requirement = new Testing_Requirement_Requirement($row['requirement_id']);
            $list_of_available_requirements[] = new Testing_TestCase_RequirementPresenter($this->getProject(), $requirement, $testcase);
        }

        $create_requirement_form = new TestingFacadeTrackerCreationPresenter($this->requirement_tracker);

        $presenter = new Testing_TestCase_TestCasePresenter(
            $this->getProject(),
            $testcase,
            $list_of_requirements,
            $list_of_available_requirements,
            $create_requirement_form
        );
        $this->render(self::RENDER_PREFIX . __FUNCTION__, $presenter);
    }

    public function linkRequirement() {
        $testcase_id    = $this->request->get('id');
        $requirement_id = $this->request->get('requirement_id');
        $this->testcase_association_dao->create($requirement_id, $testcase_id);
        $GLOBALS['Response']->addFeedback('info', 'The requirement has been successfuly linked to the current test case');
        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'testcase',
                'action'   => 'show',
                'id'       => $testcase_id
            )
        );
    }

    /**
     * Both create a new requirement and link it to the current test case
     */
    public function addRequirement() {
        $user = $this->getCurrentUser();
        $email = null;
        $fields_data = $this->request->get('artifact');
        $this->requirement_tracker->augmentDataFromRequest($fields_data);

        $artifact = Tracker_ArtifactFactory::instance()->createArtifact($this->requirement_tracker, $fields_data, $user, $email);
        if ($artifact) {
            $GLOBALS['Response']->addFeedback('info', 'The requirement has been successfuly created');
            $testcase_id = $this->request->get('id');
            $this->testcase_association_dao->create($artifact->getId(), $testcase_id);
            $GLOBALS['Response']->addFeedback('info', 'The requirement has been successfuly linked to the current test case');
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Error while creating the requirement. Please try again.');
        }

        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'testcase',
                'action'   => 'show',
                'id'       => $testcase_id
            )
        );
    }

    public function unlinkRequirement() {
        $testcase_id    = $this->request->get('id');
        $requirement_id = $this->request->get('requirement_id');
        $this->testcase_association_dao->delete($requirement_id, $testcase_id);
        $GLOBALS['Response']->addFeedback('info', 'The requirement has been successfuly unlinked from the current test case');
        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'testcase',
                'action'   => 'show',
                'id'       => $testcase_id
            )
        );
    }
}
