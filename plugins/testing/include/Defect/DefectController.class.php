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
 * Controller for a defect resource
 */
class Testing_Defect_DefectController extends TestingController {

    /** @var Testing_Defect_DefectDao */
    private $dao;

    public function __construct(
        Codendi_Request $request,
        Testing_Defect_DefectDao $dao
    ) {
        parent::__construct('testing', $request);
        $this->dao = $dao;
    }

    /**
     * @todo csrf
     */
    public function create() {
        $execution_id = $this->request->get('execution_id');
        $tracker = TrackerFactory::instance()->getTrackerById(133);
        $user = $this->getCurrentUser();
        $email = null;
        $fields_data = $this->request->get('artifact');
        $tracker->augmentDataFromRequest($fields_data);

        $artifact = Tracker_ArtifactFactory::instance()->createArtifact($tracker, $fields_data, $user, $email);
        if ($artifact) {
            $this->dao->create($execution_id, $artifact->getId());
            $GLOBALS['Response']->addFeedback('info', 'The defect has been successfuly created');
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Error while creating the defect. Please try again.');
        }

        $this->redirect(
            array(
                'group_id' => $this->getProject()->getId(),
                'resource' => 'testexecution',
                'action'   => 'show',
                'id'       => $execution_id,
            )
        );
    }
}

