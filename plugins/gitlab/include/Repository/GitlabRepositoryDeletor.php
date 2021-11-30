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

use GitPermissionsManager;
use GitUserNotAdminException;
use PFUser;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixDao;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchInfoDao;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\TagPush\TagInfoDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookDeletor;

class GitlabRepositoryDeletor
{
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;
    /**
     * @var GitlabRepositoryIntegrationDao
     */
    private $gitlab_repository_dao;
    /**
     * @var IntegrationApiTokenDao
     */
    private $gitlab_bot_api_token_dao;
    /**
     * @var CommitTuleapReferenceDao
     */
    private $commit_tuleap_reference_dao;
    /**
     * @var MergeRequestTuleapReferenceDao
     */
    private $merge_request_dao;
    /**
     * @var WebhookDeletor
     */
    private $webhook_deletor;
    /**
     * @var CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var TagInfoDao
     */
    private $tag_info_dao;

    private BranchInfoDao $branch_info_dao;
    private CreateBranchPrefixDao $branch_prefix_dao;

    public function __construct(
        GitPermissionsManager $git_permissions_manager,
        DBTransactionExecutor $db_transaction_executor,
        WebhookDeletor $webhook_deletor,
        GitlabRepositoryIntegrationDao $gitlab_repository_dao,
        IntegrationApiTokenDao $gitlab_bot_api_token_dao,
        CommitTuleapReferenceDao $commit_tuleap_reference_dao,
        MergeRequestTuleapReferenceDao $merge_request_dao,
        TagInfoDao $tag_info_dao,
        BranchInfoDao $branch_info_dao,
        CredentialsRetriever $credentials_retriever,
        CreateBranchPrefixDao $branch_prefix_dao,
    ) {
        $this->git_permissions_manager     = $git_permissions_manager;
        $this->db_transaction_executor     = $db_transaction_executor;
        $this->webhook_deletor             = $webhook_deletor;
        $this->gitlab_repository_dao       = $gitlab_repository_dao;
        $this->gitlab_bot_api_token_dao    = $gitlab_bot_api_token_dao;
        $this->commit_tuleap_reference_dao = $commit_tuleap_reference_dao;
        $this->merge_request_dao           = $merge_request_dao;
        $this->tag_info_dao                = $tag_info_dao;
        $this->credentials_retriever       = $credentials_retriever;
        $this->branch_info_dao             = $branch_info_dao;
        $this->branch_prefix_dao           = $branch_prefix_dao;
    }

    /**
     * @throws GitUserNotAdminException
     */
    public function deleteRepositoryIntegration(
        GitlabRepositoryIntegration $repository_integration,
        PFUser $user,
    ): void {
        $project = $repository_integration->getProject();
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
            throw new GitUserNotAdminException();
        }

        $this->db_transaction_executor->execute(function () use ($repository_integration, $project) {
            $integration_id         = $repository_integration->getId();
            $integration_path       = $repository_integration->getName();
            $integration_project_id = (int) $repository_integration->getProject()->getID();

            $credentials = $this->credentials_retriever->getCredentials($repository_integration);
            $this->webhook_deletor->deleteGitlabWebhookFromGitlabRepository($credentials, $repository_integration);
            $this->gitlab_bot_api_token_dao->deleteIntegrationToken($integration_id);
            $this->commit_tuleap_reference_dao->deleteCommitsInIntegration(
                $integration_path,
                $integration_id,
                $integration_project_id
            );
            $this->merge_request_dao->deleteAllMergeRequestInIntegration(
                $integration_path,
                $integration_id,
                $integration_project_id
            );
            $this->tag_info_dao->deleteTagsInIntegration(
                $integration_path,
                $integration_id,
                $integration_project_id
            );
            $this->branch_info_dao->deleteBranchesInIntegration(
                $integration_path,
                $integration_id,
                $integration_project_id
            );
            $this->branch_prefix_dao->deleteIntegrationPrefix(
                $integration_id
            );
            $this->gitlab_repository_dao->deleteIntegration($integration_id);
        });
    }
}
