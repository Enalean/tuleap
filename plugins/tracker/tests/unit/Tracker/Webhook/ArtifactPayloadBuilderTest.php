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

namespace Tuleap\Tracker\Webhook;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\HTMLOrTextCommentRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;

final class ArtifactPayloadBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ArtifactPayloadBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ChangesetRepresentationBuilder
     */
    private $changeset_representation_builder;

    protected function setUp(): void
    {
        \UserHelper::setInstance(\Mockery::spy(\UserHelper::class));
        $this->changeset_representation_builder = \Mockery::mock(ChangesetRepresentationBuilder::class);
        $this->builder                          = new ArtifactPayloadBuilder($this->changeset_representation_builder);
    }

    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testCreationIsIdentified(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(101);
        $user->shouldReceive('isAnonymous')->andReturns(false);
        $user->shouldReceive('getRealName')->andReturns('Real Name');
        $user->shouldReceive('getUserName')->andReturns('username');
        $user->shouldReceive('getLdapId')->andReturns(null);
        $user->shouldReceive('getAvatarUrl')->andReturns('');
        $user->shouldReceive('hasAvatar')->andReturns(false);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getPreviousChangeset')->andReturns(null);
        $artifact->shouldReceive('getId')->andReturns(103);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturns(1);
        $changeset->shouldReceive('getSubmitter')->andReturns($user);
        $changeset->shouldReceive('getArtifact')->andReturns($artifact);
        $this->changeset_representation_builder->shouldReceive('buildWithFieldValuesWithoutPermissions')
            ->once()
            ->andReturn($this->buildChangesetRepresentation($user));

        $payload = $this->builder->buildPayload($changeset);

        $this->assertSame('create', $payload->getPayload()['action']);
        $this->assertNull($payload->getPayload()['previous']);
    }

    private function buildChangesetRepresentation(\PFUser $user): ChangesetRepresentation
    {
        $comment_representation = new HTMLOrTextCommentRepresentation('last comment', 'last comment', 'text', null);
        return new ChangesetRepresentation(
            98,
            101,
            MinimalUserRepresentation::build($user),
            1234567890,
            null,
            $comment_representation,
            [],
            MinimalUserRepresentation::build($user),
            1234567890
        );
    }
}
