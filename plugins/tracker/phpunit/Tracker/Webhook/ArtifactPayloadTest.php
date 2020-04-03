<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\REST\ChangesetRepresentation;

require_once __DIR__ . '/../../bootstrap.php';


class ArtifactPayloadTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        \UserHelper::setInstance(\Mockery::spy(\UserHelper::class));
    }

    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testCreationIsIdentified()
    {
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(101);
        $user->shouldReceive('isAnonymous')->andReturns(false);
        $user->shouldReceive('getRealName')->andReturns('Real Name');
        $user->shouldReceive('getUserName')->andReturns('username');
        $user->shouldReceive('getLdapId')->andReturns(null);
        $user->shouldReceive('getAvatarUrl')->andReturns('');
        $user->shouldReceive('hasAvatar')->andReturns(false);
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getPreviousChangeset')->andReturns(null);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturns(1);
        $changeset->shouldReceive('getSubmitter')->andReturns($user);
        $changeset->shouldReceive('getArtifact')->andReturns($artifact);
        $changeset->shouldReceive('getFullRESTValue')->andReturns(\Mockery::mock(ChangesetRepresentation::class));

        $payload = new ArtifactPayload($changeset);

        $this->assertSame('create', $payload->getPayload()['action']);
        $this->assertNull($payload->getPayload()['previous']);
    }
}
