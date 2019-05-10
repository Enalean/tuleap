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

namespace Tuleap\FRS;

class FRSReleasePaginatedCollection
{
    /**
     * @var \FRSRelease[]
     */
    private $releases;

    /**
     * @var int
     */
    private $total_size;

    public function __construct(array $release, $total_size)
    {
        $this->releases   = $release;
        $this->total_size = $total_size;
    }

    /**
     * @return \FRSRelease[]
     */
    public function getReleases()
    {
        return $this->releases;
    }

    /**
     * @return int
     */
    public function getTotalSize()
    {
        return $this->total_size;
    }
}
