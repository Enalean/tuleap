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

use PFUser;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentations\MinimalDefinitionRepresentation;
use Tuleap\TestPlan\TestDefinition\TestPlanTestDefinitionWithTestStatus;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactReferenceRepresentation;

/**
 * @psalm-immutable
 *
 * @psalm-type TestStatus = null|"notrun"|"passed"|"failed"|"blocked"
 */
final class DefinitionLinkedToABacklogItemRepresentation extends MinimalDefinitionRepresentation
{
    /**
     * @var string
     */
    public $short_type = '';
    /**
     * @var string | null {@choice notrun,passed,failed,blocked}
     * @psalm-var TestStatus
     */
    public $test_status;
    /**
     * @var TestExecutionUsedToDefineStatusRepresentation | null
     */
    public $test_execution_used_to_define_status;
    /**
     * @var ArtifactReferenceRepresentation | null
     */
    public $test_campaign_defining_status;

    /**
     * @psalm-param TestStatus $test_status
     */
    private function __construct(
        Artifact $artifact,
        Tracker_FormElementFactory $form_element_factory,
        PFUser $user,
        string $short_type,
        ?string $test_status,
        ?TestExecutionUsedToDefineStatusRepresentation $test_exec,
        ?ArtifactReferenceRepresentation $test_campaign,
    ) {
        parent::__construct($artifact, $form_element_factory, $user, null);
        $this->short_type                           = $short_type;
        $this->test_status                          = $test_status;
        $this->test_execution_used_to_define_status = $test_exec;
        $this->test_campaign_defining_status        = $test_campaign;
    }

    public static function fromTestDefinitionWithTestStatus(
        TestPlanTestDefinitionWithTestStatus $test_definition_with_test_status,
        PFUser $user,
        Tracker_FormElementFactory $form_element_factory,
    ): self {
        $test_definition = $test_definition_with_test_status->getTestDefinition();

        return new self(
            $test_definition,
            $form_element_factory,
            $user,
            $test_definition->getTracker()->getItemName(),
            $test_definition_with_test_status->getStatus(),
            TestExecutionUsedToDefineStatusRepresentation::fromTestPlanTestDefinitionWithStatus($test_definition_with_test_status),
            self::buildArtifactReference($test_definition_with_test_status->getTestCampaignIdDefiningTheStatus())
        );
    }

    private static function buildArtifactReference(?int $id): ?ArtifactReferenceRepresentation
    {
        if ($id === null) {
            return null;
        }

        return new ArtifactReferenceRepresentation($id);
    }
}
