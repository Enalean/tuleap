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
use PFUser;
use Project;
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixDao;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchInfoDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDeletor;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabRepositoryDeletorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var GitlabRepositoryDeletor
     */
    private $deletor;
    /**
     * @var DBTransactionExecutorPassthrough
     */
    private $db_transaction_executor;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var GitlabRepositoryIntegration
     */
    private $gitlab_repository_integration;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&WebhookDeletor
     */
    private $webhook_deletor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CommitTuleapReferenceDao
     */
    private $commit_tuleap_reference_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationDao
     */
    private $repository_integration_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IntegrationApiTokenDao
     */
    private $integration_api_token_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&MergeRequestTuleapReferenceDao
     */
    private $merge_request_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TagInfoDao
     */
    private $tag_info_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BranchInfoDao
     */
    private $branch_info_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CreateBranchPrefixDao
     */
    private $branch_prefix_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_permissions_manager     = $this->createMock(GitPermissionsManager::class);
        $this->db_transaction_executor     = new DBTransactionExecutorPassthrough();
        $this->webhook_deletor             = $this->createMock(WebhookDeletor::class);
        $this->commit_tuleap_reference_dao = $this->createMock(CommitTuleapReferenceDao::class);
        $this->repository_integration_dao  = $this->createMock(GitlabRepositoryIntegrationDao::class);
        $this->integration_api_token_dao   = $this->createMock(IntegrationApiTokenDao::class);
        $this->merge_request_dao           = $this->createMock(MergeRequestTuleapReferenceDao::class);
        $this->tag_info_dao                = $this->createMock(TagInfoDao::class);
        $this->credentials_retriever       = $this->createMock(CredentialsRetriever::class);
        $this->branch_info_dao             = $this->createMock(BranchInfoDao::class);
        $this->branch_prefix_dao           = $this->createMock(CreateBranchPrefixDao::class);

        $this->deletor = new GitlabRepositoryDeletor(
            $this->git_permissions_manager,
            $this->db_transaction_executor,
            $this->webhook_deletor,
            $this->repository_integration_dao,
            $this->integration_api_token_dao,
            $this->commit_tuleap_reference_dao,
            $this->merge_request_dao,
            $this->tag_info_dao,
            $this->branch_info_dao,
            $this->credentials_retriever,
            $this->branch_prefix_dao
        );

        $this->project = ProjectTestBuilder::aProject()->build();
        $this->user    = $this->createMock(PFUser::class);

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
        $this->git_permissions_manager
            ->expects(self::once())
            ->method('userIsGitAdmin')
            ->with($this->user, $this->project)
            ->willReturn(false);

        $this->expectException(GitUserNotAdminException::class);

        $this->deletor->deleteRepositoryIntegration(
            $this->gitlab_repository_integration,
            $this->user
        );
    }

    public function testItDeletesAllIntegrationData(): void
    {
        $this->git_permissions_manager
            ->expects(self::once())
            ->method('userIsGitAdmin')
            ->with($this->user, $this->project)
            ->willReturn(true);

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->expects(self::once())
            ->method('getCredentials')
            ->willReturn($credentials);

        $this->webhook_deletor
            ->expects(self::once())
            ->method('deleteGitlabWebhookFromGitlabRepository')
            ->with($credentials, $this->gitlab_repository_integration);

        $this->repository_integration_dao
            ->expects(self::once())
            ->method('deleteIntegration');
        $this->integration_api_token_dao
            ->expects(self::once())
            ->method('deleteIntegrationToken');

        $this->commit_tuleap_reference_dao
            ->expects(self::once())
            ->method('deleteCommitsInIntegration')
            ->with('root/repo01', 1, 101);

        $this->merge_request_dao
            ->expects(self::once())
            ->method('deleteAllMergeRequestInIntegration');
        $this->tag_info_dao
            ->expects(self::once())
            ->method('deleteTagsInIntegration');
        $this->branch_info_dao
            ->expects(self::once())
            ->method('deleteBranchesInIntegration');
        $this->branch_prefix_dao
            ->expects(self::once())
            ->method('deleteIntegrationPrefix');

        $this->deletor->deleteRepositoryIntegration(
            $this->gitlab_repository_integration,
            $this->user
        );
    }
}
