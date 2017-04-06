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

namespace Tuleap\Theme\BurningParrot\Navbar\Dropdown;

use Tuleap\Theme\BurningParrot\Navbar\GlobalMenuItemPresenter;

class DropdownItemsPresenterBuilder
{
    /** @var string */
    private $id;

    /** @var array */
    private $items;

    public function build(
        $id,
        array $items
    ) {
        $this->id    = $id;
        $this->items = $items;

        return $this->getDropdownItemsPresenter();
    }

    private function getDropdownItemsPresenter()
    {
        $items = array();
        $links = array();
        foreach ($this->items as $item) {
            $items[] = new GlobalMenuItemPresenter(
                $item['title'],
                $item['link'],
                '',
                ''
            );
            $links[] = $item['link'];
        }

        if (count($links) > 0) {
            $dropdown_items = new DropdownItemsPresenter(
                $this->id,
                $items
            );

            return $dropdown_items;
        }
    }
}
