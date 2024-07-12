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

use DateTimeImmutable;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\SaveQuery;

final class SaveQueryStub implements SaveQuery
{
    private bool $has_been_called = false;

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function getHasBeenCalled(): bool
    {
        return $this->has_been_called;
    }

    public function saveQueryWithDates(int $widget_id, DateTimeImmutable $start_date, DateTimeImmutable $end_date): void
    {
        $this->has_been_called = true;
    }
}
