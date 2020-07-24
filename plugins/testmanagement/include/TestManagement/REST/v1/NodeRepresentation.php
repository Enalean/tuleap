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

namespace Tuleap\TestManagement\REST\v1;

/**
 * @psalm-immutable
 */
class NodeRepresentation extends NodeReferenceRepresentation
{
    /**
     * @var array
     */
    public $links = [];

    /**
     * @var array
     */
    public $reverse_links = [];

    public function __construct(array $links, array $reverse_links)
    {
        $this->links         = $links;
        $this->reverse_links = $reverse_links;
    }
}
