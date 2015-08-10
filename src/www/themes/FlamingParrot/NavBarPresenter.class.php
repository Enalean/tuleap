<?php
/**
 * Copyright (c) Enalean, 2013-2015. All Rights Reserved.
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

    private $project_list;

    private $display_new_account;

    /** @var string */
    public $motd;

    /** @var bool */
    public $has_motd;

    /** @var array */
    private $extra_tabs;

    public $number_of_page_results;

    public function __construct(
        $imgroot,
        PFUser $user,
        $request_uri,
        $selected_top_tab,
        HTTPRequest $request,
        $title,
        $search_form_presenter,
        $project_list,
        $display_new_account,
        $motd,
        $extra_tabs
    ) {
        $this->imgroot                = $imgroot;
        $this->user                   = $user;
        $this->request_uri            = $request_uri;
        $this->request                = $request;
        $this->selected_top_tab       = $selected_top_tab;
        $this->title                  = $title;
        $this->search_form_presenter  = $search_form_presenter;
        $this->project_list           = $project_list;
        $this->display_new_account    = $display_new_account;
        $this->motd                   = $motd;
        $this->has_motd               = ! empty($motd);
        $this->extra_tabs             = $extra_tabs;
        $this->number_of_page_results = Search_SearchPlugin::RESULTS_PER_QUERY;
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

    public function selected_top_tab() {
        return $this->selected_top_tab;
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

    public function user_projects() {
        return $this->project_list;
    }

    public function user_has_projects() {
        return count($this->project_list) > 0;
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

    public function menu_projects_text() {
        return $GLOBALS['Language']->getText('include_menu','projects');
    }

    public function is_trove_cat_enabled() {
        return $GLOBALS['sys_use_trove'] != 0;
    }

    public function browse_projects_text() {
        return $GLOBALS['Language']->getText('include_menu','browse_projects');
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

    public function filter_project() {
        return $GLOBALS['Language']->getText('include_menu','filter_project');
    }

    public function get_help() {
        return $GLOBALS['Language']->getText('include_menu','get_help');
    }

    public function help() {
        return $GLOBALS['Language']->getText('include_menu','help');
    }

    public function contact_us() {
        return $GLOBALS['Language']->getText('include_menu','contact_us');
    }

    public function soap_api() {
        return $GLOBALS['Language']->getText('include_menu','soap_api');
    }

    public function search_placeholder() {
        return $GLOBALS['Language']->getText('include_menu','search');
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
        return $this->getClassnameNavItemActive(array('/site/', '/contact.php', '/soap/index.php'), 'site');
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

    public function extra_tabs() {
        return $this->extra_tabs;
    }

    public function there_are_extra_tabs() {
        return count($this->extra_tabs) > 0;
    }

    public function extra_tabs_active() {
        $current_page = getStringFromServer('REQUEST_URI');

        foreach ($this->extra_tabs as $tab) {
            if (strstr($current_page, $tab['link'])) {
                return 'active';
            }
        }

        return '';
    }

    public function extras_text() {
        return $GLOBALS['Language']->getText('include_menu','extras');
    }

    public function return_to() {
        $request_uri = $_SERVER['REQUEST_URI'];

        if ($this->isUserTryingToLogIn($request_uri)) {
            return urlencode($this->request->get('return_to'));
        }

        if ($this->isUserTryingToRegister($request_uri)) {
            return false;
        }

        return $request_uri;
    }

    private function isUserTryingToLogIn($request_uri) {
        return strpos($request_uri, '/account/login.php') === 0;
    }

    private function isUserTryingToRegister($request_uri) {
        return strpos($request_uri, '/account/register.php') === 0;
    }
}
