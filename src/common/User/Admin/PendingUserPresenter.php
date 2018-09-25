<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use ForgeConfig;

class PendingUserPresenter
{
    public $id;
    public $login;
    public $realname;
    public $email;
    public $registration_date;
    public $purpose;
    public $expiry_date;
    public $can_resend_email;

    public function __construct($id, $login, $realname, $email, $registration_date, $purpose, $expiry_date, $status)
    {
        $this->id                = $id;
        $this->login             = $login;
        $this->realname          = $realname;
        $this->email             = $email;
        $this->registration_date = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $registration_date);
        $this->purpose           = $purpose;
        if ($purpose === null) {
            $this->purpose = '';
        }
        $this->expiry_date       = '';
        if ($expiry_date) {
            $this->expiry_date = format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), $expiry_date);
        }
        $this->can_resend_email = ForgeConfig::get('sys_user_approval') == 0 || 'V' === $status || 'W' === $status;
        $this->resend_label     = $GLOBALS['Language']->getText('admin_approve_pending_users', 'resend');
    }
}
