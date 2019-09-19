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

    public function __construct(array $masschange_aids, $form_elements, $javascript_rules)
    {
        $this->masschange_aids  = $masschange_aids;
        $this->form_elements    = $form_elements;
        $this->javascript_rules = $javascript_rules;
    }

    public function changing_items()
    {
        return $GLOBALS['Language']->getText(
            'plugin_tracker_artifact_masschange',
            'changing_items',
            array(count($this->masschange_aids))
        );
    }

    public function tracker_base_url()
    {
        return TRACKER_BASE_URL;
    }

    public function artifact_fields_title()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'artifact_fields_title');
    }

    public function masschange_info_title()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_masschange', 'info');
    }

    public function unsubscribe_label()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_masschange', 'unsubscribe');
    }

    public function add_comment()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'add_comment');
    }

    public function notification_label()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_masschange', 'send_notifications');
    }

    public function masschange_submit()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'submit_mass_change');
    }

    public function default_comment()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_index', 'mass_change');
    }
}
