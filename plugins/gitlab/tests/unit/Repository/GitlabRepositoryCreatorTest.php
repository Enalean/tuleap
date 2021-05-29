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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenInserter;
use Tuleap\Gitlab\Repository\Webhook\WebhookCreator;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class GitlabRepositoryCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GitlabRepositoryCreator
     */
    private $creator;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryIntegrationDao
     */
    private $repository_integration_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WebhookCreator
     */
    private $webhook_creator;

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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IntegrationApiTokenInserter
     */
    private $token_inserter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository_integration_factory = Mockery::mock(GitlabRepositoryIntegrationFactory::class);
        $this->repository_integration_dao     = Mockery::mock(GitlabRepositoryIntegrationDao::class);
        $this->webhook_creator                = Mockery::mock(WebhookCreator::class);
        $this->token_inserter                 = Mockery::mock(IntegrationApiTokenInserter::class);

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
        );

        $this->project = Project::buildForTest();
    }

    public function testItThrowsAnExceptionIfARepositoryWithSameNameAlreadyIntegratedInProject(): void
    {
        $this->repository_integration_dao->shouldReceive('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->once()
            ->andReturnTrue();

        $this->repository_integration_factory->shouldNotReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath');
        $this->repository_integration_dao->shouldNotReceive('createGitlabRepository');
        $this->repository_integration_factory->shouldNotReceive('getGitlabRepositoryByGitlabProjectAndIntegrationId');
        $this->webhook_creator->shouldNotReceive('addWebhookInGitlabProject');

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
        $this->repository_integration_dao->shouldReceive('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->once()
            ->andReturnFalse();

        $this->repository_integration_dao->shouldReceive('isTheGitlabRepositoryAlreadyIntegratedInProject')
            ->once()
            ->with(101, 12569, 'https://example.com/root/project01')
            ->andReturnTrue();

        $this->repository_integration_dao->shouldNotReceive('createGitlabRepository');
        $this->repository_integration_factory->shouldNotReceive('getGitlabRepositoryByGitlabProjectAndIntegrationId');
        $this->webhook_creator->shouldNotReceive('addWebhookInGitlabProject');

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

        $this->repository_integration_dao->shouldReceive('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->once()
            ->andReturnFalse();

        $this->repository_integration_dao->shouldReceive('isTheGitlabRepositoryAlreadyIntegratedInProject')
            ->once()
            ->with(101, 12569, 'https://example.com/root/project01')
            ->andReturnFalse();

        $integration = $this->buildGitlabRepositoryIntegration();
        $this->repository_integration_factory->shouldReceive('createRepositoryIntegration')
            ->once()
            ->with($this->gitlab_project, $this->project, $configuration)
            ->andReturn($integration);

        $this->webhook_creator->shouldReceive('generateWebhookInGitlabProject')
            ->once()
            ->with($this->credentials, $integration);

        $this->token_inserter
            ->shouldReceive('insertToken')
            ->with($integration, $this->credentials->getBotApiToken()->getToken())
            ->once();

        $result = $this->creator->integrateGitlabRepositoryInProject(
            $this->credentials,
            $this->gitlab_project,
            $this->project,
            $configuration
        );

        self::assertSame($integration, $result);
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
            Project::buildForTest(),
            false
        );
    }
}
