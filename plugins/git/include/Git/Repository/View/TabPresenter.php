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

namespace Tuleap\Git\Repository\View;

class TabPresenter
{
    /**
     * @var bool
     */
    public $is_active;
    /**
     * @var string
     */
    public $url;
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $html_id;
    /**
     * @var bool
     */
    public $has_count_to_display;
    /**
     * @var int
     */
    public $count;

    public function __construct($is_active, $url, $label, $html_id, $has_count_to_display, $count)
    {
        $this->is_active            = $is_active;
        $this->url                  = $url;
        $this->label                = $label;
        $this->html_id              = $html_id;
        $this->has_count_to_display = $has_count_to_display;
        $this->count                = $count;
    }
}
