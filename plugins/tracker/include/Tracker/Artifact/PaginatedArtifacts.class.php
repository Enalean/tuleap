<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class Tracker_Artifact_PaginatedArtifacts
{

    /**
     * @var Tracker_Artifact[]
     */
    private $artifacts;

    /**
     * @var int
     */
    private $total_size;

    /**
     *
     * @param Tracker_Artifact[] $artifacts
     * @param int $total_size
     */
    public function __construct(array $artifacts, $total_size)
    {
        $this->artifacts  = $artifacts;
        $this->total_size = $total_size;
    }

    public function getTotalSize()
    {
        return $this->total_size;
    }

    /**
     * @return Tracker_Artifact[]
     */
    public function getArtifacts()
    {
        return $this->artifacts;
    }
}
