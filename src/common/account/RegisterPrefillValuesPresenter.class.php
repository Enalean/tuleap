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

class Account_RegisterPrefillValuesPresenter {

    public $form_loginname;
    public $form_email;
    public $form_realname;
    public $form_register_purpose;
    public $form_mail_site;
    public $form_timezone;

    public function __construct($login_name, $email, $realname, $register_purpose, $mail_site, $timezone) {
        $this->form_loginname        = $login_name;
        $this->form_email            = $email;
        $this->form_realname         = $realname;
        $this->form_register_purpose = $register_purpose;
        $this->form_mail_site        = $mail_site;
        $this->form_timezone         = $timezone;
    }
}
