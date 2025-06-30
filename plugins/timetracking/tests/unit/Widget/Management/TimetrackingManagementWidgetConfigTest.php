<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\Management;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Timetracking\Tests\Stub\SearchQueryByWidgetIdStub;
use Tuleap\Timetracking\Tests\Stub\SearchUsersByWidgetIdStub;
use Tuleap\User\REST\MinimalUserRepresentation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimetrackingManagementWidgetConfigTest extends TestCase
{
    public function testItReturnsATimetrackingWidgetConfig(): void
    {
        $id              = 21;
        $start_date      = 1234567890;
        $end_date        = null;
        $predefined_time = 'today';
        $user1           = UserTestBuilder::aUser()
            ->withId(101)
            ->withUserName('user1')
            ->withRealName('Mister User1')
            ->build();
        $user2           = UserTestBuilder::aUser()
            ->withId(102)
            ->withUserName('user2')
            ->withRealName('Mister User2')
            ->build();

        $provide_user_avatar_url = ProvideUserAvatarUrlStub::build();

        $result = TimetrackingManagementWidgetConfig::fromId(
            $id,
            SearchQueryByWidgetIdStub::build($id, $start_date, $end_date, $predefined_time),
            SearchUsersByWidgetIdStub::build([$user1->getId(), $user2->getId()]),
            RetrieveUserByIdStub::withUsers($user1, $user2),
            $provide_user_avatar_url
        );

        self::assertEquals($id, $result->id);
        self::assertEquals('2009-02-14T00:31:30+01:00', $result->start_date);
        self::assertNull($result->end_date);
        self::assertEquals('today', $result->predefined_time);

        $user1_representation = MinimalUserRepresentation::build($user1, $provide_user_avatar_url);
        $user2_representation = MinimalUserRepresentation::build($user2, $provide_user_avatar_url);

        self::assertEquals([$user1_representation, $user2_representation], $result->users);
    }
}
