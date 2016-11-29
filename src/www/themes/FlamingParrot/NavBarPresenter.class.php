<?php
/**
 * Copyright (c) Enalean, 2013-2016. All Rights Reserved.
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

class FlamingParrot_NavBarPresenter {

    private $imgroot;

    /** @var PFUser */
    private $user;

    private $request_uri;

    /** @var HTTPRequest */
    private $request;

    private $title;

    /** @var FlamingParrot_SearchFormPresenter */
    private $search_form_presenter;

    private $selected_top_tab;

    private $display_new_account;

    /** @var string */
    public $motd;

    /** @var bool */
    public $has_motd;

    public $number_of_page_results;

    /** @var FlamingParrot_NavBarItemPresenter[] */
    public $navbar_items;

    /** @var Tuleap\User\UserActionPresenter[] */
    public $user_actions;

    /**
     * @var CSRFSynchronizerToken
     */
    public $logout_csrf;
    /**
     * @var URLRedirect
     */
    private $url_redirect;

    public function __construct(
        $imgroot,
        PFUser $user,
        $request_uri,
        $selected_top_tab,
        HTTPRequest $request,
        $title,
        $search_form_presenter,
        $display_new_account,
        $motd,
        FlamingParrot_NavBarItemPresentersCollection $navbar_items_collection,
        array $user_actions,
        CSRFSynchronizerToken $logout_csrf,
        URLRedirect $url_redirect
    ) {
        $this->imgroot                = $imgroot;
        $this->user                   = $user;
        $this->request_uri            = $request_uri;
        $this->request                = $request;
        $this->selected_top_tab       = $selected_top_tab;
        $this->title                  = $title;
        $this->search_form_presenter  = $search_form_presenter;
        $this->display_new_account    = $display_new_account;
        $this->motd                   = $motd;
        $this->has_motd               = ! empty($motd);
        $this->number_of_page_results = Search_SearchPlugin::RESULTS_PER_QUERY;
        $this->navbar_items           = $navbar_items_collection->getItems();
        $this->user_actions           = $user_actions;
        $this->logout_csrf            = $logout_csrf;
        $this->url_redirect           = $url_redirect;
    }

    public function imgroot() {
        return $this->imgroot;
    }

    public function user_is_logged_in() {
        return $this->user->isLoggedIn();
    }

    public function user_can_search() {
        return $this->user->isActive();
    }

    public function user_real_name() {
        return $this->user->getRealName();
    }

    public function has_user_avatar() {
        return $this->user->hasAvatar();
    }

    public function user_avatar() {
        return $this->user->getAvatarUrl();
    }

    public function search_form_presenter() {
        if ($this->userIsOnPageWithItsOwnSearchForm()) {
            return null;
        }
        return $this->search_form_presenter;
    }

    private function userIsOnPageWithItsOwnSearchForm() {
        return $this->getClassnameNavItemActive('/search/');
    }

    public function display_new_user() {
        return $this->display_new_account;
    }

    public function bookmark_title() {
        return urlencode(str_replace($GLOBALS['sys_name'].': ', '', html_entity_decode($this->title)));
    }

    public function bookmark_url() {
        return urlencode($this->request_uri);
    }

    public function my_index_text() {
        return $GLOBALS['Language']->getText('my_index','account_maintenance');
    }

    public function account_options_text() {
        return $GLOBALS['Language']->getText('account_options','preferences');
    }

    public function include_menu_text() {
        return $GLOBALS['Language']->getText('include_menu','logout');
    }

    public function menu_home_text() {
        return $GLOBALS['Language']->getText('menu', 'home');
    }

    public function include_menu_login_text() {
       return $GLOBALS['Language']->getText('include_menu','login');
    }

    public function include_menu_new_user_text() {
        return $GLOBALS['Language']->getText('include_menu','new_user');
    }

    public function include_menu_bookmark_this_page_text() {
        return $GLOBALS['Language']->getText('include_menu','bookmark_this_page');
    }

    public function search_placeholder() {
        return $GLOBALS['Language']->getText('include_menu','search');
    }

    public function nav_user_class() {
        return $this->getClassnameNavItemActive('/account/');
    }

    public function request_is_post() {
        return $this->request->isPost();
    }

    private function getClassnameNavItemActive($pathsToDetect, $toptab = null) {
        if ($toptab === $this->selected_top_tab) {
            $class = 'active';
        } else {
            if (!is_array($pathsToDetect)) {
                $pathsToDetect = array($pathsToDetect);
            }
            $class = '';
            while (!$class && (list(,$path) = each($pathsToDetect))) {
                if (strpos($this->request_uri, $path) === 0) {
                    $class = 'active';
                }
            }
        }
        return $class;
    }

    public function login_url()
    {
        return $this->url_redirect->buildReturnToLogin($_SERVER);
    }
}
