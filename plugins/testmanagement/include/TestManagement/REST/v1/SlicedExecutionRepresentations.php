<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

class SlicedExecutionRepresentations
{

    /**
     * @var ExecutionRepresentation[]
     */
    private $representations;

    /**
     * @var int
     */
    private $total_size;

    /**
     *
     * @param ExecutionRepresentation[] $representations
     * @param int $total_size
     */
    public function __construct(array $representations, $total_size)
    {
        $this->representations = $representations;
        $this->total_size      = $total_size;
    }

    public function getTotalSize(): int
    {
        return $this->total_size;
    }

    /**
     * @return ExecutionRepresentation[]
     */
    public function getRepresentations()
    {
        return $this->representations;
    }
}
