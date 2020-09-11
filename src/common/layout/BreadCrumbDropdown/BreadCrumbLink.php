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

class BreadCrumbLink
{
    /** @var string */
    private $label;

    /** @var string */
    private $url;

    /** @var string */
    private $icon_name;

    /**
     * @var array<string, string>
     */
    private $data_attributes = [];

    public function __construct(
        $label,
        $url
    ) {
        $this->label     = $label;
        $this->url       = $url;
        $this->icon_name = '';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getIconName()
    {
        return $this->icon_name;
    }

    /**
     * @param string $icon_name
     */
    public function setIconName($icon_name)
    {
        $this->icon_name = $icon_name;
    }

    public function setDataAttribute(string $name, string $value): void
    {
        $this->data_attributes[$name] = $value;
    }

    /**
     * @return array<string, string>
     */
    public function getDataAttributes(): array
    {
        return $this->data_attributes;
    }
}
