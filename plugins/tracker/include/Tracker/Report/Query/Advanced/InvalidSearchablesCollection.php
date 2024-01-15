<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced;

final class InvalidSearchablesCollection
{
    /** @var string[] */
    private array $searchables_not_exist;

    /** @var string[] */
    private array $invalid_searchable_errors;

    public function __construct()
    {
        $this->searchables_not_exist     = [];
        $this->invalid_searchable_errors = [];
    }

    public function addNonexistentSearchable(string $searchable_name): void
    {
        $this->searchables_not_exist[] = $searchable_name;
    }

    public function hasInvalidSearchable(): bool
    {
        return max(
            count($this->searchables_not_exist),
            count($this->invalid_searchable_errors)
        ) > 0;
    }

    /**
     * @return string[]
     */
    public function getNonexistentSearchables(): array
    {
        return $this->searchables_not_exist;
    }

    /**
     * @return string[]
     */
    public function getInvalidSearchableErrors(): array
    {
        return $this->invalid_searchable_errors;
    }

    public function addInvalidSearchableError(string $error_message): void
    {
        $this->invalid_searchable_errors[] = $error_message;
    }
}
