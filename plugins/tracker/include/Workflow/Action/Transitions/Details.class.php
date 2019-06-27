<?php
/**
 * Copyright (c) Enalean, 2012 - 2019. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\Transition\Condition\CannotCreateTransitionException;

class Tracker_Workflow_Action_Transitions_Details extends Tracker_Workflow_Action_Transitions {
     /** @var TransitionFactory */
    private $transition_factory;

    public function __construct(Tracker $tracker, TransitionFactory $transition_factory) {
        parent::__construct($tracker);
        $this->transition_factory = $transition_factory;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user)
    {
        $transition = $this->transition_factory->getTransition($request->get('transition'));
        if ($transition === null || (int) $transition->getWorkflow()->getTrackerId() !== (int) $this->tracker->getId()) {
            $GLOBALS['Response']->redirect('/');
        }

        $ugroups = $request->get('ugroups');
        if (! $ugroups || ! is_array($ugroups)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('workflow_admin', 'permissions_not_updated')
            );
            $this->redirectToTransactionEditionPage($transition);
        }

        // Permissions

        permission_clear_all($transition->getGroupId(), 'PLUGIN_TRACKER_WORKFLOW_TRANSITION', $transition->getId(), false);
        if ($this->transition_factory->addPermissions($ugroups, $transition->getId())) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin', 'permissions_updated'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('workflow_admin', 'permissions_not_updated'));
        }

        //Conditions
        try {
            $condition_manager = new Transition_ConditionManager();
            $condition_manager->process($transition, $request, $current_user);
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin', 'empty_fields_updated'));
        } catch (CannotCreateTransitionException $exception) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('workflow_admin', 'empty_fields_not_updated'));
        }

        // Post actions
        $tpam = new Transition_PostActionManager();
        $tpam->process($transition, $request, $current_user);

        $this->redirectToTransactionEditionPage($transition);
    }

    private function redirectToTransactionEditionPage(Transition $transition) : void
    {
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(
                array(
                    'tracker'         => $transition->getWorkflow()->getTrackerId(),
                    'func'            => Workflow::FUNC_ADMIN_TRANSITIONS,
                    'edit_transition' => $transition->getId(),
                )
            ));
    }
}
