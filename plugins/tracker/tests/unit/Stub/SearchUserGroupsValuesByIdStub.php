<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\SearchUserGroupsValuesById;

final class SearchUserGroupsValuesByIdStub implements SearchUserGroupsValuesById
{
    /**
     * @psalm-param array{id: int, field_id: int, ugroup_id: int, is_hidden: int}[] $values
     */
    private function __construct(private readonly array $values)
    {
    }

    /**
     * @psalm-param array{id: int, field_id: int, ugroup_id: int, is_hidden: int}[] $values
     */
    public static function withValues(array $values): self
    {
        return new self($values);
    }

    public static function withoutValues(): self
    {
        return new self([]);
    }

    public function searchById(int $id): ?array
    {
        foreach ($this->values as $value) {
            if ($value['id'] === $id) {
                return $value;
            }
        }

        return null;
    }
}
