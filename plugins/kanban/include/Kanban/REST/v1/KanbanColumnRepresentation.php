<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST\v1;

use AgileDashboard_KanbanColumn;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class KanbanColumnRepresentation
{
    public const ROUTE = "kanban_columns";

    /**
     * @var int {@type int}
     */
    public $id;

    /**
     * @var string {@type string}
     */
    public $label;

    /**
     * @var bool {@type bool}
     */
    public $is_open;

    /**
     * @var int {@type int}
     */
    public $limit;

    /**
     * @var string {@type string}
     */
    public $color;

    public function __construct(
        AgileDashboard_KanbanColumn $column,
        public readonly bool $user_can_add_in_place,
        public readonly bool $user_can_remove_column,
        public readonly bool $user_can_edit_label,
    ) {
        $this->id      = JsonCast::toInt($column->getId());
        $this->label   = $column->getLabel();
        $this->is_open = $column->isOpen();
        $this->color   = $column->getColor();
        $this->limit   = JsonCast::toInt($column->getLimit());
    }
}
