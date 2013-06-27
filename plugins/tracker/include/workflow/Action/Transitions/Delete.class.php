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


class Tracker_Workflow_Action_Transitions_Delete extends Tracker_Workflow_Action_Transitions {
     /** @var WorkflowFactory */
    private $workflow_factory;

    public function __construct(Tracker $tracker, WorkflowFactory $workflow_factory) {
        parent::__construct($tracker);
        $this->workflow_factory = $workflow_factory;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user) {
        if ($this->workflow_factory->deleteWorkflow($request->get('delete'))) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','deleted'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array(
                                            'tracker' => (int)$this->tracker->id,
                                            'func'    => Workflow::FUNC_ADMIN_TRANSITIONS)));
         }
    }
}

?>
