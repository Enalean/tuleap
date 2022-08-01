<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Rule;

/**
 * @psalm-immutable
 */
final class InvolvedFieldsInRule
{
    public function __construct(private int $source_field_id, private int $target_field_id)
    {
    }

    public function getSourceFieldId(): int
    {
        return $this->source_field_id;
    }

    public function getTargetFieldId(): int
    {
        return $this->target_field_id;
    }
}
