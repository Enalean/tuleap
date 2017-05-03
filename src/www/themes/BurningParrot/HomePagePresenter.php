<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot;

use Admin_Homepage_LogoFinder;
use Codendi_HTMLPurifier;
use PFUser;
use User_LoginPresenter;

class HomePagePresenter
{
    public $headline;
    public $path_custom_logo;
    public $create_account_label;
    public $purified_welcome_message;
    /** @var PFUser */
    public $user;
    public $user_is_anonymous;
    public $welcome_back;
    public $most_secure_url;
    public $sign_in_title;
    public $login;
    public $username_placeholder;
    public $password_placeholder;
    public $sign_in_submit;
    public $forgot_password;
    public $not_a_member;
    public $display_homepage_login_form;
    public $is_custom_logo_used;

    /**
     * HomePagePresenter constructor.
     */
    public function __construct(
        $headline,
        PFUser $user,
        $most_secure_url,
        User_LoginPresenter $login,
        $display_homepage_login_form
    ) {
        $this->headline                    = $headline;
        $this->is_custom_logo_used         = Admin_Homepage_LogoFinder::isCustomLogoUsed();
        $this->path_custom_logo            = Admin_Homepage_LogoFinder::getCurrentUrl();
        $this->user                        = $user;
        $this->user_is_anonymous           = $user->isAnonymous();
        $this->most_secure_url             = $most_secure_url;
        $this->login                       = $login;
        $this->display_homepage_login_form = $display_homepage_login_form;

        $purifier        = Codendi_HTMLPurifier::instance();
        $welcome_message = $GLOBALS['Language']->getText('homepage', 'welcome_title');

        $this->purified_welcome_message = $purifier->purify($welcome_message, CODENDI_PURIFIER_LIGHT);

        $this->create_account_label = $GLOBALS['Language']->getText('homepage', 'create_account');
        $this->welcome_back         = $GLOBALS['Language']->getText('homepage', 'welcome_back');
        $this->username_placeholder = $GLOBALS['Language']->getText('homepage', 'username_placeholder');
        $this->password_placeholder = $GLOBALS['Language']->getText('homepage', 'password_placeholder');
        $this->sign_in_submit       = $GLOBALS['Language']->getText('homepage', 'sign_in_submit');
        $this->forgot_password      = $GLOBALS['Language']->getText('homepage', 'forgot_password');
        $this->not_a_member         = $GLOBALS['Language']->getText('homepage', 'not_a_member');
        $this->my_personal_page     = $GLOBALS['Language']->getText('homepage', 'my_personal_page');
    }
}
