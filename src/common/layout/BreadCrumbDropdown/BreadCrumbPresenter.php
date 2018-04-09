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

class BreadCrumbPresenter
{
    /** @var string */
    public $label;

    /** @var string */
    public $url;

    /** @var string */
    public $icon_name;

    /** @var bool */
    public $has_icon;

    /** @var BreadCrumbSubItem[] */
    public $sub_items;

    /** @var bool */
    public $has_sub_items;

    public function __construct(BreadCrumbSubItem $crumb)
    {
        $this->label     = $crumb->getLabel();
        $this->url       = $crumb->getUrl();
        $icon_name       = $crumb->getIconName();
        $this->has_icon  = ($icon_name !== null);
        $this->icon_name = ($this->has_icon) ? $icon_name : "";
    }

    public function setSubItems($sub_items)
    {
        $this->sub_items     = $sub_items;
        $this->has_sub_items = count($this->sub_items) > 0;
    }
}
