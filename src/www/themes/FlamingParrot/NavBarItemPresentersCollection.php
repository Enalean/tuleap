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

class FlamingParrot_NavBarItemPresentersCollection
{

    private $items = array();

    public function addItem(FlamingParrot_NavBarItemPresenter $item)
    {
        $this->items[] = $item;
    }

    public function addItemAfterAnotherOne($sibling_id, FlamingParrot_NavBarItemPresenter $item)
    {
        $index = 0;
        foreach ($this->items as $previous) {
            $index++;
            if ($previous->id == $sibling_id) {
                break;
            }
        }

        array_splice($this->items, $index, 0, array($item));
    }

    public function getItems()
    {
        return $this->items;
    }
}
