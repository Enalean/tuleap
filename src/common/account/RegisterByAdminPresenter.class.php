<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
    public $form_url               = '/admin/register_admin.php?page=admin_creation';
    public $should_display_purpose = true;

    public function __construct(Account_RegisterAdminPrefillValuesPresenter $prefill_values, $extra_plugin_field)
    {
        parent::__construct($prefill_values, $extra_plugin_field);
        $this->title                  = _('Create a new user');
        $this->submit                 = _('Activate');
        $this->purpose_directions     = _('You can add a comment on this user registration<br>');
        $this->restricted_user        = _('Restricted user');
        $this->send_email             = _('Send a welcome email to the user');
        $this->expiry_date            = _('Expiration date');
        $this->expiry_date_directions = _('<em><strong>Remark:</strong> The account will be suspended when the date is reached. Leave blank if you don\'t want an expiration date.</em>');
    }
}
