<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Webhook;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\ProjectUserUGroupMembershipsRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\HTMLOrTextCommentRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\BuildCompleteTrackerRESTRepresentationStub;
use Tuleap\User\TuleapFunctionsUser;
use Tuleap\User\REST\MinimalUserRepresentation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactPayloadBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ArtifactPayloadBuilder $builder;
    private ChangesetRepresentationBuilder&MockObject $changeset_representation_builder;

    protected function setUp(): void
    {
        $user_helper = $this->createMock(\UserHelper::class);
        \UserHelper::setInstance($user_helper);
        $user_helper->method('getUserUrl');
        $user_helper->method('getDisplayNameFromUser');
        $this->changeset_representation_builder = $this->createMock(ChangesetRepresentationBuilder::class);
        $this->builder                          = new ArtifactPayloadBuilder(
            $this->changeset_representation_builder,
            BuildCompleteTrackerRESTRepresentationStub::build(),
            ProvideUserAvatarUrlStub::build(),
            new class implements ProjectUserUGroupMembershipsRetriever
            {
                public function getMembershipsInAProject(\Project $project, \PFUser $user): array
                {
                    return [new \ProjectUGroup(['group_id' => $project->getID(), 'ugroup_id' => 104])];
                }
            }
        );
    }

    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testCreationIsIdentified(): void
    {
        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withRealName('Real Name')
            ->withUserName('username')
            ->withAvatarUrl('')
            ->build();

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact  = ArtifactTestBuilder::anArtifact(103)
            ->withChangesets($changeset)
            ->build();
        $changeset->method('getId')->willReturn(1);
        $changeset->method('getSubmitter')->willReturn($user);
        $changeset->method('getArtifact')->willReturn($artifact);
        $changeset->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build());
        $this->changeset_representation_builder->expects($this->once())->method('buildWithFieldValuesWithoutPermissions')
            ->willReturn($this->buildChangesetRepresentation($user));

        $payload = $this->builder->buildPayload($changeset);

        self::assertSame('create', $payload->getPayload()['action']);
        self::assertNull($payload->getPayload()['previous']);
        self::assertFalse($payload->getPayload()['is_custom_code_execution']);
        self::assertNotEmpty($payload->getPayload()['submitter_user_groups']);
    }

    public function testItSetWASMUpdateToTrueIfCCEUser(): void
    {
        $user = UserTestBuilder::aUser()
            ->withId(TuleapFunctionsUser::ID)
            ->withRealName('Real Name')
            ->withUserName('username')
            ->withAvatarUrl('')
            ->build();

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact  = ArtifactTestBuilder::anArtifact(103)
            ->withChangesets($changeset)
            ->build();
        $changeset->method('getId')->willReturn(1);
        $changeset->method('getSubmitter')->willReturn($user);
        $changeset->method('getArtifact')->willReturn($artifact);
        $changeset->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build());
        $this->changeset_representation_builder->expects($this->once())->method('buildWithFieldValuesWithoutPermissions')
            ->willReturn($this->buildChangesetRepresentation($user));

        $payload = $this->builder->buildPayload($changeset);

        self::assertTrue($payload->getPayload()['is_custom_code_execution']);
    }

    private function buildChangesetRepresentation(\PFUser $user): ChangesetRepresentation
    {
        $comment_representation = new HTMLOrTextCommentRepresentation('last comment', 'last comment', 'text', null);
        return new ChangesetRepresentation(
            98,
            101,
            MinimalUserRepresentation::build($user, ProvideUserAvatarUrlStub::build()),
            1234567890,
            null,
            $comment_representation,
            [],
            MinimalUserRepresentation::build($user, ProvideUserAvatarUrlStub::build()),
            1234567890
        );
    }
}
