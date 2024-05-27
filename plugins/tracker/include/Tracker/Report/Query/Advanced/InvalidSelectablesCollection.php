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

namespace Tuleap\Tracker\Report\Query\Advanced;

final class InvalidSelectablesCollection
{
    /**
     * @var string[]
     */
    private array $non_existent_selectables = [];
    /**
     * @var string[]
     */
    private array $invalid_selectables_errors = [];

    public function addNonExistentSelectable(string $selectable_name): void
    {
        $this->non_existent_selectables[] = $selectable_name;
    }

    public function addInvalidSelectableError(string $error_message): void
    {
        $this->invalid_selectables_errors[] = $error_message;
    }

    public function getNonExistentSelectables(): array
    {
        return $this->non_existent_selectables;
    }

    public function getInvalidSelectablesErrors(): array
    {
        return $this->invalid_selectables_errors;
    }
}
