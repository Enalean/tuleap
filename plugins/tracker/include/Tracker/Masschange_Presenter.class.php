<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tracker_Masschange_Presenter
{

    /** @var array */
    public $masschange_aids;

    /** @var string */
    public $form_elements;

    /** @var string */
    public $javascript_rules;

    /**
     * @var string[]
     */
    public $external_actions;

    /**
     * @var bool
     */
    public $has_external_actions;

    public function __construct(array $masschange_aids, $form_elements, $javascript_rules, array $external_actions)
    {
        $this->masschange_aids  = $masschange_aids;
        $this->form_elements    = $form_elements;
        $this->javascript_rules = $javascript_rules;
        $this->external_actions = $external_actions;

        $this->has_external_actions = (bool) count($external_actions) > 0;
    }

    public function changing_items()
    {
        return sprintf(dgettext('tuleap-tracker', 'Changing %1$s artifact(s):'), count($this->masschange_aids));
    }

    public function tracker_base_url()
    {
        return TRACKER_BASE_URL;
    }

    public function artifact_fields_title()
    {
        return dgettext('tuleap-tracker', 'Artifacts fields');
    }

    public function masschange_info_title()
    {
        return dgettext('tuleap-tracker', 'Masschange information');
    }

    public function unsubscribe_label()
    {
        return dgettext('tuleap-tracker', 'Unsubscribe me from these artifacts\' notifications');
    }

    public function add_comment()
    {
        return dgettext('tuleap-tracker', 'Add a follow-up comment');
    }

    public function notification_label()
    {
        return dgettext('tuleap-tracker', 'Send notifications to people monitoring these artifacts');
    }

    public function masschange_submit()
    {
        return dgettext('tuleap-tracker', 'Submit');
    }

    public function default_comment()
    {
        return dgettext('tuleap-tracker', 'Mass Change');
    }
}
