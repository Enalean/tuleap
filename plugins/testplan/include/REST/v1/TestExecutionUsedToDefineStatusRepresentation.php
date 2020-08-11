<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestPlan\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\TestPlan\TestDefinition\TestPlanTestDefinitionWithTestStatus;
use Tuleap\Tracker\REST\Artifact\ArtifactReferenceRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
final class TestExecutionUsedToDefineStatusRepresentation extends ArtifactReferenceRepresentation
{
    /**
     * @var string {@type date}
     */
    public $submitted_on;
    /**
     * @var MinimalUserRepresentation
     */
    public $submitted_by;

    private function __construct(int $id, int $submitted_on, MinimalUserRepresentation $submitted_by)
    {
        parent::__construct($id);
        $this->submitted_on = JsonCast::toDate($submitted_on);
        $this->submitted_by = $submitted_by;
    }

    public static function fromTestPlanTestDefinitionWithStatus(TestPlanTestDefinitionWithTestStatus $test_definition_with_test_status): ?self
    {
        $artifact_id  = $test_definition_with_test_status->getTestExecutionIdUsedToDefineStatus();
        $submitted_on = $test_definition_with_test_status->getTestExecutionDate();
        $submitted_by = $test_definition_with_test_status->getTestExecutionSubmittedBy();

        if ($artifact_id === null || $submitted_on === null || $submitted_by === null) {
            return null;
        }

        return new self($artifact_id, $submitted_on, MinimalUserRepresentation::build($submitted_by));
    }
}
