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
 * Controller for a test result resource
 */
class Testing_TestResult_TestResultController extends MVC2_PluginController {

    /** @var Testing_TestResult_TestResultDao */
    private $dao;

    public function __construct(
        Codendi_Request $request,
        Testing_TestResult_TestResultDao $dao
    ) {
        parent::__construct('testing', $request);
        $this->dao = $dao;
    }

    /**
     * @todo csrf
     */
    public function create() {
        $this->dao->create(
            $this->request->get('execution_id'),
            $this->request->getCurrentUser()->getId(),
            $_SERVER['REQUEST_TIME'],
            $this->request->get('status'),
            $this->request->get('message')
        );
        $GLOBALS['Response']->addFeedback('info', 'The test result has been successfuly created');
        $GLOBALS['Response']->redirect('/plugins/testing/?group_id='. $this->getProject()->getId() .'&resource=testexecution&action=show&id='. $this->request->get('execution_id'));
    }
}
