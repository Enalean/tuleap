<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\DescriptionFields;

/**
 * @psalm-immutable
 */
final class ProjectRegistrationSubmittedField
{
    private int $field_id;
    private string $field_value;

    public function __construct(int $field_id, string $field_value)
    {
        $this->field_id    = $field_id;
        $this->field_value = $field_value;
    }

    public function getFieldId(): int
    {
        return $this->field_id;
    }

    public function getFieldValue(): string
    {
        return $this->field_value;
    }
}
