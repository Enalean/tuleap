<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

/**
 * @psalm-immutable
 */
final class KanbanColumnPATCHRepresentation
{
    public const string ROUTE = 'kanban_columns';

    /**
     * @var string {@type string} {@required false} {@min 1}
     */
    public $label;

    /**
     * @var int {@type int} {@required false}
     */
    public $wip_limit;
}
