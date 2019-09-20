<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

use Tuleap\REST\JsonCast;

class AgileDashboard_ColumnRepresentation
{

    /** @var int */
    public $id;

    /** @var String */
    public $label;

    /** @var String */
    public $color;

    public function build(Cardwall_Column $column)
    {
        $this->id    = JsonCast::toInt($column->getId());
        $this->label = $column->getLabel();
        $this->color = ($column->isHeaderATLPColor())
            ? $column->getHeadercolor()
            : ColorHelper::CssRGBToHexa($column->getHeadercolor());
    }
}
