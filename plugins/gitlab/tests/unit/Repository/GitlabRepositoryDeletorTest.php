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
use Project;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDeletor;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class GitlabRepositoryDeletorTest extends \Tuleap\Test\PHPUnit\TestCase
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryIntegrationDao
     */
    private $repository_integration_dao;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var GitlabRepositoryIntegration
     */
    private $gitlab_repository_integration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IntegrationApiTokenDao
     */
    private $integration_api_token_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CommitTuleapReferenceDao
     */
    private $commit_tuleap_reference_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MergeRequestTuleapReferenceDao
     */
    private $merge_request_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WebhookDeletor
     */
    private $webhook_deletor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TagInfoDao
     */
    private $tag_info_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_permissions_manager     = Mockery::mock(GitPermissionsManager::class);
        $this->db_transaction_executor     = new DBTransactionExecutorPassthrough();
        $this->webhook_deletor             = Mockery::mock(WebhookDeletor::class);
        $this->commit_tuleap_reference_dao = Mockery::mock(CommitTuleapReferenceDao::class);
        $this->repository_integration_dao  = Mockery::mock(GitlabRepositoryIntegrationDao::class);
        $this->integration_api_token_dao   = Mockery::mock(IntegrationApiTokenDao::class);
        $this->merge_request_dao           = Mockery::mock(MergeRequestTuleapReferenceDao::class);
        $this->tag_info_dao                = Mockery::mock(TagInfoDao::class);
        $this->credentials_retriever       = Mockery::mock(CredentialsRetriever::class);

        $this->deletor = new GitlabRepositoryDeletor(
            $this->git_permissions_manager,
            $this->db_transaction_executor,
            $this->webhook_deletor,
            $this->repository_integration_dao,
            $this->integration_api_token_dao,
            $this->commit_tuleap_reference_dao,
            $this->merge_request_dao,
            $this->tag_info_dao,
            $this->credentials_retriever
        );

        $this->project = Project::buildForTest();
        $this->user    = Mockery::mock(PFUser::class);

        $this->gitlab_repository_integration = new GitlabRepositoryIntegration(
            1,
            156981,
            'root/repo01',
            '',
            'https://example.com/gitlab/root/repo01',
            new DateTimeImmutable(),
            $this->project,
            false
        );
    }

    public function testItThrowsAnExceptionIfUserIsNotGitAdministrator(): void
    {
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with($this->user, $this->project)
            ->andReturnFalse();

        $this->expectException(GitUserNotAdminException::class);

        $this->deletor->deleteRepositoryIntegration(
            $this->gitlab_repository_integration,
            $this->user
        );
    }

    public function testItDeletesAllIntegrationData(): void
    {
        $this->git_permissions_manager->shouldReceive('userIsGitAdmin')
            ->once()
            ->with($this->user, $this->project)
            ->andReturnTrue();

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->andReturn($credentials)
            ->once();

        $this->webhook_deletor
            ->shouldReceive('deleteGitlabWebhookFromGitlabRepository')
            ->once()
            ->with($credentials, $this->gitlab_repository_integration);

        $this->repository_integration_dao->shouldReceive('deleteIntegration')->once();
        $this->integration_api_token_dao->shouldReceive('deleteIntegrationToken')->once();

        $this->commit_tuleap_reference_dao
            ->shouldReceive('deleteCommitsInIntegration')
            ->with(1)
            ->once();

        $this->merge_request_dao->shouldReceive('deleteAllMergeRequestInIntegration')->once();
        $this->tag_info_dao->shouldReceive('deleteTagsInIntegration')->once();

        $this->deletor->deleteRepositoryIntegration(
            $this->gitlab_repository_integration,
            $this->user
        );
    }
}
