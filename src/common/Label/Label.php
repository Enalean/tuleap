<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label;

class Label
{
    private $id;
    private $name;
    private $is_outline;
    private $color;

    /**
     * @param int $id
     * @param string $name
     * @param bool $is_outline
     * @param string $color
     */
    public function __construct($id, $name, $is_outline, $color)
    {
        $this->id         = $id;
        $this->name       = $name;
        $this->is_outline = $is_outline;
        $this->color      = $color;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isOutline()
    {
        return $this->is_outline;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }
}
