<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\SearchPullRequest;

final class SearchPullRequestStub implements SearchPullRequest
{
    private function __construct(private readonly array $pull_request_row)
    {
    }

    public function searchByPullRequestId(int $pull_request_id): array
    {
        return $this->pull_request_row;
    }

    public static function withNoRow(): self
    {
        return new self([]);
    }

    public static function withDefaultRow(): self
    {
        return new self(
            [
                'id'                 => 1,
                'title'              => 'title',
                'description'        => '',
                'repository_id'      => 15,
                'user_id'            => 102,
                'creation_date'      => 1697465547,
                'branch_src'         => '',
                'sha1_src'           => '',
                'repo_dest_id'       => '',
                'branch_dest'        => '',
                'sha1_dest'          => '',
                "description_format" => 'commonmark',
                'status'             => '',
                'merge_status'       => 'M',
            ]
        );
    }
}
