<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\DocumentGeneration\Report;

/**
 * @psalm-immutable
 */
class DateCriterionValueJson implements CriterionValueJson
{
    public string $criterion_name;
    public string $criterion_type;
    public ?string $criterion_from_value;
    public ?string $criterion_to_value;
    public string $criterion_value_operator;
    public bool $is_advanced;

    public function __construct(
        string $criterion_name,
        ?string $criterion_from_value,
        ?string $criterion_to_value,
        string $criterion_value_operator,
        bool $is_advanced,
    ) {
        $this->criterion_name           = $criterion_name;
        $this->criterion_type           = "date";
        $this->criterion_from_value     = $criterion_from_value;
        $this->criterion_to_value       = $criterion_to_value;
        $this->criterion_value_operator = $criterion_value_operator;
        $this->is_advanced              = $is_advanced;
    }
}
