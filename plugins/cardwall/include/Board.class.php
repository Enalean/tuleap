<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Cardwall\OnTop\Config\ColumnCollection;

/**
 * A board that contains swimlines (with cards) and columns
 */
class Cardwall_Board
{
    /**
     * @var array of TreeNode
     */
    public $swimlines;

    /**
     * @var ColumnCollection
     */
    public $columns;

    /**
     * @var Cardwall_MappingCollection
     */
    public $mappings;

    /**
     * @param array of Cardwall_Swimline             $swimlines
     */
    public function __construct(array $swimlines, ColumnCollection $columns, Cardwall_MappingCollection $mappings)
    {
        $this->swimlines = $swimlines;
        $this->columns   = $columns;
        $this->mappings  = $mappings;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getSwimlines()
    {
        return $this->swimlines;
    }
}
