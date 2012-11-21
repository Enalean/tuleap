<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once 'Abstract.class.php';

class Tracker_Workflow_Action_EnableWorkflow extends Tracker_Workflow_Action_Abstract {
    
    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        $workflow = WorkflowFactory::instance()->getWorkflowByTrackerId($this->tracker->id);
        $is_used = $request->get('is_used');
        //TODO : use $request
        if (/*$request->existAndNonEmpty($is_used)*/$is_used=='on') {
            $is_used = 1;
            $feedback = $GLOBALS['Language']->getText('workflow_admin','workflow_enabled');
        }else {
            $is_used = 0;
            $feedback = $GLOBALS['Language']->getText('workflow_admin','workflow_disabled');
        }

       if (WorkflowFactory::instance()->updateActivation((int)$workflow->workflow_id, $is_used)) {
           $GLOBALS['Response']->addFeedback('info', $feedback);
           $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')));
       }
    }
}

?>
