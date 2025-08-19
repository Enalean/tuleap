<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Test\Builders;

use Tuleap\TestManagement\Step\Execution\Field\StepsExecution;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

final class StepsExecutionFieldBuilder
{
    private Tracker $tracker;

    private function __construct(readonly private int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(65)->build();
    }

    public static function aStepsExecutionField(int $id): self
    {
        return new self($id);
    }

    public function build(): StepsExecution
    {
        $field = new StepsExecution(
            $this->id,
            $this->tracker->getId(),
            0,
            'steps_exec',
            'Steps execution',
            "Execution of the test's steps",
            true,
            'P',
            null,
            null,
            10,
            null,
        );
        $field->setTracker($this->tracker);

        return $field;
    }
}
