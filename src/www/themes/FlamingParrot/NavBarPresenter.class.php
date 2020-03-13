<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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


class FlamingParrot_NavBarPresenter
{
    public $search_label;
    public $history;
    public $empty_history;
    public $error_fetch;
    public $error_clear;
    public $homepage_label;

    private $imgroot;

    /** @var PFUser */
    private $user;

    public $current_project_navbar_info;

    private $request_uri;

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

    /**
     * @var string
     */
    public $logout_label;

    /**
     * @var string
     */
    public $my_account_label;

    /**
     * @var string
     */
    public $join_community_title;

    public function __construct(
        $imgroot,
        PFUser $user,
        $current_project_navbar_info,
        $request_uri,
        $selected_top_tab,
        $search_form_presenter,
        $display_new_account,
        $motd,
        FlamingParrot_NavBarItemPresentersCollection $navbar_items_collection,
        array $user_actions,
        CSRFSynchronizerToken $logout_csrf,
        URLRedirect $url_redirect
    ) {
        $this->imgroot                     = $imgroot;
        $this->user                        = $user;
        $this->current_project_navbar_info = $current_project_navbar_info;
        $this->request_uri                 = $request_uri;
        $this->selected_top_tab            = $selected_top_tab;
        $this->search_form_presenter       = $search_form_presenter;
        $this->display_new_account         = $display_new_account;
        $this->motd                        = $motd;
        $this->has_motd                    = ! empty($motd);
        $this->number_of_page_results      = Search_SearchPlugin::RESULTS_PER_QUERY;
        $this->navbar_items                = $navbar_items_collection->getItems();
        $this->user_actions                = $user_actions;
        $this->logout_csrf                 = $logout_csrf;
        $this->url_redirect                = $url_redirect;

        $this->logout_label         = $GLOBALS['Language']->getText('include_menu', 'logout');
        $this->my_account_label     = $GLOBALS['Language']->getText('my_index', 'account_maintenance');
        $this->join_community_title = $GLOBALS['Language']->getText('include_menu', 'join_community');
        $this->search_label         = $GLOBALS['Language']->getText('include_menu', 'search');

        $this->current_user_id = $user->getId();
        $this->history         = _('History');
        $this->clear_history   = _('Clear history');
        $this->empty_history   = _('Your history is empty');
        $this->error_fetch     = _('An error occurred while fetching the content of your history');
        $this->error_clear     = _('An error occurred while clearing the content of your history');
        $this->homepage_label  = _('Homepage');
    }

    public function imgroot()
    {
        return $this->imgroot;
    }

    public function user_is_logged_in()
    {
        return $this->user->isLoggedIn();
    }

    public function user_can_search()
    {
        return $this->user->isActive();
    }

    public function user_real_name()
    {
        return $this->user->getRealName();
    }

    public function user_login_name()
    {
        return "@" . $this->user->getUnixName();
    }

    public function has_user_avatar()
    {
        return $this->user->hasAvatar();
    }

    public function user_avatar()
    {
        return $this->user->getAvatarUrl();
    }

    public function search_form_presenter()
    {
        if ($this->userIsOnPageWithItsOwnSearchForm()) {
            return null;
        }
        return $this->search_form_presenter;
    }

    private function userIsOnPageWithItsOwnSearchForm()
    {
        return $this->getClassnameNavItemActive('/search/');
    }

    public function display_new_user()
    {
        return $this->display_new_account;
    }

    public function account_options_text()
    {
        return $GLOBALS['Language']->getText('account_options', 'preferences');
    }

    public function menu_home_text()
    {
        return $GLOBALS['Language']->getText('menu', 'home');
    }

    public function include_menu_login_text()
    {
        return $GLOBALS['Language']->getText('include_menu', 'login');
    }

    public function include_menu_new_user_text()
    {
        return $GLOBALS['Language']->getText('include_menu', 'new_user');
    }

    public function search_placeholder()
    {
        return $GLOBALS['Language']->getText('include_menu', 'search');
    }

    private function getClassnameNavItemActive($pathsToDetect, $toptab = null)
    {
        if ($toptab === $this->selected_top_tab) {
            return 'active';
        } else {
            if (!is_array($pathsToDetect)) {
                $pathsToDetect = array($pathsToDetect);
            }
            foreach ($pathsToDetect as $path) {
                if (strpos($this->request_uri, $path) === 0) {
                    return 'active';
                }
            }
        }
        return '';
    }

    public function login_url()
    {
        return $this->url_redirect->buildReturnToLogin($_SERVER);
    }
}
