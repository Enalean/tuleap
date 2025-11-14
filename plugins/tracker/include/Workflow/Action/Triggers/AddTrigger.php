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

readonly class Tracker_Workflow_Action_Triggers_AddTrigger
{
    public function __construct(
        private Tracker $tracker,
        private Tracker_FormElementFactory $formelement_factory,
        private Tracker_Workflow_Trigger_RulesManager $rule_manager,
        private \Tuleap\Request\CSRFSynchronizerTokenInterface $csrf_token,
    ) {
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, \Tuleap\HTTPRequest $request, PFUser $current_user): void
    {
        $this->csrf_token->check();
        $validator = new Tracker_Workflow_Trigger_TriggerValidator($this->rule_manager);

        $GLOBALS['Response']->setContentType('text/plain');
        try {
            $rules_factory = new Tracker_Workflow_Trigger_RulesFactory($this->formelement_factory, $validator);
            $rule          = $rules_factory->getRuleFromJson(
                $this->tracker,
                json_decode((string) $request->get('trigger_data'), false, 16, JSON_THROW_ON_ERROR)
            );
            $this->rule_manager->add($rule);
            echo $rule->getId();
        } catch (JsonException | Tracker_Exception $exception) {
            echo $exception->getMessage();
            $GLOBALS['Response']->sendStatusCode(400);
        }
    }
}
