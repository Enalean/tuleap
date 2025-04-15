<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard;

class Dashboard
{
    /**
     * This is also defined in dashboard-layout.js
     *
     * @var array
     */
    public static $LAYOUTS = [
        0 => [ '' ],
        1 => ['one-column'],
        2 => ['two-columns', 'two-columns-small-big', 'two-columns-big-small'],
        3 => [
            'three-columns',
            'three-columns-small-big-small',
            'three-columns-big-small-small',
            'three-columns-small-small-big',
        ],
        4 => [ 'too-many-columns' ],
    ];

    private $id;
    private $name;

    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isLayoutValid($layout, $columns_count)
    {
        $index = max($columns_count, 0);
        $index = min($index, 4);
        return in_array($layout, self::$LAYOUTS[$index]);
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
