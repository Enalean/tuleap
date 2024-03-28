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

namespace Tuleap\JiraImport\JiraAgile;

/**
 * @psalm-immutable
 */
final class JiraEpic
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $key;
    /**
     * @var string
     */
    public $url;

    public function __construct(int $id, string $key, string $url)
    {
        $this->id  = $id;
        $this->key = $key;
        $this->url = $url;
    }

    /**
     * @param array{id: int, key: string, self: string} $json
     */
    public static function buildFromAPI(array $json): self
    {
        return new self($json['id'], $json['key'], $json['self']);
    }

    /**
     * @param array{id: string, key: string} $json
     */
    public static function buildFromIssueAPI(array $json): self
    {
        return new self(
            (int) $json['id'],
            $json['key'],
            '/rest/agile/1.0/epic/' . urlencode($json['id'])
        );
    }
}
