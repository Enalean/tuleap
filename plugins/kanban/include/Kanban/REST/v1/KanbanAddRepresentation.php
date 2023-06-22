<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Luracast\Restler\RestException;

/**
 * @psalm-immutable
 */
final class KanbanAddRepresentation
{
    /**
     * @var array {@type int}
     * @psalm-var int[]
     */
    public $ids;

    /**
     * @throws RestException
     */
    public function checkFormat(): void
    {
        $this->isArrayOfInt('ids');
        if (count($this->ids) == 0) {
            throw new RestException(400, "invalid value specified for `ids`. Expected: array of integers");
        }
    }

    private function isArrayOfInt(string $name): void
    {
        if (! is_array($this->$name)) {
            throw new RestException(400, "invalid value specified for `$name`. Expected: array of integers");
        }
        foreach ($this->$name as $id) {
            if (! is_int($id)) {
                throw new RestException(400, "invalid value specified for `$name`. Expected: array of integers");
            }
        }
    }
}
