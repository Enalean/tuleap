<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use Luracast\Restler\RestException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabBranchInformationRetrieverTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&GitlabProjectBuilder
     */
    private $project_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&CredentialsRetriever
     */
    private $credentials_retriever;
    private GitlabBranchInformationRetriever $branch_information_retriever;

    protected function setUp(): void
    {
        $this->repository_integration_factory = $this->createStub(GitlabRepositoryIntegrationFactory::class);
        $this->credentials_retriever          = $this->createStub(CredentialsRetriever::class);
        $this->project_builder                = $this->createStub(GitlabProjectBuilder::class);
        $this->branch_information_retriever   = new GitlabBranchInformationRetriever($this->repository_integration_factory, $this->credentials_retriever, $this->project_builder);
    }

    public function testRetrievesInformationAboutBranches(): void
    {
        $current_user = $this->createStub(\PFUser::class);
        $current_user->method('isMember')->willReturn(true);
        $this->repository_integration_factory->method('getIntegrationById')->willReturn(self::buildGitlabRepositoryIntegration());
        $this->credentials_retriever->method('getCredentials')->willReturn(CredentialsTestBuilder::get()->build());
        $gitlab_project = new GitlabProject(
            9,
            'Description',
            'https://gitlab.example.com',
            '/',
            new \DateTimeImmutable('@0'),
            'main'
        );
        $this->project_builder->method('getProjectFromGitlabAPI')->willReturn($gitlab_project);

        $information = $this->branch_information_retriever->getBranchInformation($current_user, 1);

        self::assertEquals('main', $information->default_branch);
    }

    public function testCannotAccessInformationWhenIntegrationDoesNotExist(): void
    {
        $this->repository_integration_factory->method('getIntegrationById')->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->branch_information_retriever->getBranchInformation(UserTestBuilder::aUser()->build(), 404);
    }

    public function testCannotAccessInformationWithoutBeingAProjectMember(): void
    {
        $current_user = $this->createStub(\PFUser::class);
        $current_user->method('isMember')->willReturn(false);
        $this->repository_integration_factory->method('getIntegrationById')->willReturn(self::buildGitlabRepositoryIntegration());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->branch_information_retriever->getBranchInformation($current_user, 1);
    }

    public function testCannotAccessInformationWithoutHavingCredentialsForTheIntegration(): void
    {
        $current_user = $this->createStub(\PFUser::class);
        $current_user->method('isMember')->willReturn(true);
        $this->repository_integration_factory->method('getIntegrationById')->willReturn(self::buildGitlabRepositoryIntegration());
        $this->credentials_retriever->method('getCredentials')->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->branch_information_retriever->getBranchInformation($current_user, 1);
    }

    /**
     * @psalm-param class-string<\Exception> $exception_name
     */
    #[DataProvider('dataProviderGitlabAPIFailures')]
    public function testCannotAccessWhenRequestToTheGitLabAPIDoesNotSucceed(string $exception_name): void
    {
        $current_user = $this->createStub(\PFUser::class);
        $current_user->method('isMember')->willReturn(true);
        $this->repository_integration_factory->method('getIntegrationById')->willReturn(self::buildGitlabRepositoryIntegration());
        $this->credentials_retriever->method('getCredentials')->willReturn(CredentialsTestBuilder::get()->build());

        $this->project_builder->method('getProjectFromGitlabAPI')->willThrowException($this->createStub($exception_name));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->branch_information_retriever->getBranchInformation($current_user, 1);
    }

    public static function dataProviderGitlabAPIFailures(): array
    {
        return [
            [GitlabRequestException::class],
            [GitlabResponseAPIException::class],
        ];
    }

    private static function buildGitlabRepositoryIntegration(): GitlabRepositoryIntegration
    {
        return new GitlabRepositoryIntegration(
            1,
            9,
            'Name',
            'Description',
            'https://gitlab.example.com',
            new \DateTimeImmutable('@0'),
            ProjectTestBuilder::aProject()->build(),
            false
        );
    }
}
