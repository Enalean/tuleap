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

declare(strict_types=1);

namespace Tuleap\Tracker\Rule;

use Tracker_Rule_List;

final class FieldListRuleCollection
{
    private array $rules;

    public function __construct(private ?int $actual_value)
    {
        $this->rules = [];
    }

    public function getActualValue(): ?int
    {
        return $this->actual_value;
    }

    /**
     * @return Tracker_Rule_List[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function addRule(Tracker_Rule_List $rule): void
    {
        $this->rules[] = $rule;
    }
}
