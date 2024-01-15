<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest;

/**
 * @psalm-immutable
 */
final class PullRequestsPage
{
    /**
     * @psalm-param array<array{
     *      id:int,
     *      title:string,
     *      description:string,
     *      repository_id:int,
     *      user_id:int,
     *      creation_date: int,
     *      branch_src: string,
     *      sha1_src: string,
     *      repo_dest_id: int,
     *      branch_dest: string,
     *      sha1_dest: int,
     *      status: string,
     *      merge_status: int,
     *      description_format: string
     *  }> $pull_requests
     */
    public function __construct(public readonly int $total_size, public readonly array $pull_requests)
    {
    }
}
