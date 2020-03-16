<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class WorkflowManager
{
    protected $tracker;

    public function __construct($tracker)
    {
        $this->tracker = $tracker;
    }

    public function process(TrackerManager $engine, HTTPRequest $request, PFUser $current_user)
    {
        $workflow_factory = WorkflowFactory::instance();
        if ($request->get('func') == Workflow::FUNC_ADMIN_RULES) {
            $token = new CSRFSynchronizerToken(TRACKER_BASE_URL . '/?' . http_build_query(
                array(
                    'tracker' => (int) $this->tracker->id,
                    'func'    => Workflow::FUNC_ADMIN_RULES,
                    )
            ));
            $rule_date_factory = new Tracker_Rule_Date_Factory(new Tracker_Rule_Date_Dao(), Tracker_FormElementFactory::instance());
            $action = new Tracker_Workflow_Action_Rules_EditRules($this->tracker, $rule_date_factory, $token);
        } elseif ($request->get('func') == Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS) {
            $token = new CSRFSynchronizerToken(TRACKER_BASE_URL . '/?' . http_build_query(
                array(
                    'tracker' => (int) $this->tracker->id,
                    'func'    => Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS,
                    )
            ));

            $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_BASE_DIR . '/../templates');
            $action   = new Tracker_Workflow_Action_Triggers_EditTriggers(
                $this->tracker,
                $token,
                $renderer,
                $workflow_factory->getTriggerRulesManager()
            );
        } elseif ($request->get('func') == Workflow::FUNC_ADMIN_GET_TRIGGERS_RULES_BUILDER_DATA) {
            $action = new Tracker_Workflow_Action_Triggers_GetTriggersRulesBuilderData($this->tracker, Tracker_FormElementFactory::instance());
        } elseif ($request->get('func') == Workflow::FUNC_ADMIN_ADD_TRIGGER) {
            $action = new Tracker_Workflow_Action_Triggers_AddTrigger($this->tracker, Tracker_FormElementFactory::instance(), $workflow_factory->getTriggerRulesManager());
        } elseif ($request->get('func') == Workflow::FUNC_ADMIN_DELETE_TRIGGER) {
            $action = new Tracker_Workflow_Action_Triggers_DeleteTrigger($this->tracker, $workflow_factory->getTriggerRulesManager());
        } else {
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/workflow/' . $this->tracker->id . '/transitions');
            return;
        }

        $action->process($engine, $request, $current_user);
    }
}
