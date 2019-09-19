<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Account_RegisterByAdminPresenter extends Account_RegisterPresenter
{

    public $title;
    public $submit;
    public $purpose_directions;
    public $restricted_user;
    public $send_email;
    public $expiry_date;
    public $expiry_date_directions;
    public $form_url = '/admin/register_admin.php?page=admin_creation';
    public $should_display_purpose = true;

    public function __construct(Account_RegisterAdminPrefillValuesPresenter $prefill_values, $extra_plugin_field)
    {
        parent::__construct($prefill_values, $extra_plugin_field);
        $this->title                  = $GLOBALS['Language']->getText('account_register', 'title_admin');
        $this->submit                 = $GLOBALS['Language']->getText('account_register', 'btn_activate');
        $this->purpose_directions     = $GLOBALS['Language']->getText('account_register', 'purpose_directions_admin');
        $this->restricted_user        = $GLOBALS['Language']->getText('account_register', 'restricted_user');
        $this->send_email             = $GLOBALS['Language']->getText('account_register', 'send_email');
        $this->expiry_date            = $GLOBALS['Language']->getText('account_register', 'expiry_date');
        $this->expiry_date_directions = $GLOBALS['Language']->getText('account_register', 'expiry_date_directions');
    }
}
