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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;

final class IterationLabelsBuilder
{
    public static function buildWithLabels(string $label, string $sub_label): IterationLabels
    {
        return IterationLabels::fromIterationTracker(
            RetrieveIterationLabelsStub::buildLabels($label, $sub_label),
            IterationTrackerIdentifierBuilder::buildWithId(101)
        );
    }

    public static function buildWithNoLabels(): IterationLabels
    {
        return IterationLabels::fromIterationTracker(
            RetrieveIterationLabelsStub::buildLabels(null, null),
            IterationTrackerIdentifierBuilder::buildWithId(101)
        );
    }
}
