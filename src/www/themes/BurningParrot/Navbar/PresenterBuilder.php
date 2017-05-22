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

use Admin_Homepage_Dao;
use HTTPRequest;
use PFUser;
use EventManager;
use Tuleap\BurningParrotCompatiblePageDetector;
use Tuleap\Theme\BurningParrot\Navbar\Dropdown\DropdownItemsPresenterBuilder;
use Tuleap\Theme\BurningParrot\Navbar\Dropdown\DropdownProjectsPresenter;
use Tuleap\Theme\BurningParrot\Navbar\Dropdown\DropdownProjectsPresenterBuilder;
use Tuleap\Theme\BurningParrot\Navbar\Project\ProjectPresenterBuilder;
use URLRedirect;

class PresenterBuilder
{
    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $current_user;

    /** @var array */
    private $extra_tabs;

    /** @var array */
    private $help_menu_items;

    public function build(
        HTTPRequest $request,
        PFUser $current_user,
        array $extra_tabs,
        array $help_menu_items,
        URLRedirect $url_redirect
    ) {
        $this->request         = $request;
        $this->current_user    = $current_user;
        $this->extra_tabs      = $extra_tabs;
        $this->help_menu_items = $help_menu_items;

        return new Presenter(
            new GlobalNavPresenter(
                $this->getGlobalMenuItems($current_user),
                $this->getGlobalNavbarDropdownMenuItems()
            ),
            new SearchPresenter(),
            new UserNavPresenter(
                $this->request,
                $this->current_user,
                $this->displayNewAccountMenuItem(),
                $url_redirect
            ),
            new BurningParrotCompatiblePageDetector(
                new Admin_Homepage_Dao()
            ),
            $this->current_user,
            new JoinCommunityPresenter()
        );
    }

    private function getGlobalNavbarDropdownMenuItems()
    {
        $navbar_dropdown_items_builder     = new DropdownItemsPresenterBuilder();
        $global_navbar_dropdown_menu_items = array();

        $projects_builder = new ProjectPresenterBuilder();
        $projects         = new DropdownProjectsPresenter(
            'projects',
            $projects_builder->build($this->current_user)
        );
        if ($projects) {
            $global_navbar_dropdown_menu_items[] = new GlobalNavbarDropdownMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'projects'),
                'fa fa-archive',
                $projects
            );
        }

        $help_dropdowns = $navbar_dropdown_items_builder->build('help-dropdown', $this->help_menu_items);
        if ($help_dropdowns) {
            $global_navbar_dropdown_menu_items[] = new GlobalNavbarDropdownMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'help'),
                'fa fa-question-circle',
                $help_dropdowns
            );
        }

        $dropdowns = $navbar_dropdown_items_builder->build('extra-tabs-dropdown', $this->extra_tabs);
        if ($dropdowns) {
            $global_navbar_dropdown_menu_items[] = new GlobalNavbarDropdownMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'extras'),
                'fa fa-ellipsis-h',
                $dropdowns
            );
        }

        return $global_navbar_dropdown_menu_items;
    }

    private function getGlobalMenuItems(PFUser $current_user)
    {
        $global_menu_items = array();

        if ($current_user->isSuperUser()) {
            $global_menu_items[] = new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'site_admin'),
                '/admin/',
                'fa fa-cog',
                'go-to-admin'
            );
        }

        return $global_menu_items;
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
