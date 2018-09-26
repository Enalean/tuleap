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

use Codendi_HTMLPurifier;
use CSRFSynchronizerToken;
use ForgeConfig;

class PendingUsersCollectionPresenter
{
    public $title;
    public $users;
    public $validate_notice;
    public $activate_notice;
    public $registration_date;
    public $purpose_label;
    public $empty_label;
    public $edit_label;
    public $page;
    public $csrf_token;
    public $expiry_date_label;
    public $purified_expiry_date_details;
    public $can_be_validated;
    public $can_be_restricted;
    public $activate_label;
    public $validate_label;
    public $delete_label;
    public $list_ids;
    public $validate_all_label;
    public $activate_all_label;
    public $restricted_all_label;
    public $more_than_one_to_validate;

    public function __construct($title, array $users, $page, CSRFSynchronizerToken $csrf_token)
    {
        $this->title      = $title;
        $this->users      = $users;
        $this->page       = $page;
        $this->csrf_token = $csrf_token;
        $this->list_ids   = $this->getListOfUserIds($users);

        $this->more_than_one_to_validate = count($users) > 1;

        $this->can_be_restricted = ForgeConfig::areRestrictedUsersAllowed();
        $this->can_be_validated  = ForgeConfig::get('sys_user_approval') == 1 && ADMIN_APPROVE_PENDING_PAGE_PENDING == $page;

        $this->validate_notice = $GLOBALS['Language']->getText('admin_approve_pending_users', 'validate_notice');
        $this->activate_notice = $GLOBALS['Language']->getText('admin_approve_pending_users', 'activate_notice');
        $this->id_label        = $GLOBALS['Language']->getText('admin_approve_pending_users', 'id');
        $this->email_label     = $GLOBALS['Language']->getText('admin_approve_pending_users', 'email');

        $this->registration_date_label = $GLOBALS['Language']->getText('admin_approve_pending_users', 'reg_date');
        $this->purpose_label           = $GLOBALS['Language']->getText('admin_approve_pending_users', 'purpose');
        $this->empty_label             = $GLOBALS['Language']->getText('admin_approve_pending_users', 'empty');
        $this->edit_label              = $GLOBALS['Language']->getText('admin_approve_pending_users', 'user_edit');
        $this->expiry_date_label       = $GLOBALS['Language']->getText('admin_approve_pending_users', 'expiry_date');

        $this->validate_label     = $GLOBALS['Language']->getText('admin_approve_pending_users', 'validate');
        $this->activate_label     = $GLOBALS['Language']->getText('admin_approve_pending_users', 'activate');
        $this->delete_label       = $GLOBALS['Language']->getText('admin_approve_pending_users', 'delete');
        $this->validate_all_label = $GLOBALS['Language']->getText('admin_approve_pending_users', 'validate_all');
        $this->activate_all_label = $GLOBALS['Language']->getText('admin_approve_pending_users', 'activate_all');

        $this->restricted_label     = $GLOBALS['Language']->getText('admin_approve_pending_users', 'status_restricted');
        $this->restricted_all_label = $GLOBALS['Language']->getText('admin_approve_pending_users', 'status_all_restricted');

        $this->purified_expiry_date_details = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('admin_approve_pending_users', 'expiry_date_directions'),
            CODENDI_PURIFIER_LIGHT
        );
    }

    private function getListOfUserIds(array $users)
    {
        return implode(
            ',',
            array_map(
                function ($user) {
                    return $user->id;
                },
                $users
            )
        );
    }
}
