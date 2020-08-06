<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

/**
 * @psalm-immutable
 */
class PullRequestFileRepresentation
{
    /**
     * @var string {@type string}
     */
    public $path;

    /**
     * @var string {@type string}
     */
    public $status;

    /**
     * @var string
     */
    public $lines_added;

    /**
     * @var string
     */
    public $lines_removed;

    public function __construct(string $path, string $status, string $lines_added, string $lines_removed)
    {
        $this->path          = $path;
        $this->status        = $status;
        $this->lines_added   = $lines_added;
        $this->lines_removed = $lines_removed;
    }
}
