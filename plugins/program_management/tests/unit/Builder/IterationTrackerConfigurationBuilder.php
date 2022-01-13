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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerConfiguration;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class IterationTrackerConfigurationBuilder
{
    public static function buildWithIdAndLabels(
        int $tracker_id,
        ?string $label,
        ?string $sublabel,
    ): IterationTrackerConfiguration {
        $configuration = IterationTrackerConfiguration::fromProgram(
            RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerReferenceStub::withId($tracker_id)),
            RetrieveIterationLabelsStub::buildLabels($label, $sublabel),
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser()
        );
        assert($configuration !== null);
        return $configuration;
    }
}
