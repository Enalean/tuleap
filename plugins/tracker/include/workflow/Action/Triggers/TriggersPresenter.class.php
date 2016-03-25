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

class Tracker_Workflow_Action_Triggers_TriggersPresenter {

    private $form_action;

    /** @var CSRFSynchronizerToken */
    private $token;

    public $project_use_artifact_link_natures;

    public function __construct($form_action, CSRFSynchronizerToken $token, $project_use_artifact_link_natures) {
        $this->form_action                       = $form_action;
        $this->token                             = $token;
        $this->project_use_artifact_link_natures = $project_use_artifact_link_natures;
    }

    public function title_define_triggers() {
        return $GLOBALS['Language']->getText('workflow_admin','title_define_triggers');
    }

    public function title_existing_triggers() {
        return $GLOBALS['Language']->getText('workflow_admin','title_existing_triggers');
    }

    public function title_new_trigger() {
        return $GLOBALS['Language']->getText('workflow_admin','title_new_trigger');
    }

    public function triggers_definition() {
        return $GLOBALS['Language']->getText('workflow_admin','hint_triggers_definition');
    }

    public function triggers_form_action() {
        return $this->form_action;
    }

    public function triggers_add_new() {
        return $GLOBALS['Language']->getText('workflow_admin','add_new_trigger');
    }

    public function triggers_submit() {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function triggers_synch_token() {
        return $this->token->fetchHTMLInput();
    }

    public function new_trigger_select_target_field_name() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_new_trigger_select_target_field_name');
    }

    public function new_trigger_select_target_field_value() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_new_trigger_select_target_field_value');
    }

    public function condition_select_tracker_name() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_condition_select_tracker_name');
    }

    public function condition_select_tracker_field() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_condition_select_tracker_field');
    }

    public function condition_select_tracker_field_value() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_condition_select_tracker_field_value');
    }

    public function condition_of_type() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_condition_of_type');
    }

    public function condition_set_to() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_set_to');
    }

    public function condition_will_be_set_to() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_will_be_set_to');
    }

    public function new_trigger_triggering_field_list_intro() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_new_trigger_triggering_field_list_intro');
    }

    public function new_trigger_target_intro() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_new_trigger_target_intro');
    }

    public function cancel() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_cancel');
    }

    public function no_children() {
        return $GLOBALS['Language']->getText('workflow_admin','tab_triggers_no_children');
    }

    public function cannot_use_triggers() {
        return $GLOBALS['Language']->getText('workflow_admin', 'cannot_use_triggers');
    }
}

?>
