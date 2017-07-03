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

class Account_RegisterAdminPrefillValuesPresenter extends Account_RegisterPrefillValuesPresenter
{
    public $form_restricted;
    public $form_send_email;
    public $does_platform_allows_restricted;

    public function __construct(
        $login_name,
        $email,
        $password,
        $realname,
        $register_purpose,
        $mail_site,
        $timezone,
        $restricted,
        $send_email,
        $does_platform_allows_restricted
    ) {
        parent::__construct($login_name, $email, $password, $realname, $register_purpose, $mail_site, $timezone);
        $this->does_platform_allows_restricted = $does_platform_allows_restricted;
        $this->form_restricted                 = $restricted;
        $this->form_send_email                 = $send_email;
    }
}
