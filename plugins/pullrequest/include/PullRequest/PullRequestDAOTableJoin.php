<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
final readonly class PullRequestDAOTableJoin
{
    /**
     * @psalm-param "LEFT"|"INNER" $join_type
     * @psalm-param literal-string $table_name
     * @psalm-param literal-string $join_condition
     */
    private function __construct(
        public string $join_type,
        public string $table_name,
        public string $join_condition,
    ) {
    }

    /**
     * @psalm-param literal-string $table_name
     * @psalm-param literal-string $join_condition
     */
    public static function innerJoin(string $table_name, string $join_condition): self
    {
        return new self('INNER', $table_name, $join_condition);
    }

    /**
     * @psalm-param literal-string $table_name
     * @psalm-param literal-string $join_condition
     */
    public static function leftJoin(string $table_name, string $join_condition): self
    {
        return new self('LEFT', $table_name, $join_condition);
    }
}
