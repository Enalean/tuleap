<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\JiraAgile\Board\Backlog;

/**
 * @psalm-immutable
 */
final class BacklogIssueRepresentation
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $key;

    public function __construct(int $id, string $key)
    {
        $this->id  = $id;
        $this->key = $key;
    }

    public static function buildFromAPIResponse(array $response): self
    {
        if (
            ! isset($response['id']) ||
            ! isset($response['key'])
        ) {
            throw new BoardBacklogAPIResponseNotWellFormedException();
        }

        $id  = (int) $response['id'];
        $key = $response['key'];

        return new self($id, $key);
    }
}
