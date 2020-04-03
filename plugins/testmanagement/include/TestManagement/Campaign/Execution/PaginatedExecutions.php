<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
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

namespace Tuleap\TestManagement\Campaign\Execution;

use Tracker_Artifact;

class PaginatedExecutions
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
     * @var int[]
     */
    private $definitions_changeset_ids;

    /**
     *
     * @param Tracker_Artifact[] $artifacts
     * @param int                $total_size
     * @param int[]              $definitions_changeset_ids
     */
    public function __construct(array $artifacts, $total_size, array $definitions_changeset_ids)
    {
        $this->artifacts                 = $artifacts;
        $this->total_size                = $total_size;
        $this->definitions_changeset_ids = $definitions_changeset_ids;
    }

    /**
     * @return int
     */
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

    /**
     * @return int[]
     */
    public function getDefinitionsChangesetIds()
    {
        return $this->definitions_changeset_ids;
    }
}
