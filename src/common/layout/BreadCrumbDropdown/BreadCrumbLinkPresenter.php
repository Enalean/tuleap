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

class BreadCrumbLinkPresenter
{
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $url;
    /**
     * @var bool
     */
    public $has_icon;
    /**
     * @var string
     */
    public $icon_name;
    /**
     * @var array<array{key: string, value: string}>
     * @psalm-readonly
     */
    public $data_attributes;

    public function __construct(BreadCrumbLink $link)
    {
        $this->label = $link->getLabel();
        $this->url   = $link->getUrl();

        $icon_name             = $link->getIconName();
        $this->has_icon        = ($icon_name !== '');
        $this->icon_name       = ($this->has_icon) ? $icon_name : "";
        $this->data_attributes = [];
        foreach ($link->getDataAttributes() as $key => $value) {
            $this->data_attributes[] = [
                'key'   => $key,
                'value' => $value,
            ];
        }
    }
}
