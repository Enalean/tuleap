<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reviewer\Autocompleter;

/**
 * @psalm-immutable
 */
final class UsernameToSearch
{
    private const MINIMAL_LENGTH = 3;

    /**
     * @var string
     */
    private $username_to_search;

    private function __construct(string $username_to_search)
    {
        $this->username_to_search = $username_to_search;
    }

    /**
     * @throws UsernameToSearchTooSmallException
     */
    public static function fromString(string $username_to_search): self
    {
        $username_to_search_length = strlen($username_to_search);
        if ($username_to_search_length < self::MINIMAL_LENGTH) {
            throw new UsernameToSearchTooSmallException(self::MINIMAL_LENGTH, $username_to_search_length);
        }

        return new self($username_to_search);
    }

    public function toString(): string
    {
        return $this->username_to_search;
    }
}
