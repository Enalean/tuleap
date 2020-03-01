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

use PFUser;
use CSRFSynchronizerToken;

class UserChangePasswordPresenter
{
    /** @var PFUser */
    private $user;

    /** @var CSRFSynchronizerToken */
    public $csrf_token;
    /** @var array */
    public $additional_password_messages;
    /** @var array */
    public $passwords_validators;
    public $user_id;
    public $modal_title;
    public $modal_save;
    public $modal_cancel;
    public $password_field_label;
    public $confirm_password_field_label;

    public function __construct(
        PFUser $user,
        CSRFSynchronizerToken $csrf_token,
        array $additional_password_messages,
        array $passwords_validators
    ) {
        $this->user                         = $user;
        $this->csrf_token                   = $csrf_token;
        $this->additional_password_messages = $additional_password_messages;
        $this->passwords_validators         = $passwords_validators;

        $this->user_id                      = $this->user->getId();
        $this->modal_title                  = $GLOBALS['Language']->getText('admin_user_changepw', 'header');
        $this->modal_save                   = $GLOBALS['Language']->getText('admin_user_changepw', 'save');
        $this->modal_cancel                 = $GLOBALS['Language']->getText('admin_user_changepw', 'cancel');
        $this->password_field_label         = $GLOBALS['Language']->getText('admin_user_changepw', 'password_field_label');
        $this->confirm_password_field_label = $GLOBALS['Language']->getText('admin_user_changepw', 'confirm_password_field_label');
    }
}
