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

namespace Tuleap\Tracker\Artifact\ActionButtons;

class ActionButtonPresenter
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
     * @var string
     */
    public $icon;
    /**
     * @var array
     */
    public $data_property;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $css_class;
    /**
     * @var string
     */
    public $has_svg;

    public function __construct($label, $title, $url, $icon, array $data_property, $class, $has_svg)
    {
        $this->label         = $label;
        $this->title         = $title;
        $this->url           = $url;
        $this->icon          = $icon;
        $this->data_property = $data_property;
        $this->css_class     = $class;
        $this->has_svg       = $has_svg;
    }
}
