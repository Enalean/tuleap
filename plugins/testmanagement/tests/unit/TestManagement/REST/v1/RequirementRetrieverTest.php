<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\IRetrieveTestExecutionTrackerIdFromConfig;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveViewableArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class RequirementRetrieverTest extends TestCase
{
    public function testGetAllRequirementsForDefinitionReturnsOnlyReadableRequirementsLinkedToTheDefinition()
    {
        $definition_id        = 66;
        $execution_tracker_id = 42;

        $dao = $this->createMock(ArtifactDao::class);
        $dao->method('searchAllRequirementsForDefinition')
            ->with(66, 42)
            ->willReturn([101, 102, 103]);

        $retriever = new RequirementRetriever(
            new class implements RetrieveViewableArtifact {
                /**
                 * @param int $id
                 */
                public function getArtifactByIdUserCanView(\PFUser $user, $id): ?Artifact
                {
                    if ($id === 101 || $id === 102) {
                        return ArtifactTestBuilder::anArtifact($id)->build();
                    }

                    return null;
                }
            },
            $dao,
            new class ($execution_tracker_id) implements IRetrieveTestExecutionTrackerIdFromConfig {
                public function __construct(private int $execution_tracker_id)
                {
                }

                /**
                 * @return int|false
                 */
                public function getTestExecutionTrackerId(\Project $project)
                {
                    return $this->execution_tracker_id;
                }
            }
        );

        $user = UserTestBuilder::anAnonymousUser()->build();

        $requirements = $retriever->getAllRequirementsForDefinition(
            ArtifactTestBuilder::anArtifact($definition_id)
                ->inProject(ProjectTestBuilder::aProject()->build())
                ->build(),
            $user
        );
        self::assertCount(2, $requirements);
        self::assertEquals(101, $requirements[0]->getId());
        self::assertEquals(102, $requirements[1]->getId());
    }
}
