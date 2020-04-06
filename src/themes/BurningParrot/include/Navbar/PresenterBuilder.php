<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot\Navbar;

use EventManager;
use PFUser;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Links\LinkPresentersBuilder;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Links\LinksPresenter;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Projects\ProjectPresentersBuilder;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Projects\ProjectsPresenter;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Presenter as DropdownMenuItemPresenter;
use Tuleap\Theme\BurningParrot\Navbar\MenuItem\Presenter as MenuItemPresenter;
use URLRedirect;

class PresenterBuilder
{
    /** @var PFUser */
    private $current_user;

    /** @var array */
    private $extra_tabs;

    /** @var array */
    private $help_menu_items;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $registration_user_permission_checker;

    public function build(
        PFUser $current_user,
        array $extra_tabs,
        array $help_menu_items,
        URLRedirect $url_redirect,
        ProjectRegistrationUserPermissionChecker $registration_user_permission_checker
    ) {
        $this->current_user    = $current_user;
        $this->extra_tabs      = $extra_tabs;
        $this->help_menu_items = $help_menu_items;
        $this->registration_user_permission_checker = $registration_user_permission_checker;

        return new Presenter(
            new GlobalNavPresenter(
                $this->getGlobalMenuItems($current_user),
                $this->getGlobalDropdownMenuItems($current_user)
            ),
            new SearchPresenter($current_user),
            new UserNavPresenter(
                $this->current_user,
                $this->displayNewAccountMenuItem(),
                $url_redirect
            ),
            new JoinCommunityPresenter()
        );
    }

    private function getGlobalDropdownMenuItems(PFUser $user)
    {
        $global_dropdown_menu_items = array();

        $dropdown_menu_item_content_project_presenters_builder = new ProjectPresentersBuilder();
        $dropdown_menu_item_content_project_presenters         = $dropdown_menu_item_content_project_presenters_builder->build(
            $this->current_user
        );

        $is_project_dropdown_visible = $this->current_user->isLoggedIn() && (
                count($dropdown_menu_item_content_project_presenters) > 0 ||
                \ForgeConfig::get('sys_use_trove') ||
                $this->registration_user_permission_checker->isUserAllowedToCreateProjects($user)
            );

        if ($is_project_dropdown_visible) {
            $global_dropdown_menu_items[] = new DropdownMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'projects'),
                'fa fa-archive',
                new ProjectsPresenter(
                    'projects',
                    $this->registration_user_permission_checker->isUserAllowedToCreateProjects($user),
                    $dropdown_menu_item_content_project_presenters,
                ),
                'nav-dropdown-left'
            );
        }

        $dropdown_menu_item_content_link_presenters_builder = new LinkPresentersBuilder();
        $dropdown_menu_item_content_help_links_presenter    = $dropdown_menu_item_content_link_presenters_builder->build(
            $this->help_menu_items
        );
        if ($dropdown_menu_item_content_help_links_presenter) {
            $global_dropdown_menu_items[] = new DropdownMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'help'),
                'fa fa-question-circle',
                new LinksPresenter(
                    'help-dropdown',
                    $dropdown_menu_item_content_help_links_presenter
                ),
                ''
            );
        }

        $dropdown_menu_item_content_extra_links_presenter    = $dropdown_menu_item_content_link_presenters_builder->build(
            $this->extra_tabs
        );
        if ($dropdown_menu_item_content_extra_links_presenter) {
            $global_dropdown_menu_items[] = new DropdownMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'extras'),
                'fa fa-ellipsis-h',
                new LinksPresenter(
                    'extra-tabs-dropdown',
                    $dropdown_menu_item_content_extra_links_presenter
                ),
                ''
            );
        }

        return $global_dropdown_menu_items;
    }

    private function getGlobalMenuItems(PFUser $current_user)
    {
        if (! $current_user->isSuperUser()) {
            return [];
        }

        return [
            new MenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'site_admin'),
                '/admin/',
                'fa fa-cog',
                'go-to-admin',
                ['data-test=platform-administration-link']
            )
        ];
    }

    private function displayNewAccountMenuItem()
    {
        $display_new_user_menu_item = true;

        EventManager::instance()->processEvent(
            'display_newaccount',
            array('allow' => &$display_new_user_menu_item)
        );

        return $display_new_user_menu_item;
    }
}
