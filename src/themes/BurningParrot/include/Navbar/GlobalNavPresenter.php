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

use Tuleap\Theme\BurningParrot\Navbar\MenuItem\Presenter as MenuItemPresenter;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Presenter as DropdownMenuItemPresenter;

class GlobalNavPresenter
{
    /** @var MenuItemPresenter[] */
    public $menu_item_presenters;

    /** @var DropdownMenuItemPresenter[] */
    public $dropdown_menu_item_presenters;

    public function __construct(array $menu_item_presenters, $dropdown_menu_item_presenters)
    {
        $this->menu_item_presenters          = $menu_item_presenters;
        $this->dropdown_menu_item_presenters = $dropdown_menu_item_presenters;
    }
}
