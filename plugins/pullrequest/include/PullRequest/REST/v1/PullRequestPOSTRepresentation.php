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
class PullRequestPOSTRepresentation
{
    /**
     * @var int {@type int}
     */
    public $repository_id;

    /**
     * @var int {@type int}
     */
    public $repository_dest_id;

    /**
     * @var string {@type string}
     */
    public $branch_src;

    /**
     * @var string {@type string}
     */
    public $branch_dest;
    /**
     * @psalm-var string | null
     * {@type string} {@required false}
     */
    public string|null $description_format;
}
