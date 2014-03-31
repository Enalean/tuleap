<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_PreferencesPresenter {

    /** @var PFUser */
    private $user;
    public  $can_change_real_name;
    private $can_change_email;
    private $can_change_password;

    private $extra_user_info;

    /** @var array */
    private $user_access;

    /** string */
    private $third_party_html;

    private $ssh_keys_extra_html;

    public function __construct(
        PFUser $user,
        $can_change_real_name,
        $can_change_email,
        $can_change_password,
        array $extra_user_info,
        array $user_access,
        $ssh_keys_extra_html,
        $third_party_html
    ) {
        $this->user                 = $user;
        $this->can_change_real_name = $can_change_real_name;
        $this->can_change_email     = $can_change_email;
        $this->can_change_password  = $can_change_password;
        $this->extra_user_info      = $extra_user_info;
        $this->user_access          = $user_access;
        $this->ssh_keys_extra_html  = $ssh_keys_extra_html;
        $this->third_party_html     = $third_party_html;
    }

    public function has_avatar() {
        return Config::get('sys_enable_avatars');
    }

    public function avatar() {
        return $this->user->fetchHtmlAvatar();
    }

    public function change_real_name() {
        return $GLOBALS['Language']->getText('account_options', 'change_real_name');
    }

    public function real_name() {
        return $this->user->getRealName();
    }

    public function welcome_user() {
        return $GLOBALS['Language']->getText('account_options', 'welcome') . ' ' . $this->user->getRealName();
    }

    public function user_id_label() {
        return $GLOBALS['Language']->getText('account_options', 'user_id');
    }

    public function user_id_value() {
        return $this->user->getId();
    }

    public function user_email_label() {
        return $GLOBALS['Language']->getText('account_options', 'email_address');
    }

    public function user_email_value() {
        return $this->user->getEmail();
    }

    public function can_change_email() {
        return $this->can_change_email;
    }

    public function change_email() {
        return $GLOBALS['Language']->getText('account_options', 'change_email_address');
    }

    public function can_change_password() {
        return $this->can_change_password;
    }

    public function change_password() {
        return $GLOBALS['Language']->getText('account_options', 'change_password');
    }

    public function member_since_label() {
        return $GLOBALS['Language']->getText('account_options', 'member_since');
    }

    public function member_since_value() {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->user->getAddDate());
    }

    public function timezone_label() {
        return $GLOBALS['Language']->getText('account_options', 'timezone');
    }

    public function timezone_value() {
        return $this->user->getTimezone();
    }

    public function change_timezone() {
        return $GLOBALS['Language']->getText('account_options', 'change_timezone');
    }

    public function extra_user_info() {
        return $this->extra_user_info;
    }

    public function shell_account_title() {
        return $GLOBALS['Language']->getText('account_options', 'shell_account_title');
    }

    public function ssh_keys_count_label() {
        return $GLOBALS['Language']->getText('account_options', 'shell_shared_keys');
    }

    public function ssh_keys_count() {
        return count($this->user->getAuthorizedKeysArray());
    }

    public function ssh_keys_label() {
        return 'Key';
    }

    public function ssh_keys_list() {
        $keys = array();
        foreach ($this->user->getAuthorizedKeysArray() as $key) {
            $keys[] = array('ssh_key_value' => substr($key, 0, 20).'...'.substr($key, -20));
        }
        return $keys;
    }

    public function ssh_keys_extra_html() {
        return $this->ssh_keys_extra_html;
    }

    public function authentication_attempts_title() {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_title');
    }

    public function last_successful_login_label() {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_last_success');
    }

    public function last_successful_login_value() {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->user_access['last_auth_success']);
    }

    public function last_login_failure_label() {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_last_failure');
    }

    public function last_login_failure_value() {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->user_access['last_auth_failure']);
    }

    public function number_login_failure_label() {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_nb_failure');
    }

    public function number_login_failure_value() {
        return $this->user_access['nb_auth_failure'];
    }

    public function previous_successful_login_label() {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_prev_success');
    }

    public function previous_successful_login_value() {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->user_access['prev_auth_success']);
    }

    public function third_party_applications_title() {
        return 'Third party applications';
    }

    public function third_party_applications_content() {
        return $this->third_party_html;
    }

    public function user_legal() {
        ob_start();
        include $GLOBALS['Language']->getContent('account/user_legal');
        return ob_get_clean();
    }

    public function add_ssh_key_button() {
        return $GLOBALS['Language']->getText('account_options', 'shell_edit_keys');
    }

    public function has_ssh_key() {
        return $this->ssh_keys_count() > 0;
    }
}
