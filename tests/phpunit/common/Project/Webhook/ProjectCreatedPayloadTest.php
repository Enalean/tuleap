<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Webhook;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class ProjectCreatedPayloadTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider ownerDataProvider
     */
    public function testPayloadCreation(bool $has_owner) : void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getStartDate')->andReturn(0);
        $project->shouldReceive('getPublicName')->andReturn('public_name');
        $project->shouldReceive('getUnixName')->andReturn('unix_name');
        $project->shouldReceive('getID')->andReturn(101);
        $project->shouldReceive('getAccess')->andReturn('private');
        $owner = null;
        if ($has_owner) {
            $owner = \Mockery::mock(\PFUser::class);
            $owner->shouldReceive('getId')->andReturn(10);
            $owner->shouldReceive('getEmail')->andReturn('tuleap@example.com');
            $owner->shouldReceive('getRealName')->andReturn('user realname');
            $project->shouldReceive('getAdmins')->andReturn([$owner]);
        } else {
            $project->shouldReceive('getAdmins')->andReturn([]);
        }

        $payload = new ProjectCreatedPayload($project, 0);
        $this->assertEqualsCanonicalizing(
            [
                'created_at'          => '1970-01-01T00:00:00+00:00',
                'updated_at'          => '1970-01-01T00:00:00+00:00',
                'event_name'          => 'project_create',
                'name'                => 'public_name',
                'owner_id'            => $owner !== null ? $owner->getId() : null,
                'owner_email'         => $owner !== null ? $owner->getEmail() : null,
                'owner_name'          => $owner !== null ? $owner->getRealName() : null,
                'path'                => 'unix_name',
                'path_with_namespace' => 'unix_name',
                'project_id'          => 101,
                'project_visibility'  => 'private',
            ],
            $payload->getPayload()
        );
    }

    public function ownerDataProvider() : array
    {
        return [
            [true],
            [false]
        ];
    }
}
