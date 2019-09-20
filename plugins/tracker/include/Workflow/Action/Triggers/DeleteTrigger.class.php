<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Workflow_Action_Triggers_DeleteTrigger
{

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var Tracker_Workflow_Trigger_RulesManager
     */
    private $rule_manager;

    public function __construct(Tracker $tracker, Tracker_Workflow_Trigger_RulesManager $rule_manager)
    {
        $this->tracker             = $tracker;
        $this->rule_manager        = $rule_manager;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user)
    {
        try {
            if (! $request->isPost()) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, 'Method must be post');
                $GLOBALS['Response']->sendStatusCode(405);
                return false;
            }
            $rule = $this->rule_manager->getRuleById($request->getValidated('id', 'uint', 0));
            $this->rule_manager->delete($this->tracker, $rule);
        } catch (Tracker_Exception $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            $GLOBALS['Response']->sendStatusCode(400);
        }
    }
}
