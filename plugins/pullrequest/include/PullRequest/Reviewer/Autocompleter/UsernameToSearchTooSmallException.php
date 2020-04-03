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
final class UsernameToSearchTooSmallException extends \RuntimeException
{
    /**
     * @var int
     */
    private $minimal_accepted_length;

    public function __construct(int $minimal_accepted_length, int $actual_length)
    {
        parent::__construct(
            sprintf(
                'Username to search must at least be %d characters, got %d characters',
                $minimal_accepted_length,
                $actual_length
            )
        );
        $this->minimal_accepted_length = $minimal_accepted_length;
    }

    public function getMinimalAcceptedLength(): int
    {
        return $this->minimal_accepted_length;
    }
}
