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

namespace Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem;

use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Presenter as ContentPresenter;

class Presenter
{
    /** @var string */
    public $label;

    /** @var icon */
    public $icon;

    /** @var ContentPresenter */
    public $content;

    /** @var string */
    public $additional_classes;

    public function __construct($label, $icon, $content, $additional_classes)
    {
        $this->label              = $label;
        $this->icon               = $icon;
        $this->content            = $content;
        $this->additional_classes = $additional_classes;
    }
}
