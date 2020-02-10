<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Layout\BreadCrumbDropdown;

class BreadCrumb
{
    /**
     * @var BreadCrumbLink
     */
    private $link;
    /**
     * @var BreadCrumbSubItems
     */
    private $sub_items;

    public function __construct(BreadCrumbLink $link)
    {
        $this->link      = $link;
        $this->sub_items = new BreadCrumbSubItems();
    }

    /**
     * @return BreadCrumbLink
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return BreadCrumbSubItems
     */
    public function getSubItems()
    {
        return $this->sub_items;
    }

    public function setSubItems(BreadCrumbSubItems $sub_items)
    {
        $this->sub_items = $sub_items;
    }
}
