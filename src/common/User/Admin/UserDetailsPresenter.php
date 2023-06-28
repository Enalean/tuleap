<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use DateTimeImmutable;
use ForgeConfig;
use PFUser;
use Tuleap\WebAuthn\Source\AuthenticatorPresenter;
use User_UserStatusManager;

class UserDetailsPresenter
{
    public const ADDITIONAL_DETAILS = 'additional_details';

    public $name;
    public $login;
    public $id;
    public $email;
    public $has_avatar;
    public $purpose;
    public $display_purpose;
    public $access;
    public $change_password;
    public $access_title;
    public $account_details;
    public $current_projects;
    public $change_passwd;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    public $projects;
    public $has_projects;
    public $is_admin;
    public $no_project;
    public $shell;
    public $shells;
    public $unix_status_label;
    public $unix_status;
    public $status_label;
    public $email_label;
    public $status;
    public $nb_project_user_is_member_of_that_dont_accept_restricted;
    public $name_label;
    public $login_label;
    public $password_label;
    public $expiry_date_label;
    public $more_title;
    public $has_additional_details;
    public $additional_details;
    public $expiry;
    public $is_in_limbo;
    public $current_status_id;
    public $current_status_label;

    /**
     * @var bool
     */
    public $user_has_rest_read_only_administration_delegation;
    /**
     * @var string
     */
    public $avatar_url;

    /**
     * @param AuthenticatorPresenter[] $authenticators
     */
    public function __construct(
        PFUser $user,
        array $projects,
        UserDetailsAccessPresenter $access,
        UserChangePasswordPresenter $change_password,
        \CSRFSynchronizerToken $csrf_token,
        array $additional_details,
        array $more,
        array $shells,
        array $status,
        int $nb_project_user_is_member_of_that_dont_accept_restricted,
        array $unix_status,
        bool $user_has_rest_read_only_administration_delegation,
        public readonly bool $webauthn_enabled,
        public readonly array $authenticators,
    ) {
        $this->id    = $user->getId();
        $this->name  = $user->getRealName();
        $this->login = $user->getUserName();
        $this->email = $user->getEmail();

        if ((int) $user->getExpiryDate() !== 0) {
            $this->expiry = (new DateTimeImmutable())->setTimestamp((int) $user->getExpiryDate())->format('Y-m-d');
        } else {
            $this->expiry = "";
        }

        $this->has_avatar = $user->hasAvatar();
        $this->avatar_url = $user->getAvatarUrl();

        $this->access                                                   = $access;
        $this->change_password                                          = $change_password;
        $this->csrf_token                                               = $csrf_token;
        $this->additional_details                                       = $additional_details;
        $this->shells                                                   = $shells;
        $this->unix_status                                              = $unix_status;
        $this->status                                                   = $status;
        $this->nb_project_user_is_member_of_that_dont_accept_restricted = $nb_project_user_is_member_of_that_dont_accept_restricted;
        $this->more                                                     = $more;

        $this->projects     = $projects;
        $this->has_projects = count($projects) > 0;

        $this->display_purpose = ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1;
        $this->purpose         = $user->getRegisterPurpose();
        if (! $this->purpose) {
            $this->purpose = false;
        }

        $this->account_details    = $GLOBALS['Language']->getText('admin_usergroup', 'account_details');
        $this->access_title       = $GLOBALS['Language']->getText('admin_usergroup', 'access');
        $this->current_projects   = $GLOBALS['Language']->getText('admin_usergroup', 'current_projects');
        $this->change_passwd      = $GLOBALS['Language']->getText('admin_usergroup', 'change_passwd');
        $this->administrator      = $GLOBALS['Language']->getText('admin_usergroup', 'is_admin');
        $this->no_project         = $GLOBALS['Language']->getText('admin_usergroup', 'no_project');
        $this->shell              = $GLOBALS['Language']->getText('admin_usergroup', 'shell');
        $this->unix_status_label  = $GLOBALS['Language']->getText('admin_usergroup', 'unix_status');
        $this->status_label       = $GLOBALS['Language']->getText('admin_usergroup', 'status');
        $this->email_label        = $GLOBALS['Language']->getText('admin_usergroup', 'email');
        $this->name_label         = _('Real name');
        $this->login_label        = _('Login');
        $this->password_label     = _('Password');
        $this->expiry_date_label  = $GLOBALS['Language']->getText('admin_usergroup', 'expiry_date');
        $this->more_title         = $GLOBALS['Language']->getText('admin_usergroup', 'more_info');
        $this->update_information = $GLOBALS['Language']->getText('admin_usergroup', 'update_information');
        $this->purpose_label      = $GLOBALS['Language']->getText('admin_usergroup', 'purpose_label');
        $this->empty_purpose      = $GLOBALS['Language']->getText('admin_usergroup', 'empty_purpose');

        $this->has_additional_details = count($this->additional_details) > 0;

        $this->is_in_limbo          = in_array(
            $user->getStatus(),
            [PFUser::STATUS_PENDING, PFUser::STATUS_VALIDATED, PFUser::STATUS_VALIDATED_RESTRICTED]
        );
        $this->current_status_id    = $user->getStatus();
        $this->current_status_label = $this->getCurrentStatusLabel($this->current_status_id);

        $this->user_has_rest_read_only_administration_delegation = $user_has_rest_read_only_administration_delegation;
    }

    private function getCurrentStatusLabel($current_status_id)
    {
        $labels = [
            PFUser::STATUS_ACTIVE               => $GLOBALS['Language']->getText('admin_userlist', 'active'),
            PFUser::STATUS_RESTRICTED           => $GLOBALS['Language']->getText('admin_userlist', 'restricted'),
            PFUser::STATUS_VALIDATED_RESTRICTED => $GLOBALS['Language']->getText('admin_userlist', 'validated_restricted'),
            PFUser::STATUS_DELETED              => $GLOBALS['Language']->getText('admin_userlist', 'deleted'),
            PFUser::STATUS_SUSPENDED            => $GLOBALS['Language']->getText('admin_userlist', 'suspended'),
            PFUser::STATUS_PENDING              => $GLOBALS['Language']->getText('admin_userlist', 'pending'),
            PFUser::STATUS_VALIDATED            => $GLOBALS['Language']->getText('admin_userlist', 'validated'),
        ];

        return $labels[$current_status_id];
    }
}
