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
 * Controller for a Requirement resource
 */
class Testing_Requirement_RequirementController extends MVC2_PluginController {

    const RENDER_PREFIX = 'Requirement/';

    public function __construct(Codendi_Request $request) {
        parent::__construct('testing', $request);
    }

    /**
     * Fallback to prevent us implement dummy methods for the poc
     */
    public function __call($name, $arguments) {
        $presenter = new stdClass;
        $this->render(self::RENDER_PREFIX . $name, $presenter);
    }

    public function index() {
        $presenter = new Testing_Requirement_RequirementInfoCollectionPresenter($this->getProject(), $this->getListOfRequirementInfoPresenters());
        $this->render(self::RENDER_PREFIX . __FUNCTION__, $presenter);
    }

    public function show() {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->request->get('id'));
        $requirement = new Testing_Requirement_Requirement($artifact->getId());
        $i = 1;
        foreach ($artifact->getChildrenForUser($this->getCurrentUser()) as $subartifact) {
            $requirement_version = new Testing_Requirement_RequirementVersion($subartifact->getId(), $requirement, $i++);
        }
        $presenter = new Testing_Requirement_RequirementVersionPresenter($this->getProject(), $requirement_version, array());

        $this->render(self::RENDER_PREFIX . __FUNCTION__, $presenter);
    }

    private function getListOfRequirementInfoPresenters() {
        $list_of_requirement_info_presenters = array();
        foreach(Tracker_ArtifactFactory::instance()->getArtifactsByTrackerId(223) as $artifact) {
            $requirement = new Testing_Requirement_Requirement($artifact->getId());
            $i = 1;
            foreach ($artifact->getChildrenForUser($this->getCurrentUser()) as $subartifact) {
                $requirement_version = new Testing_Requirement_RequirementVersion($subartifact->getId(), $requirement, $i++);
            }
            $list_of_requirement_info_presenters[] = new Testing_Requirement_RequirementVersionInfoPresenter($this->getProject(), $requirement_version);
        }
        return $list_of_requirement_info_presenters;
    }

    public function create() {
        $GLOBALS['Response']->addFeedback('info', 'The milestone has been successfuly created');
        $GLOBALS['Response']->redirect('/plugins/testing/?group_id=1&resource=requirement');
    }
}
