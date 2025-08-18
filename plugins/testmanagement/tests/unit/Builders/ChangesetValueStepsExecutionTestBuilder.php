<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use Tracker_Artifact_Changeset;
use Tuleap\TestManagement\Step\Execution\Field\StepsExecution;
use Tuleap\TestManagement\Step\Execution\Field\StepsExecutionChangesetValue;
use Tuleap\TestManagement\Step\Execution\StepResult;

final class ChangesetValueStepsExecutionTestBuilder
{
    /**
     * @var list<StepResult>
     */
    private array $steps_results = [];

    private function __construct(
        private readonly int $id,
        private readonly Tracker_Artifact_Changeset $changeset,
        private readonly StepsExecution $field,
    ) {
    }

    public static function aValue(int $id, Tracker_Artifact_Changeset $changeset, StepsExecution $field): self
    {
        return new self($id, $changeset, $field);
    }

    /**
     * @param list<StepResult> $steps_results
     */
    public function withStepsResults(array $steps_results): self
    {
        $this->steps_results = $steps_results;
        return $this;
    }

    public function build(): StepsExecutionChangesetValue
    {
        return new StepsExecutionChangesetValue(
            $this->id,
            $this->changeset,
            $this->field,
            true,
            $this->steps_results,
        );
    }
}
