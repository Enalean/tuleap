<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tuleap_Tour_Step
{

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    public $element;

    /**
     * @var string
     */
    public $placement;

    /**
     * @var bool
     */
    public $backdrop;


    public function __construct($title, $content, $placement = 'bottom', $element = '', $backdrop = false)
    {
        $this->title     = $title;
        $this->content   = $content;
        $this->element   = $element;
        $this->placement = $placement;
        $this->backdrop  = $backdrop;
    }

    public function setElement($element)
    {
        $this->element = $element;
    }

    public function setPlacement($placement)
    {
        $this->placement = $placement;
    }

    public function setBackdrop($backdrop)
    {
        $this->backdrop = $backdrop;
    }
}
