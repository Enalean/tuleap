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

namespace Tuleap\SVN\REST\v1;

class RepositoryRepresentationPaginatedCollection
{
    /**
     * @var RepositoryRepresentation[]
     */
    private $repositories_representations;

    /**
     * @var int
     */
    private $total_size;

    public function __construct(array $repositories_representations, $total_size)
    {
        $this->repositories_representations = $repositories_representations;
        $this->total_size                   = $total_size;
    }

    /**
     * @return RepositoryRepresentation[]
     */
    public function getRepositoriesRepresentations()
    {
        return $this->repositories_representations;
    }

    /**
     * @return int
     */
    public function getTotalSize()
    {
        return $this->total_size;
    }
}
