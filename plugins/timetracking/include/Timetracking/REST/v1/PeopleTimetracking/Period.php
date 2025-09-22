<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\PeopleTimetracking;

use DateTimeImmutable;
use Tuleap\Timetracking\Widget\People\PredefinedTimePeriod;

final readonly class Period
{
    private function __construct(
        private ?DateTimeImmutable $start_date,
        private ?DateTimeImmutable $end_date,
        private ?PredefinedTimePeriod $period,
    ) {
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->start_date;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->end_date;
    }

    public function getPeriod(): ?PredefinedTimePeriod
    {
        return $this->period;
    }

    public static function fromPredefinedTimePeriod(PredefinedTimePeriod $predefined_time_period): self
    {
        return new self(
            null,
            null,
            $predefined_time_period
        );
    }

    public static function fromDates(DateTimeImmutable $start, DateTimeImmutable $end): self
    {
        return new self(
            $start,
            $end,
            null
        );
    }

    public function isPredefined(): bool
    {
        return $this->period !== null;
    }
}
