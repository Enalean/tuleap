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


class Tracker_Workflow_Action_Transitions_Details extends Tracker_Workflow_Action_Transitions {
     /** @var TransitionFactory */
    private $transition_factory;

    public function __construct(Tracker $tracker, TransitionFactory $transition_factory) {
        parent::__construct($tracker);
        $this->transition_factory = $transition_factory;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        $transition = $request->get('transition');

        //TODO check that the transition belongs to the current tracker

        // Permissions
        $ugroups = $request->get('ugroups');
        permission_clear_all($this->tracker->group_id, 'PLUGIN_TRACKER_WORKFLOW_TRANSITION', $transition, false);
        if ($this->transition_factory->addPermissions($ugroups, $transition)) {
           $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','permissions_updated'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('workflow_admin','permissions_not_updated'));
        }
        //Conditions
        $condition_manager = new Transition_ConditionManager();
        $condition_manager->process($this->transition_factory->getTransition($transition), $request, $current_user);

        // Post actions
        $tpam = new Transition_PostActionManager();
        $tpam->process($this->transition_factory->getTransition($transition), $request, $current_user);

        $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker'         => (int)$this->tracker->id,
                'func'            => Workflow::FUNC_ADMIN_TRANSITIONS,
                'edit_transition' => $request->get('transition'),
            )
        ));
    }
}

?>