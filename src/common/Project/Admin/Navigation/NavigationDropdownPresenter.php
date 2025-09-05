<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Navigation;

class NavigationDropdownPresenter implements NavigationItem
{
    /**
     * @var NavigationDropdownItemPresenter[]
     */
    public $menu_items;

    public $label;

    public $is_active;

    public function __construct($label, $shortname, $current_pane_shortname, array $menu_items)
    {
        $this->menu_items = $menu_items;
        $this->label      = $label;
        $this->is_active  = ($current_pane_shortname === $shortname);
    }

    #[\Override]
    public function hasSubItems()
    {
        return true;
    }
}
