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

use Luracast\Restler\RestException;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryGroupLinkHandler;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Builder\GitlabProjectBuilder;
use Tuleap\Gitlab\Test\Stubs\AddNewGroupLinkStub;
use Tuleap\Gitlab\Test\Stubs\BuildGitlabProjectsStub;
use Tuleap\Gitlab\Test\Stubs\InsertGroupLinkTokenStub;
use Tuleap\Gitlab\Test\Stubs\IntegrateGitlabProjectStub;
use Tuleap\Gitlab\Test\Stubs\RetrieveGitlabGroupInformationStub;
use Tuleap\Gitlab\Test\Stubs\VerifyGroupIsAlreadyLinkedStub;
use Tuleap\Gitlab\Test\Stubs\VerifyProjectIsAlreadyLinkedStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GroupLinkCreatorTest extends TestCase
{
    private const INTEGRATED_GROUP_ID = 15;
    private const PROJECT_ID          = 101;

    private BuildGitlabProjectsStub $project_builder;
    private VerifyGroupIsAlreadyLinkedStub $group_integrated_verifier;
    private VerifyProjectIsAlreadyLinkedStub $project_linked_verifier;
    private ?string $branch_prefix;

    #[\Override]
    protected function setUp(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::withProjects([
            GitlabProjectBuilder::aGitlabProject(19)->build(),
            GitlabProjectBuilder::aGitlabProject(15)->build(),
            GitlabProjectBuilder::aGitlabProject(21)->build(),

        ]);
        $this->group_integrated_verifier = VerifyGroupIsAlreadyLinkedStub::withNeverLinked();
        $this->project_linked_verifier   = VerifyProjectIsAlreadyLinkedStub::withNeverLinked();

        $this->branch_prefix = 'dev-';
    }

    /**
     * @return Ok<GitlabGroupRepresentation>|Err<Fault>
     * @throws RestException
     */
    private function createGroup(): Ok|Err
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->withPublicName('exegetist')->build();

        $creator = new GroupLinkCreator(
            $this->project_builder,
            RetrieveGitlabGroupInformationStub::buildDefault(),
            new GitlabRepositoryGroupLinkHandler(
                new DBTransactionExecutorPassthrough(),
                new GroupLinkFactory(
                    $this->group_integrated_verifier,
                    $this->project_linked_verifier,
                    AddNewGroupLinkStub::withGroupId(self::INTEGRATED_GROUP_ID),
                ),
                InsertGroupLinkTokenStub::build(),
                IntegrateGitlabProjectStub::withOkResult()
            )
        );

        $credentials = CredentialsTestBuilder::get()->build();
        $post        = new GitlabGroupPOSTRepresentation(
            self::PROJECT_ID,
            1,
            'azertyuiop',
            'https://gitlab.example.com',
            true,
            $this->branch_prefix
        );

        return $creator->createGroupAndIntegrations($credentials, $post, $project);
    }

    public function testItReturnsTheRepresentation(): void
    {
        $result = $this->createGroup();

        self::assertTrue(Result::isOk($result));
        self::assertSame(self::INTEGRATED_GROUP_ID, $result->value->id);
        self::assertSame(3, $result->value->number_of_integrations);
    }

    public function testItThrowsExceptionIfTheGitlabRepositoryIsInError(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::buildWithException(
            new GitlabRequestException(
                500,
                'What a fail !',
                null
            )
        );

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsExceptionIfTheRequestResultHasSomeErrors(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::buildWithException(
            new GitlabResponseAPIException('fail')
        );

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsExceptionIfTheGitlabGroupAlreadyExists(): void
    {
        $this->group_integrated_verifier = VerifyGroupIsAlreadyLinkedStub::withAlwaysLinked();

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsIfTheProjectIsAlreadyLinkedToAGitlabGroup(): void
    {
        $this->project_linked_verifier = VerifyProjectIsAlreadyLinkedStub::withAlwaysLinked();

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsIfTheBranchPrefixProducesInvalidBranchNames(): void
    {
        $this->branch_prefix = 'invalid:';

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsWhenTheBranchPrefixIsNull(): void
    {
        $this->branch_prefix = null;

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->createGroup();
    }
}
