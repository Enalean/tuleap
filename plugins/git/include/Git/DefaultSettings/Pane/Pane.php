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

namespace Tuleap\Git\DefaultSettings\Pane;

abstract class Pane
{
    public $is_active;
    public $is_disabled;
    public $href;
    public $title;

    /**
     * @param string $title
     * @param string $href
     * @param bool   $is_active
     * @param bool   $is_disabled
     */
    public function __construct($title, $href, $is_active, $is_disabled)
    {
        $this->is_active   = $is_active;
        $this->is_disabled = $is_disabled;
        $this->href        = $href;
        $this->title       = $title;
    }

    /**
     * @return string
     */
    abstract public function content();
}
