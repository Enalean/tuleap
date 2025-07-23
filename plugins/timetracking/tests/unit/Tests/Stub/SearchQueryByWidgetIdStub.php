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

namespace Tuleap\Timetracking\Tests\Stub;

use Tuleap\Timetracking\REST\v1\TimetrackingManagement\SearchQueryByWidgetId;

final class SearchQueryByWidgetIdStub implements SearchQueryByWidgetId
{
    private function __construct(
        private int $id,
        private ?int $start_date,
        private ?int $end_date,
        private ?string $predefined_time_period,
    ) {
    }

    public static function build(
        int $id,
        ?int $start_date,
        ?int $end_date,
        ?string $predefined_time_period,
    ): self {
        return new self($id, $start_date, $end_date, $predefined_time_period);
    }

    #[\Override]
    public function searchQueryById(int $id): ?array
    {
        return [
            'id' => $this->id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'predefined_time_period' => $this->predefined_time_period,
        ];
    }
}
