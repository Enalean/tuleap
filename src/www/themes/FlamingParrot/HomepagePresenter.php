<?php
/**
  * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class FlamingParrot_HomepagePresenter {

    /** @var string */
    public $display_homepage_news;

    /** @var FlamingParrot_HomepageNewsPresenter[] */
    public $news;

    /** @var User_LoginPresenter */
    public $login;

    /** @var string */
    public $most_secure_url;

    /** @var int */
    public $nb_users;

    /** @var int */
    public $nb_projects;

    /** @var int */
    public $headline;

    /** @var int */
    public $logo;

    /** @var PFUser */
    public $user;

    /** @var boolean */
    public $user_is_anonymous;

    /** @var boolean */
    public $display_news_xor_signin;

    /** @var boolean */
    public $display_news_and_signin;

    /** @var boolean */
    public $do_not_display_news_and_signin;

    /** @var string html */
    public $awesomeness;

    /**
     * @var boolean
     */
    public $display_homepage_login_form;

    public function __construct(
        $headline,
        $nb_projects,
        $nb_users,
        $most_secure_url,
        User_LoginPresenter $login,
        $display_homepage_news,
        $display_homepage_login_form,
        $news,
        $user,
        $awesomeness
    ) {
        $this->news                        = $news;
        $this->login                       = $login;
        $this->nb_users                    = $nb_users;
        $this->headline                    = $headline;
        $this->path_logo                   = Admin_Homepage_LogoFinder::getCurrentUrl();
        $this->awesomeness                 = $awesomeness;
        $this->nb_projects                 = $nb_projects;
        $this->user                        = $user;
        $this->user_is_anonymous           = $user->isAnonymous();
        $this->most_secure_url             = $most_secure_url;
        $this->display_homepage_news       = $display_homepage_news && $news;
        $this->display_homepage_login_form = $display_homepage_login_form;
    }

    public function discover_tuleap_awesomeness() {
        return $GLOBALS['Language']->getText('homepage', 'discover_tuleap_awesomeness');
    }

    public function latest_news() {
        return $GLOBALS['Language']->getText('homepage', 'latest_news');
    }

    public function nb_project_label() {
        return ($this->nb_projects > 1) ? $GLOBALS['Language']->getText('homepage', 'nb_projects_label') : $GLOBALS['Language']->getText('homepage', 'nb_project_label');
    }

    public function nb_user_label() {
        return ($this->nb_users > 1) ? $GLOBALS['Language']->getText('homepage', 'nb_users_label') : $GLOBALS['Language']->getText('homepage', 'nb_user_label');
    }

    public function sign_in_title() {
        return $GLOBALS['Language']->getText('homepage', 'sign_in_title');
    }

    public function username_placeholder() {
        return $GLOBALS['Language']->getText('homepage', 'username_placeholder');
    }

    public function password_placeholder() {
        return $GLOBALS['Language']->getText('homepage', 'password_placeholder');
    }

    public function sign_in_submit() {
        return $GLOBALS['Language']->getText('homepage', 'sign_in_submit');
    }

    public function forgot_password() {
        return $GLOBALS['Language']->getText('homepage', 'forgot_password');
    }

    public function sign_in_or() {
        return $GLOBALS['Language']->getText('homepage', 'sign_in_or');
    }

    public function not_a_member() {
        return $GLOBALS['Language']->getText('homepage', 'not_a_member');
    }

    public function register() {
        return $GLOBALS['Language']->getText('homepage', 'register');
    }

    public function welcome_back() {
        return $GLOBALS['Language']->getText('homepage', 'welcome_back');
    }

}