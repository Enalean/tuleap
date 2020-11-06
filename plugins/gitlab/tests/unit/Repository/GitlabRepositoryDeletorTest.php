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
use GitPermissionsManager;
use GitUserNotAdminException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class GitlabRepositoryDeletorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GitlabRepositoryDeletor
     */
    private $deletor;

    /**
     * @var GitPermissionsManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_permissions_manager;

    /**
     * @var DBTransactionExecutorPassthrough
     */
    private $db_transaction_executor;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryProjectDao
     */
    private $gitlab_repository_project_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SecretDao
     */
    private $secret_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryDao
     */
    private $gitlab_repository_dao;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var GitlabRepository
     */
    private $gitlab_repository;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_permissions_manager       = Mockery::mock(GitPermissionsManager::class);
        $this->db_transaction_executor       = new DBTransactionExecutorPassthrough();
        $this->gitlab_repository_project_dao = Mockery::mock(GitlabRepositoryProjectDao::class);
        $this->secret_dao                    = Mockery::mock(SecretDao::class);
        $this->gitlab_repository_dao         = Mockery::mock(GitlabRepositoryDao::class);

        $this->deletor = new GitlabRepositoryDeletor(
            $this->git_permissions_manager,
            $this->db_transaction_executor,
            $this->gitlab_repository_project_dao,
            $this->secret_dao,
            $this->gitlab_repository_dao
        );

        $this->gitlab_repository = new GitlabRepository(
            1,
            156981,
            'Repo01',
            'root/repo01',
            '',
            'https://example.com/gitlab/root/repo01',
            new DateTimeImmutable()
        );

        $this->project = Project::buildForTest();
        $this->user    = Mockery::mock(PFUser::class);
    }

    public function testItThrowsAnExceptionIfUserIsNotGitAdministrator(): void
    {
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with($this->user, $this->project)
            ->andReturnFalse();

        $this->expectException(GitUserNotAdminException::class);

        $this->deletor->deleteRepositoryInProject(
            $this->gitlab_repository,
            $this->project,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfGitlabRepositoryNotIntegratedInAnyProject(): void
    {
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with($this->user, $this->project)
            ->andReturnTrue();

        $this->gitlab_repository_project_dao->shouldReceive('searchProjectsTheGitlabRepositoryIsIntegratedIn')
            ->andReturn([]);

        $this->expectException(GitlabRepositoryNotIntegratedInAnyProjectException::class);

        $this->deletor->deleteRepositoryInProject(
            $this->gitlab_repository,
            $this->project,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfGitlabRepositoryNotIntegratedInProvidedProject(): void
    {
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with($this->user, $this->project)
            ->andReturnTrue();

        $this->gitlab_repository_project_dao->shouldReceive('searchProjectsTheGitlabRepositoryIsIntegratedIn')
            ->andReturn([
                102, 103
            ]);

        $this->expectException(GitlabRepositoryNotInProjectException::class);

        $this->deletor->deleteRepositoryInProject(
            $this->gitlab_repository,
            $this->project,
            $this->user
        );
    }

    public function testItDeletesIntegrationIfRepositoryIsIntegratedInMultipleProject(): void
    {
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with($this->user, $this->project)
            ->andReturnTrue();

        $this->gitlab_repository_project_dao->shouldReceive('searchProjectsTheGitlabRepositoryIsIntegratedIn')
            ->andReturn([
                101, 103
            ]);

        $this->gitlab_repository_project_dao->shouldReceive('removeGitlabRepositoryIntegrationInProject')
            ->once();

        $this->secret_dao->shouldNotReceive('deleteGitlabRepositoryWebhookSecret');
        $this->gitlab_repository_dao->shouldNotReceive('deleteGitlabRepository');

        $this->deletor->deleteRepositoryInProject(
            $this->gitlab_repository,
            $this->project,
            $this->user
        );
    }

    public function testItDeletesAllGitlabRepositoryDataIfRepositoryIsIntegratedInOneProject(): void
    {
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with($this->user, $this->project)
            ->andReturnTrue();

        $this->gitlab_repository_project_dao->shouldReceive('searchProjectsTheGitlabRepositoryIsIntegratedIn')
            ->andReturn([
                101
            ]);

        $this->gitlab_repository_project_dao->shouldReceive('removeGitlabRepositoryIntegrationInProject')
            ->once();
        $this->secret_dao->shouldReceive('deleteGitlabRepositoryWebhookSecret')->once();
        $this->gitlab_repository_dao->shouldReceive('deleteGitlabRepository')->once();

        $this->deletor->deleteRepositoryInProject(
            $this->gitlab_repository,
            $this->project,
            $this->user
        );
    }
}
