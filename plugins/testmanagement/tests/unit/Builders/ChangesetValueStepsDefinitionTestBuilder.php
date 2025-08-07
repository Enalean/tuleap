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
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinition;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Step;

final class ChangesetValueStepsDefinitionTestBuilder
{
    /**
     * @var list<Step>
     */
    private array $steps = [];

    private function __construct(
        private readonly int $id,
        private readonly Tracker_Artifact_Changeset $changeset,
        private readonly StepsDefinition $field,
    ) {
    }

    public static function aValue(int $id, Tracker_Artifact_Changeset $changeset, StepsDefinition $field): self
    {
        return new self($id, $changeset, $field);
    }

    /**
     * @param list<Step> $steps
     */
    public function withSteps(array $steps): self
    {
        $this->steps = $steps;
        return $this;
    }

    public function build(): StepsDefinitionChangesetValue
    {
        return new StepsDefinitionChangesetValue(
            $this->id,
            $this->changeset,
            $this->field,
            true,
            $this->steps,
        );
    }
}
