<?php
/**
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Tuleap\Tracker\FormElement\ChartFieldUsage;

final class ChartFieldUsageTestBuilder
{
    private bool $uses_start_date       = false;
    private bool $uses_duration         = false;
    private bool $uses_capacity         = false;
    private bool $uses_hierarchy        = false;
    private bool $uses_remaining_effort = false;

    public static function aChart(): self
    {
        return new self();
    }

    public function usingStartDate(): self
    {
        $this->uses_start_date = true;
        return $this;
    }

    public function usingDuration(): self
    {
        $this->uses_duration = true;
        return $this;
    }

    public function usingRemainingEffort(): self
    {
        $this->uses_remaining_effort = true;
        return $this;
    }

    public function build(): ChartFieldUsage
    {
        return new class (
            $this->uses_start_date,
            $this->uses_duration,
            $this->uses_capacity,
            $this->uses_hierarchy,
            $this->uses_remaining_effort,
        ) implements ChartFieldUsage {
            public function __construct(
                public bool $uses_start_date,
                public bool $uses_duration,
                public bool $uses_capacity,
                public bool $uses_hierarchy,
                public bool $uses_remaining_effort,
            ) {
            }
        };
    }
}
