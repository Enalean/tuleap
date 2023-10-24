<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group;

use Tuleap\Git\Stub\VerifyUserIsGitAdministratorStub;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabRequestFault;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\API\GitlabResponseAPIFault;
use Tuleap\Gitlab\Core\ProjectRetriever;
use Tuleap\Gitlab\Permission\GitAdministratorChecker;
use Tuleap\Gitlab\Test\Builder\GitlabProjectBuilder;
use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Gitlab\Test\Stubs\BuildGitlabProjectsStub;
use Tuleap\Gitlab\Test\Stubs\IntegrateGitlabProjectStub;
use Tuleap\Gitlab\Test\Stubs\RetrieveGroupLinkByIdStub;
use Tuleap\Gitlab\Test\Stubs\RetrieveGroupLinksCredentialsStub;
use Tuleap\Gitlab\Test\Stubs\UpdateSynchronizationDateStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

final class GroupLinkSynchronizerTest extends TestCase
{
    private const GROUP_LINK_ID = 10;
    private const PROJECT_ID    = 101;

    private BuildGitlabProjectsStub $project_builder;
    private UpdateSynchronizationDateStub $date_updater;

    protected function setUp(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::withProjects(
            [
                GitlabProjectBuilder::aGitlabProject(19)->build(),
                GitlabProjectBuilder::aGitlabProject(15)->build(),
            ]
        );
        $this->date_updater    = UpdateSynchronizationDateStub::withCallCount();
    }

    /**
     * @return Ok<GroupLinkSynchronized>|Err<Fault>
     *
     */
    private function synchronizeGroupLink(): Ok|Err
    {
        $group_link = GroupLinkBuilder::aGroupLink(self::GROUP_LINK_ID)
            ->withAllowArtifactClosure(true)
            ->withNoBranchPrefix()
            ->withProjectId(self::PROJECT_ID)
            ->build();

        $group_link_synchronize = new GroupLinkSynchronizer(
            new DBTransactionExecutorPassthrough(),
            new GroupLinkRetriever(
                RetrieveGroupLinkByIdStub::withSuccessiveGroupLinks(
                    $group_link
                )
            ),
            RetrieveGroupLinksCredentialsStub::withDefaultCredentials(),
            $this->project_builder,
            $this->date_updater,
            IntegrateGitlabProjectStub::withOkResult(),
            new ProjectRetriever(
                ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build())
            ),
            new GitAdministratorChecker(VerifyUserIsGitAdministratorStub::withAlwaysGitAdministrator()),
        );

        $group_link_command = new SynchronizeGroupLinkCommand(self::GROUP_LINK_ID, UserTestBuilder::buildWithDefaults());

        return $group_link_synchronize->synchronizeGroupLink($group_link_command);
    }

    public function testItSynchronizesTheGroupLink(): void
    {
        $result = $this->synchronizeGroupLink();

        self::assertTrue(Result::isOk($result));
        self::assertSame(self::GROUP_LINK_ID, $result->value->group_link_id);
        self::assertSame(2, $result->value->number_of_integrations);
        self::assertSame(1, $this->date_updater->getCallCount());
    }

    public function testItReturnAnErrorIfTheGitlabRequestFail(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::buildWithException(
            new GitlabRequestException(
                500,
                "What a fail !",
                null
            )
        );

        $result = $this->synchronizeGroupLink();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitlabRequestFault::class, $result->error);
    }

    public function testItReturnAnErrorIfTheGitlabResponseIsNotOk(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::buildWithException(
            new GitlabResponseAPIException("fail")
        );

        $result = $this->synchronizeGroupLink();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitlabResponseAPIFault::class, $result->error);
    }
}
