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
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenInserter;
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryDao
     */
    private $gitlab_repository_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryProjectDao
     */
    private $gitlab_repository_project_dao;

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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabBotApiTokenInserter
     */
    private $token_inserter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gitlab_repository_factory     = Mockery::mock(GitlabRepositoryFactory::class);
        $this->gitlab_repository_dao         = Mockery::mock(GitlabRepositoryDao::class);
        $this->gitlab_repository_project_dao = Mockery::mock(GitlabRepositoryProjectDao::class);
        $this->webhook_creator               = Mockery::mock(WebhookCreator::class);
        $this->token_inserter                = Mockery::mock(GitlabBotApiTokenInserter::class);

        $this->credentials = CredentialsTestBuilder::get()->build();

        $this->creator = new GitlabRepositoryCreator(
            new DBTransactionExecutorPassthrough(),
            $this->gitlab_repository_factory,
            $this->gitlab_repository_dao,
            $this->gitlab_repository_project_dao,
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
        $this->gitlab_repository_dao->shouldReceive('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->once()
            ->andReturnTrue();

        $this->gitlab_repository_factory->shouldNotReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath');
        $this->gitlab_repository_project_dao->shouldNotReceive('isGitlabRepositoryIntegratedInProject');
        $this->gitlab_repository_project_dao->shouldNotReceive('addGitlabRepositoryIntegrationInProject');
        $this->gitlab_repository_dao->shouldNotReceive('createGitlabRepository');
        $this->gitlab_repository_factory->shouldNotReceive('getGitlabRepositoryByGitlabProjectAndIntegrationId');
        $this->gitlab_repository_project_dao->shouldNotReceive('addGitlabRepositoryIntegrationInProject');
        $this->webhook_creator->shouldNotReceive('addWebhookInGitlabProject');

        $this->expectException(GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException::class);

        $this->creator->integrateGitlabRepositoryInProject(
            $this->credentials,
            $this->gitlab_project,
            $this->project
        );
    }

    public function testItAddsRepositoryInProjectIfAtLeastOneIntegrationAlreadyExists(): void
    {
        $this->gitlab_repository_dao->shouldReceive('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->once()
            ->andReturnFalse();

        $gitlab_repository = $this->buildGitlabRepository();

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->once()
            ->with(12569, 'https://example.com/root/project01')
            ->andReturn($gitlab_repository);

        $this->gitlab_repository_project_dao->shouldReceive('isGitlabRepositoryIntegratedInProject')
            ->once()
            ->with(1, 101)
            ->andReturnFalse();

        $this->gitlab_repository_project_dao->shouldReceive('addGitlabRepositoryIntegrationInProject')->once();

        $this->gitlab_repository_dao->shouldNotReceive('createGitlabRepository');
        $this->gitlab_repository_factory->shouldNotReceive('getGitlabRepositoryByGitlabProjectAndIntegrationId');
        $this->gitlab_repository_project_dao->shouldNotReceive('addGitlabRepositoryIntegrationInProject');
        $this->webhook_creator->shouldNotReceive('addWebhookInGitlabProject');
        $this->creator->integrateGitlabRepositoryInProject(
            $this->credentials,
            $this->gitlab_project,
            $this->project
        );
    }

    public function testItThrowsAnExceptionIfRepositoryIsAlreadyIntegratedInProject(): void
    {
        $this->gitlab_repository_dao->shouldReceive('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->once()
            ->andReturnFalse();

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->once()
            ->with(12569, 'https://example.com/root/project01')
            ->andReturn(
                $this->buildGitlabRepository()
            );

        $this->gitlab_repository_project_dao->shouldReceive('isGitlabRepositoryIntegratedInProject')
            ->once()
            ->with(1, 101)
            ->andReturnTrue();

        $this->gitlab_repository_project_dao->shouldNotReceive('addGitlabRepositoryIntegrationInProject');
        $this->gitlab_repository_dao->shouldNotReceive('createGitlabRepository');
        $this->gitlab_repository_factory->shouldNotReceive('getGitlabRepositoryByGitlabProjectAndIntegrationId');
        $this->gitlab_repository_project_dao->shouldNotReceive('addGitlabRepositoryIntegrationInProject');
        $this->webhook_creator->shouldNotReceive('addWebhookInGitlabProject');

        $this->expectException(GitlabRepositoryAlreadyIntegratedInProjectException::class);

        $this->creator->integrateGitlabRepositoryInProject(
            $this->credentials,
            $this->gitlab_project,
            $this->project
        );
    }

    public function testItCreatesTheWholeRepositoryIntegrationIfThisIsTheFirstTimeTheGitlabRepositoryIsIntegrated(): void
    {
        $this->gitlab_repository_dao->shouldReceive('isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject')
            ->once()
            ->andReturnFalse();

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByGitlabRepositoryIdAndPath')
            ->once()
            ->with(12569, 'https://example.com/root/project01')
            ->andReturn(null);


        $this->gitlab_repository_project_dao->shouldNotReceive('isGitlabRepositoryIntegratedInProject');
        $this->gitlab_repository_project_dao->shouldNotReceive('addGitlabRepositoryIntegrationInProject');

        $this->gitlab_repository_dao->shouldReceive('createGitlabRepository')
            ->once()
            ->andReturn(1);

        $gitlab_repository = $this->buildGitlabRepository();
        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByGitlabProjectAndId')
            ->once()
            ->with($this->gitlab_project, 1)
            ->andReturn($gitlab_repository);

        $this->gitlab_repository_project_dao->shouldReceive('addGitlabRepositoryIntegrationInProject')
            ->once()
            ->with(1, 101);

        $this->webhook_creator->shouldReceive('generateWebhookInGitlabProject')
            ->once()
            ->with($this->credentials, $gitlab_repository);

        $this->token_inserter
            ->shouldReceive('insertToken')
            ->with($gitlab_repository, $this->credentials->getBotApiToken()->getToken())
            ->once();

        $result = $this->creator->integrateGitlabRepositoryInProject(
            $this->credentials,
            $this->gitlab_project,
            $this->project
        );

        $this->assertSame($gitlab_repository, $result);
    }

    private function buildGitlabRepository(): GitlabRepository
    {
        return new GitlabRepository(
            1,
            12569,
            'root/project01',
            'Desc',
            'https://example.com/root/project01',
            new DateTimeImmutable(),
        );
    }
}
