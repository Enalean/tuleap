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

use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectCreatedPayloadTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @dataProvider ownerDataProvider
     */
    public function testPayloadCreation(bool $has_owner): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getStartDate')->willReturn(0);
        $project->method('getPublicName')->willReturn('public_name');
        $project->method('getUnixName')->willReturn('unix_name');
        $project->method('getID')->willReturn(101);
        $project->method('getAccess')->willReturn('private');
        $owner = null;
        if ($has_owner) {
            $owner = UserTestBuilder::aUser()
                ->withId(10)
                ->withEmail('tuleap@example.com')
                ->withRealName('user realname')
                ->build();
            $project->method('getAdmins')->willReturn([$owner]);
        } else {
            $project->method('getAdmins')->willReturn([]);
        }

        $payload = new ProjectCreatedPayload($project, 0);
        $this->assertEqualsCanonicalizing(
            [
                'created_at'          => '1970-01-01T00:00:00+00:00',
                'updated_at'          => '1970-01-01T00:00:00+00:00',
                'event_name'          => 'project_create',
                'name'                => 'public_name',
                'owner_id'            => $owner?->getId(),
                'owner_email'         => $owner?->getEmail(),
                'owner_name'          => $owner?->getRealName(),
                'path'                => 'unix_name',
                'path_with_namespace' => 'unix_name',
                'project_id'          => 101,
                'project_visibility'  => 'private',
            ],
            $payload->getPayload()
        );
    }

    public static function ownerDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
