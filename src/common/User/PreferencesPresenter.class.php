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
        CSRFSynchronizerToken $csrf_token,
        AccountTabPresenterCollection $tabs
    ) {
        $this->user                    = $user;
        $this->csrf_token              = $csrf_token;
        $this->csrf_input_html         = $csrf_token->fetchHTMLInput();

        $this->user_language               = $user->getShortLocale();
        $this->user_has_accessibility_mode = $user->getPreference(PFUser::ACCESSIBILITY_MODE);

        $this->tabs = $tabs;
    }

    public function real_name()
    {
        return $this->user->getRealName();
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
}
