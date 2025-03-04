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

use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;
use Tuleap\Gitlab\Repository\GitlabRepositoryGroupLinkHandler;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Stubs\AddNewGroupLinkStub;
use Tuleap\Gitlab\Test\Stubs\IntegrateGitlabProjectStub;
use Tuleap\Gitlab\Test\Stubs\InsertGroupLinkTokenStub;
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
final class GitlabRepositoryGroupLinkHandlerTest extends TestCase
{
    private const GROUP_ID                    = 45;
    private const SECOND_GITLAB_REPOSITORY_ID = 10;
    private const FIRST_GITLAB_REPOSITORY_ID  = 9;
    private string $branch_prefix;

    protected function setUp(): void
    {
        $this->branch_prefix = 'dev-';
    }

    /**
     * @return Ok<GitlabGroupRepresentation>|Err<Fault>
     */
    private function integrate(): Ok|Err
    {
        $gitlab_projects = [
            new GitlabProject(
                self::FIRST_GITLAB_REPOSITORY_ID,
                'Description',
                'https://gitlab.example.com',
                '/',
                new \DateTimeImmutable('@0'),
                'main'
            ),
            new GitlabProject(
                self::SECOND_GITLAB_REPOSITORY_ID,
                'Description 2',
                'https://gitlab.example.com',
                '/',
                new \DateTimeImmutable('@0'),
                'main'
            ),
        ];

        $project = ProjectTestBuilder::aProject()->build();

        $new_group = NewGroupLink::fromAPIRepresentation(
            GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi([
                'id'         => 102,
                'name'       => 'nine-nine',
                'avatar_url' => 'https://avatar.example.com',
                'full_path'  => 'brookyln/nine-nine',
                'web_url'    => 'https://gitlab.example.com/nine-nine',
            ]),
            $project,
            new \DateTimeImmutable(),
            true,
            $this->branch_prefix
        );

        $handler = new GitlabRepositoryGroupLinkHandler(
            new DBTransactionExecutorPassthrough(),
            new GroupLinkFactory(
                VerifyGroupIsAlreadyLinkedStub::withNeverLinked(),
                VerifyProjectIsAlreadyLinkedStub::withNeverLinked(),
                AddNewGroupLinkStub::withGroupId(self::GROUP_ID),
            ),
            InsertGroupLinkTokenStub::build(),
            IntegrateGitlabProjectStub::withOkResult()
        );

        $credentials = CredentialsTestBuilder::get()->build();

        return $handler->integrateGitlabRepositoriesInProject($credentials, $gitlab_projects, $project, $new_group);
    }

    public function testItReturnsTheNewGroupIDAndTheNumberOfRepositoriesIntegrated(): void
    {
        $result = $this->integrate();

        self::assertTrue(Result::isOk($result));
        self::assertSame(self::GROUP_ID, $result->value->id);
        self::assertSame(2, $result->value->number_of_integrations);
    }

    public function testItDoesNotRecreateRepositoriesThatWereAlreadyIntegrated(): void
    {
        $result = $this->integrate();

        self::assertTrue(Result::isOk($result));
        self::assertSame(self::GROUP_ID, $result->value->id);
        self::assertSame(2, $result->value->number_of_integrations);
    }
}
