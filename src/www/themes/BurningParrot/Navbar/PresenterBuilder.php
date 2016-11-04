<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use HTTPRequest;
use PFUser;
use EventManager;
use Tuleap\Theme\BurningParrot\Navbar\Dropdown\DropdownItemsPresenterBuilder;
use Tuleap\Theme\BurningParrot\Navbar\Dropdown\DropdownProjectsPresenterBuilder;
use Tuleap\Theme\BurningParrot\Navbar\Project\ProjectPresenterBuilder;

class PresenterBuilder
{
    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $current_user;

    /** @var array */
    private $extra_tabs;

    public function build(
        HTTPRequest $request,
        PFUser $current_user,
        array $extra_tabs
    ) {
        $this->request      = $request;
        $this->current_user = $current_user;
        $this->extra_tabs   = $extra_tabs;

        return new Presenter(
            new GlobalNavPresenter(
                $this->getGlobalMenuItems(),
                $this->getGlobalNavbarDropdownMenuItems()
            ),
            new SearchPresenter(),
            new UserNavPresenter(
                $this->request,
                $this->current_user,
                $this->displayNewAccountMenuItem()
            )
        );
    }

    private function getGlobalNavbarDropdownMenuItems()
    {
        $projects_builder                 = new ProjectPresenterBuilder();
        $navbar_dropdown_items_builder    = new DropdownItemsPresenterBuilder();
        $navbar_dropdown_projects_builder = new DropdownProjectsPresenterBuilder();

        $projects  = $navbar_dropdown_projects_builder->build($projects_builder->build($this->current_user));
        $dropdowns = $navbar_dropdown_items_builder->build($this->extra_tabs);

        return array(
            new GlobalNavbarDropdownMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'projects'),
                'fa fa-archive',
                $projects
            ),
            new GlobalNavbarDropdownMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'extras'),
                'fa fa-ellipsis-h',
                $dropdowns
            )
        );
    }

    private function getGlobalMenuItems()
    {
        return array(
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'help'),
                '/site/',
                'fa fa-question-circle',
                ''
            ),
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'site_admin'),
                '/admin/',
                'fa fa-cog',
                'go-to-admin'
            )
        );
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
