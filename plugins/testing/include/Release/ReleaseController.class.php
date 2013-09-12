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
 * Controller for a Release resource
 */
class Testing_Release_ReleaseController extends TestingController {

    const RENDER_PREFIX = 'Release/';

    public function __construct(
        Codendi_Request $request,
        $release_association_dao,
        TestingConfiguration $conf
    ) {
        parent::__construct('testing', $request);
        $this->release_association_dao = $release_association_dao;
        $this->requirement_tracker     = $conf->getRequirementTracker();
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
        $release = new Testing_Release_ArtifactRelease($artifact->getId());

        $list_of_requirements = array();
        foreach ($this->release_association_dao->searchByReleaseId($release->getId()) as $row) {
            $requirement = new Testing_Requirement_Requirement($row['requirement_id']);
            $list_of_requirements[] = new Testing_Release_RequirementPresenter($this->getProject(), $requirement, $release);
        }

        $list_of_available_requirements = array();
        foreach ($this->release_association_dao->searchForAvailablesByReleaseId($this->requirement_tracker->getId(), $release->getId()) as $row) {
            $requirement = new Testing_Requirement_Requirement($row['requirement_id']);
            $list_of_available_requirements[] = new Testing_Release_RequirementPresenter($this->getProject(), $requirement, $release);
        }

        $create_requirement_form = new TestingFacadeTrackerCreationPresenter($this->requirement_tracker);

        $presenter = new Testing_Release_ReleasePresenter(
            $this->getProject(),
            $release,
            $list_of_requirements,
            $list_of_available_requirements,
            $create_requirement_form
        );
        $this->render(self::RENDER_PREFIX . __FUNCTION__, $presenter);
    }

    public function linkRequirement() {
        $release_id     = $this->request->get('id');
        $requirement_id = $this->request->get('requirement_id');
        $this->release_association_dao->create($requirement_id, $release_id);
        $GLOBALS['Response']->addFeedback('info', 'The requirement has been successfuly linked to the current release');
        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'release',
                'action'   => 'show',
                'id'       => $release_id
            )
        );
    }

    /**
     * Both create a new requirement and link it to the current release
     */
    public function addRequirement() {
        $user = $this->getCurrentUser();
        $email = null;
        $fields_data = $this->request->get('artifact');
        $this->requirement_tracker->augmentDataFromRequest($fields_data);

        $artifact = Tracker_ArtifactFactory::instance()->createArtifact($this->requirement_tracker, $fields_data, $user, $email);
        if ($artifact) {
            $GLOBALS['Response']->addFeedback('info', 'The requirement has been successfuly created');
            $release_id = $this->request->get('id');
            $this->release_association_dao->create($artifact->getId(), $release_id);
            $GLOBALS['Response']->addFeedback('info', 'The requirement has been successfuly linked to the current release');
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Error while creating the requirement. Please try again.');
        }

        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'release',
                'action'   => 'show',
                'id'       => $release_id
            )
        );
    }

    public function unlinkRequirement() {
        $release_id     = $this->request->get('id');
        $requirement_id = $this->request->get('requirement_id');
        $this->release_association_dao->delete($requirement_id, $release_id);
        $GLOBALS['Response']->addFeedback('info', 'The Release has been successfuly unlinked to the current requirement');
        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'release',
                'action'   => 'show',
                'id'       => $release_id
            )
        );
    }
}
