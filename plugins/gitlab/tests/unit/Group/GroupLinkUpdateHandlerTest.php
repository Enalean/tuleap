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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Git\Tests\Stub\VerifyUserIsGitAdministratorStub;
use Tuleap\Gitlab\Core\ProjectRetriever;
use Tuleap\Gitlab\Group\Token\GroupLinkApiToken;
use Tuleap\Gitlab\Permission\GitAdministratorChecker;
use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Gitlab\Test\Stubs\RetrieveGroupLinkByIdStub;
use Tuleap\Gitlab\Test\Stubs\UpdateArtifactClosureOfGroupLinkStub;
use Tuleap\Gitlab\Test\Stubs\UpdateBranchPrefixOfGroupLinkStub;
use Tuleap\Gitlab\Test\Stubs\UpdateGroupLinkTokenStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

final class GroupLinkUpdateHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const GROUP_LINK_ID = 62;
    private const PROJECT_ID    = 144;
    private GroupLink $updated_group_link;

    protected function setUp(): void
    {
        $this->updated_group_link = GroupLinkBuilder::aGroupLink(self::GROUP_LINK_ID)
            ->withAllowArtifactClosure(false)
            ->withBranchPrefix('dev/')
            ->withProjectId(self::PROJECT_ID)
            ->build();
    }

    private function handle(): Ok|Err
    {
        $group_link = GroupLinkBuilder::aGroupLink(self::GROUP_LINK_ID)
            ->withAllowArtifactClosure(true)
            ->withNoBranchPrefix()
            ->withProjectId(self::PROJECT_ID)
            ->build();
        $command    = new UpdateGroupLinkCommand(
            self::GROUP_LINK_ID,
            'dev/',
            false,
            GroupLinkApiToken::buildNewGroupToken(new ConcealedString("")),
            UserTestBuilder::buildWithDefaults()
        );

        $handler = new GroupLinkUpdateHandler(
            new ProjectRetriever(
                ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build())
            ),
            new GitAdministratorChecker(VerifyUserIsGitAdministratorStub::withAlwaysGitAdministrator()),
            new GroupLinkRetriever(
                RetrieveGroupLinkByIdStub::withSuccessiveGroupLinks($group_link, $this->updated_group_link)
            ),
            new GroupLinkUpdater(
                UpdateBranchPrefixOfGroupLinkStub::withCallCount(),
                UpdateArtifactClosureOfGroupLinkStub::withCallCount(),
                UpdateGroupLinkTokenStub::withCallCount()
            )
        );
        return $handler->handleGroupLinkUpdate($command);
    }

    public function testItReturnsTheUpdatedGroupLink(): void
    {
        $result = $this->handle();
        self::assertTrue(Result::isOk($result));
        self::assertSame($this->updated_group_link, $result->value);
    }
}
