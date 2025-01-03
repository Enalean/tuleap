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

class Account_RegisterByUserPresenter extends Account_RegisterPresenter
{
    public $title;
    public $submit;
    public $mandatory;
    public $new_password2;

    public string $form_url = '/account/register.php';

    public function __construct(\Tuleap\User\Account\Register\RegisterPrefillValuesPresenter $prefill_values, $extra_plugin_field)
    {
        parent::__construct($prefill_values, $extra_plugin_field);
        $this->title         = _('Create your account');
        $this->submit        = _('Create my account');
        $this->mandatory     = _('All fields are mandatory.');
        $this->new_password2 = _('Repeat password');
    }
}
