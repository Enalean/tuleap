<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Group;

use Tuleap\Git\Tests\Stub\VerifyUserIsGitAdministratorStub;
use Tuleap\Gitlab\Core\ProjectRetriever;
use Tuleap\Gitlab\Permission\GitAdministratorChecker;
use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Gitlab\Test\Stubs\DeleteGroupLinkStub;
use Tuleap\Gitlab\Test\Stubs\RetrieveGroupLinkByIdStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

final class GroupUnlinkHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const GROUP_LINK_ID = 29;
    private DeleteGroupLinkStub $group_link_deleter;

    protected function setUp(): void
    {
        $this->group_link_deleter = DeleteGroupLinkStub::withCallCount();
    }

    private function unlink(): Ok|Err
    {
        $project_id = 155;

        $handler = new GroupUnlinkHandler(
            new ProjectRetriever(
                ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId($project_id)->build())
            ),
            new GitAdministratorChecker(VerifyUserIsGitAdministratorStub::withAlwaysGitAdministrator()),
            new GroupLinkRetriever(
                RetrieveGroupLinkByIdStub::withSuccessiveGroupLinks(
                    GroupLinkBuilder::aGroupLink(self::GROUP_LINK_ID)->withProjectId($project_id)->build()
                )
            ),
            $this->group_link_deleter
        );
        return $handler->unlinkProjectAndGroup(self::GROUP_LINK_ID, UserTestBuilder::buildWithDefaults());
    }

    public function testItUnlinksProjectAndGroup(): void
    {
        $result = $this->unlink();
        self::assertTrue(Result::isOk($result));
        self::assertSame(1, $this->group_link_deleter->getCallCount());
    }
}
