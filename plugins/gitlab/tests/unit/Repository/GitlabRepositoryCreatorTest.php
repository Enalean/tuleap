<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository;

use DateTimeImmutable;
use Project;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenInserter;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class GitlabRepositoryCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var GitlabRepositoryCreator
     */
    private $creator;
    /**
     * @var GitlabProject
     */
    private $gitlab_project;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var Credentials
     */
    private $credentials;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationDao
     */
    private $repository_integration_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&WebhookCreator
     */
    private $webhook_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IntegrationApiTokenInserter
     */
    private $token_inserter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository_integration_factory = $this->createMock(GitlabRepositoryIntegrationFactory::class);
        $this->repository_integration_dao     = $this->createMock(GitlabRepositoryIntegrationDao::class);
        $this->webhook_creator                = $this->createMock(WebhookCreator::class);
        $this->token_inserter                 = $this->createMock(IntegrationApiTokenInserter::class);

        $this->credentials = CredentialsTestBuilder::get()->build();

        $this->creator = new GitlabRepositoryCreator(
            new DBTransactionExecutorPassthrough(),
            $this->repository_integration_factory,
            $this->repository_integration_dao,
            $this->webhook_creator,
            $this->token_inserter
        );

        $this->gitlab_project = new GitlabProject(
            12569,
            'Desc',
            'https://example.com/root/project01',
            'root/project01',
            new DateTimeImmutable(),
            "main"
        );

        $this->project = ProjectTestBuilder::aProject()->build();
    }

    public function testItThrowsAnExceptionIfARepositoryWithSameNameAlreadyIntegratedInProject(): void
    {
        $this->repository_integration_dao
            ->expects(self::once())
            ->method('isTheGitlabRepositoryAlreadyIntegratedInProject')
            ->with(101, 12569, 'https://example.com/root/project01')
            ->willReturn(false);


        $this->repository_integration_dao
            ->expects(self::once())
            ->method('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->willReturn(true);

        $this->repository_integration_factory->expects(self::never())->method('createRepositoryIntegration');
        $this->webhook_creator->expects(self::never())->method('generateWebhookInGitlabProject');
        $this->token_inserter->expects(self::never())->method('insertToken');

        $this->expectException(GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException::class);

        $this->creator->integrateGitlabRepositoryInProject(
            $this->credentials,
            $this->gitlab_project,
            $this->project,
            GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration()
        );
    }

    public function testItThrowsAnExceptionIfRepositoryIsAlreadyIntegratedInProject(): void
    {
        $this->repository_integration_dao
            ->expects(self::once())
            ->method('isTheGitlabRepositoryAlreadyIntegratedInProject')
            ->with(101, 12569, 'https://example.com/root/project01')
            ->willReturn(true);

        $this->repository_integration_factory->expects(self::never())->method('createRepositoryIntegration');
        $this->webhook_creator->expects(self::never())->method('generateWebhookInGitlabProject');
        $this->token_inserter->expects(self::never())->method('insertToken');

        $this->expectException(GitlabRepositoryAlreadyIntegratedInProjectException::class);

        $this->creator->integrateGitlabRepositoryInProject(
            $this->credentials,
            $this->gitlab_project,
            $this->project,
            GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration()
        );
    }

    public function testItCreatesTheWholeRepositoryIntegration(): void
    {
        $configuration = GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration();

        $this->repository_integration_dao
            ->expects(self::once())
            ->method('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->willReturn(false);

        $this->repository_integration_dao
            ->expects(self::once())
            ->method('isTheGitlabRepositoryAlreadyIntegratedInProject')
            ->with(101, 12569, 'https://example.com/root/project01')
            ->willReturn(false);

        $integration = $this->buildGitlabRepositoryIntegration();
        $this->repository_integration_factory
            ->expects(self::once())
            ->method('createRepositoryIntegration')
            ->with($this->gitlab_project, $this->project, $configuration)
            ->willReturn($integration);

        $this->webhook_creator
            ->expects(self::once())
            ->method('generateWebhookInGitlabProject')
            ->with($this->credentials, $integration);

        $this->token_inserter
            ->expects(self::once())
            ->method('insertToken')
            ->with($integration, $this->credentials->getBotApiToken()->getToken());

        $result = $this->creator->integrateGitlabRepositoryInProject(
            $this->credentials,
            $this->gitlab_project,
            $this->project,
            $configuration
        );

        self::assertSame($integration, $result);
    }

    public function testItIntegratesSeveralNeededRepositories(): void
    {
        $gitlab_project_1 = $this->gitlab_project;

        $gitlab_project_fail_id = 1316;
        $gitlab_project_fail    = new GitlabProject(
            $gitlab_project_fail_id,
            'Desc',
            'https://example.com/root/projectFAIL',
            'root/projectFAIL',
            new DateTimeImmutable(),
            "main"
        );
        $gitlab_project_2_id    = 1400;
        $gitlab_project_2       = new GitlabProject(
            $gitlab_project_2_id,
            'Desc',
            'https://example.com/root/project103',
            'root/project103',
            new DateTimeImmutable(),
            "main"
        );


        $configuration = GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration();

        $this->repository_integration_dao
            ->expects(self::exactly(2))
            ->method('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->willReturn(false);

        $this->repository_integration_dao
            ->expects(self::exactly(3))
            ->method('isTheGitlabRepositoryAlreadyIntegratedInProject')
            ->withConsecutive([101, 12569, 'https://example.com/root/project01'], [101, $gitlab_project_fail_id, 'https://example.com/root/projectFAIL'], [101, $gitlab_project_2_id, 'https://example.com/root/project103'])
            ->willReturnOnConsecutiveCalls(false, true, false);

        $integrations = $this->buildGitlabRepositoriesIntegration();
        $this->repository_integration_factory
            ->expects(self::exactly(2))
            ->method('createRepositoryIntegration')
            ->withConsecutive([$gitlab_project_1, $this->project, $configuration], [$gitlab_project_2, $this->project, $configuration])
            ->willReturnOnConsecutiveCalls($integrations[0], $integrations[1]);

        $this->webhook_creator
            ->expects(self::exactly(2))
            ->method('generateWebhookInGitlabProject')
            ->withConsecutive([$this->credentials, $integrations[0]], [$this->credentials, $integrations[1]]);

        $this->token_inserter
            ->expects(self::exactly(2))
            ->method('insertToken')
            ->withConsecutive([$integrations[0], $this->credentials->getBotApiToken()->getToken()], [$integrations[1], $this->credentials->getBotApiToken()->getToken()]);

        $gitlab_projects = [$gitlab_project_1, $gitlab_project_fail, $gitlab_project_2];

        $result = $this->creator->integrateGitlabRepositoriesInProject(
            $this->credentials,
            $gitlab_projects,
            $this->project,
            $configuration
        );

        self::assertSame($integrations, $result);
    }

    private function buildGitlabRepositoryIntegration(): GitlabRepositoryIntegration
    {
        return new GitlabRepositoryIntegration(
            1,
            12569,
            'root/project01',
            'Desc',
            'https://example.com/root/project01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );
    }

    private function buildGitlabRepositoriesIntegration(): array
    {
        return [
            new GitlabRepositoryIntegration(
                1,
                12569,
                'root/project01',
                'Desc',
                'https://example.com/root/project01',
                new DateTimeImmutable(),
                $this->project,
                false
            ),
            new GitlabRepositoryIntegration(
                2,
                1300,
                'root/project103',
                'Desc',
                'https://example.com/root/project103',
                new DateTimeImmutable(),
                $this->project,
                false
            ),
        ];
    }
}
