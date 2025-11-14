<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Tracker;

readonly class Tracker_Workflow_Action_Triggers_DeleteTrigger
{
    public function __construct(private Tracker $tracker, private Tracker_Workflow_Trigger_RulesManager $rule_manager, private \Tuleap\Request\CSRFSynchronizerTokenInterface $csrf_token)
    {
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, \Tuleap\HTTPRequest $request, PFUser $current_user): void
    {
        $this->csrf_token->check();
        try {
            $rule = $this->rule_manager->getRuleById($request->getValidated('trigger_id', 'uint', 0));
            $this->rule_manager->delete($this->tracker, $rule);
        } catch (Tracker_Exception $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        }
        $GLOBALS['Response']->redirect('/plugins/tracker?' . http_build_query(['func' => 'admin-workflow-triggers', 'tracker' => $this->tracker->getId()]));
    }
}
