<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
use Tuleap\layout\HomePage\NewsCollection;
use Tuleap\layout\HomePage\StatisticsCollection;
use User_LoginPresenter;

class HomePagePresenter
{
    public $purified_headline;
    public $path_custom_logo;
    public $create_account_label;
    public $purified_welcome_message;
    /** @var PFUser */
    public $user;
    public $user_is_anonymous;
    public $welcome_back;
    public $most_secure_url;
    public $login;
    public $username_placeholder;
    public $password_placeholder;
    public $sign_in_submit;
    public $forgot_password;
    public $not_a_member;
    public $is_custom_logo_used;
    public $display_new_account_button;
    public $login_url;

    public $my_personal_page;

    public $has_statistics;
    public $statistics;

    public $has_news;
    public $news;

    public function __construct(
        string $headline,
        PFUser $user,
        $most_secure_url,
        User_LoginPresenter $login,
        $display_new_account_button,
        $login_url,
        StatisticsCollection $statistics_collection,
        NewsCollection $news_collection
    ) {
        $this->is_custom_logo_used        = Admin_Homepage_LogoFinder::isCustomLogoUsed();
        $this->path_custom_logo           = Admin_Homepage_LogoFinder::getCurrentUrl();
        $this->user                       = $user;
        $this->user_is_anonymous          = $user->isAnonymous();
        $this->most_secure_url            = $most_secure_url;
        $this->login                      = $login;
        $this->display_new_account_button = $display_new_account_button;
        $this->login_url                  = $login_url;

        $purifier        = Codendi_HTMLPurifier::instance();
        $welcome_message = $GLOBALS['Language']->getText('homepage', 'welcome_title', \ForgeConfig::get('sys_name'));

        $this->purified_welcome_message = $purifier->purify($welcome_message, CODENDI_PURIFIER_LIGHT);
        $this->purified_headline        = $purifier->purify($headline, CODENDI_PURIFIER_LIGHT);

        $this->create_account_label = $GLOBALS['Language']->getText('homepage', 'create_account');
        $this->welcome_back         = $GLOBALS['Language']->getText('homepage', 'welcome_back');
        $this->username_placeholder = $GLOBALS['Language']->getText('homepage', 'username_placeholder');
        $this->password_placeholder = $GLOBALS['Language']->getText('homepage', 'password_placeholder');
        $this->sign_in_submit       = $GLOBALS['Language']->getText('homepage', 'sign_in_submit');
        $this->forgot_password      = $GLOBALS['Language']->getText('homepage', 'forgot_password');
        $this->not_a_member         = $GLOBALS['Language']->getText('homepage', 'not_a_member');
        $this->my_personal_page     = $GLOBALS['Language']->getText('homepage', 'my_personal_page');
        $this->has_statistics       = $statistics_collection->hasStatistics();
        $this->statistics           = $statistics_collection->getStatistics();
        $this->has_news             = $news_collection->hasNews();
        $this->news                 = $news_collection->getNews();
    }
}
