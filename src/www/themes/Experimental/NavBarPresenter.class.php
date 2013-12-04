<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Experimental_NavBarPresenter {

    private $imgroot;

    /** @var PFUser */
    private $user;

    private $request_uri;

    /** @var HTTPRequest */
    private $request;

    private $title;

    /** @var Experimental_SearchFormPresenter */
    private $search_form_presenter;

    private $selected_top_tab;

    private $project_list;

    private $display_new_account;

    public function __construct($imgroot, PFUser $user, $request_uri, $selected_top_tab, HTTPRequest $request, $title, $search_form_presenter, $project_list, $display_new_account) {
        $this->imgroot               = $imgroot;
        $this->user                  = $user;
        $this->request_uri           = $request_uri;
        $this->request               = $request;
        $this->selected_top_tab      = $selected_top_tab;
        $this->title                 = $title;
        $this->search_form_presenter = $search_form_presenter;
        $this->project_list          = $project_list;
        $this->display_new_account   = $display_new_account;
    }

    public function imgroot() {
        return $this->imgroot;
    }

    public function user_is_logged_in() {
        return $this->user->isLoggedIn();
    }

    public function user_is_admin() {
        return $this->user->isSuperUser();
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

    public function selected_top_tab() {
        return $this->selected_top_tab;
    }

    public function search_form_presenter() {
        return $this->search_form_presenter;
    }

    public function user_projects() {
        return $this->project_list;
    }

    public function display_new_user() {
        return $this->display_new_account;
    }

    public function bookmark_title() {
        return urlencode(str_replace($GLOBALS['sys_name'].': ', '', $this->title));
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

    public function menu_my_personnal_page_text() {
         return $GLOBALS['Language']->getText('menu', 'my_personal_page');
    }

    public function include_menu_register_new_proj_text() {
        return $GLOBALS['Language']->getText('include_menu','register_new_proj');
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

    public function nav_home_class() {
        $class = '';

        if ($this->request_uri == '/') {
            $class = 'active';
        }

        return $class;
    }

    public function nav_my_class() {
        return $this->getClassnameNavItemActive(array('/my/', '/widgets/'));
    }

    public function nav_project_class() {
        return $this->getClassnameNavItemActive(array('/softwaremap/', '/projects/', '/project/'));
    }

    public function nav_admin_class() {
        return $this->getClassnameNavItemActive('/admin/', 'admin');
    }

    public function nav_help_class() {
        return $this->getClassnameNavItemActive('/site/', 'site');
    }

    public function nav_user_class() {
        return $this->getClassnameNavItemActive('/account/');
    }

    public function project_registration() {
        return ((isset($GLOBALS['sys_use_project_registration']) && $GLOBALS['sys_use_project_registration'] == 1) || !isset($GLOBALS['sys_use_project_registration']));
    }

    public function nav_project_anonymous() {
        return (! $this->project_registration() && ! $this->user_projects());
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
}
