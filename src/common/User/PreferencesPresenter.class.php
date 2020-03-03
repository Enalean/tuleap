<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 *
 * phpcs:ignoreFile
 */

use Tuleap\User\Account\AccountTabPresenterCollection;

class User_PreferencesPresenter
{
    /** @var PFUser */
    private $user;
    private $can_change_email;

    private $extra_user_info;

    /** @var array */
    private $user_access;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    public $csrf_input_html;

    public $user_language;
    public $user_has_accessibility_mode;
    /**
     * @var AccountTabPresenterCollection
     */
    public $tabs;

    public function __construct(
        PFUser $user,
        $can_change_email,
        array $extra_user_info,
        CSRFSynchronizerToken $csrf_token,
        AccountTabPresenterCollection $tabs
    ) {
        $this->user                    = $user;
        $this->can_change_email        = $can_change_email;
        $this->extra_user_info         = $extra_user_info;
        $this->csrf_token              = $csrf_token;
        $this->csrf_input_html         = $csrf_token->fetchHTMLInput();

        $this->user_language               = $user->getShortLocale();
        $this->user_has_accessibility_mode = $user->getPreference(PFUser::ACCESSIBILITY_MODE);

        $this->tabs = $tabs;
    }

    public function avatar()
    {
        return $this->user->fetchHtmlAvatar();
    }

    public function real_name()
    {
        return $this->user->getRealName();
    }

    public function welcome_user()
    {
        return $GLOBALS['Language']->getText('account_options', 'welcome') . ' ' . $this->user->getRealName();
    }

    public function user_email_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'email_address');
    }

    public function user_email_value()
    {
        return $this->user->getEmail();
    }

    public function can_change_email()
    {
        return $this->can_change_email;
    }

    public function change_email()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_email_address');
    }

    public function change_avatar()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_avatar');
    }

    public function select_avatar()
    {
        return $GLOBALS['Language']->getText('account_options', 'select_avatar');
    }

    public function use_default_avatar()
    {
        return $GLOBALS['Language']->getText('account_options', 'use_default_avatar');
    }

    public function change_avatar_desc()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_avatar_desc');
    }

    public function btn_save_avatar_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'btn_save_avatar_label');
    }

    public function timezone_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'timezone');
    }

    public function timezone_value()
    {
        return $this->user->getTimezone();
    }

    public function change_timezone()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_timezone');
    }

    public function extra_user_info()
    {
        return $this->extra_user_info;
    }

    /* MODAL */

    public function btn_close_label()
    {
        return $GLOBALS['Language']->getText('global', 'btn_close');
    }
}
