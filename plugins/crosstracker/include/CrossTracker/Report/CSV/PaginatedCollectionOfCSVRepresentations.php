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

namespace Tuleap\CrossTracker\Report\CSV;

class PaginatedCollectionOfCSVRepresentations
{
    /**
     * @var CSVRepresentation[]
     */
    private $representations;

    /**
     * @var int
     */
    private $total_size;

    /**
     * @param CSVRepresentation[] $representations
     * @param int                 $total_size
     */
    public function __construct(array $representations, $total_size)
    {
        $this->representations = $representations;
        $this->total_size      = $total_size;
    }

    /**
     * @return CSVRepresentation[]
     */
    public function getRepresentations()
    {
        return $this->representations;
    }

    /**
     * @return int
     */
    public function getTotalSize()
    {
        return $this->total_size;
    }

    public function __toString()
    {
        return array_reduce(
            $this->representations,
            function ($accumulator, CSVRepresentation $representation) {
                return $accumulator . $representation . "\r\n";
            },
            ''
        );
    }
}
