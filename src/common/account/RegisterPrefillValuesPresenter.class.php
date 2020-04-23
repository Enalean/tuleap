<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

class Account_RegisterPrefillValuesPresenter
{

    /** @var Account_RegisterField */
    public $form_loginname;
    /** @var Account_RegisterField */
    public $form_email;
    /** @var Account_RegisterField|null */
    public $form_pw;
    /** @var Account_RegisterField */
    public $form_realname;
    /** @var Account_RegisterField */
    public $form_register_purpose;
    /** @var Account_RegisterField */
    public $form_mail_site;
    /** @var Account_RegisterField */
    public $form_timezone;

    public function __construct(
        Account_RegisterField $login_name,
        Account_RegisterField $email,
        ?Account_RegisterField $password,
        Account_RegisterField $realname,
        Account_RegisterField $register_purpose,
        Account_RegisterField $mail_site,
        Account_RegisterField $timezone
    ) {
        $this->form_loginname        = $login_name;
        $this->form_email            = $email;
        $this->form_pw               = $password;
        $this->form_realname         = $realname;
        $this->form_register_purpose = $register_purpose;
        $this->form_mail_site        = $mail_site;
        $this->form_timezone         = $timezone;
    }
}
