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

class Tracker_Workflow_Action_Triggers_TriggersPresenter
{
    /**
     * @var int
     */
    public $tracker_id;

    private $form_action;

    /** @var CSRFSynchronizerToken */
    private $token;

    public function __construct(int $tracker_id, string $form_action, CSRFSynchronizerToken $token)
    {
        $this->tracker_id  = $tracker_id;
        $this->form_action = $form_action;
        $this->token       = $token;
    }

    public function title_define_triggers()
    {
        return dgettext('tuleap-tracker', 'Define cross-tracker triggers');
    }

    public function title_existing_triggers()
    {
        return dgettext('tuleap-tracker', 'Existing triggers');
    }

    public function title_new_trigger()
    {
        return dgettext('tuleap-tracker', 'Create new trigger');
    }

    public function triggers_definition()
    {
        return dgettext('tuleap-tracker', 'The triggers will be applied on each creation/update of artifacts.');
    }

    public function triggers_form_action()
    {
        return $this->form_action;
    }

    public function triggers_add_new()
    {
        return dgettext('tuleap-tracker', 'Add a new trigger');
    }

    public function triggers_submit()
    {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function triggers_synch_token()
    {
        return $this->token->fetchHTMLInput();
    }

    public function new_trigger_select_target_field_name()
    {
        return dgettext('tuleap-tracker', 'select current tracker field');
    }

    public function new_trigger_select_target_field_value()
    {
        return dgettext('tuleap-tracker', 'select value');
    }

    public function condition_select_tracker_name()
    {
        return dgettext('tuleap-tracker', 'select child tracker name');
    }

    public function condition_select_tracker_field()
    {
        return dgettext('tuleap-tracker', 'select child tracker field');
    }

    public function condition_select_tracker_field_value()
    {
        return dgettext('tuleap-tracker', 'select value');
    }

    public function condition_of_type()
    {
        return dgettext('tuleap-tracker', 'of type');
    }

    public function condition_set_to()
    {
        return dgettext('tuleap-tracker', 'set to');
    }

    public function condition_will_be_set_to()
    {
        return dgettext('tuleap-tracker', 'will be set to');
    }

    public function new_trigger_triggering_field_list_intro()
    {
        return dgettext('tuleap-tracker', 'When');
    }

    public function new_trigger_target_intro()
    {
        return dgettext('tuleap-tracker', 'Then the tracker field');
    }

    public function cancel()
    {
        return dgettext('tuleap-tracker', 'cancel');
    }

    public function no_children()
    {
        return dgettext('tuleap-tracker', 'There are no child trackers defined for this tracker');
    }
}
