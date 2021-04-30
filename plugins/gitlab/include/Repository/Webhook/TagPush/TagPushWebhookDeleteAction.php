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

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;

use CrossReferenceManager;
use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;

class TagPushWebhookDeleteAction
{
    /**
     * @var GitlabRepositoryProjectRetriever
     */
    private $gitlab_repository_project_retriever;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var TagInfoDao
     */
    private $tag_info_dao;
    /**
     * @var CrossReferenceManager
     */
    private $cross_reference_manager;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    public function __construct(
        GitlabRepositoryProjectRetriever $gitlab_repository_project_retriever,
        TagInfoDao $tag_info_dao,
        CrossReferenceManager $cross_reference_manager,
        LoggerInterface $logger,
        DBTransactionExecutor $db_transaction_executor
    ) {
        $this->gitlab_repository_project_retriever = $gitlab_repository_project_retriever;
        $this->tag_info_dao                        = $tag_info_dao;
        $this->cross_reference_manager             = $cross_reference_manager;
        $this->logger                              = $logger;
        $this->db_transaction_executor             = $db_transaction_executor;
    }

    public function deleteTagReferences(GitlabRepository $gitlab_repository, TagPushWebhookData $tag_push_webhook_data): void
    {
        $this->db_transaction_executor->execute(function () use ($gitlab_repository, $tag_push_webhook_data) {
            $tag_name = $tag_push_webhook_data->getTagName();

            $projects = $this->gitlab_repository_project_retriever->getProjectsGitlabRepositoryIsIntegratedIn(
                $gitlab_repository
            );

            $this->logger->info("Tag $tag_name has been deleted, all references will be removed from database");
            foreach ($projects as $project) {
                $this->cross_reference_manager->deleteEntity(
                    $gitlab_repository->getName() . '/' . $tag_name,
                    GitlabTagReference::NATURE_NAME,
                    (int) $project->getID()
                );
            }

            $this->tag_info_dao->deleteTagInGitlabRepository(
                $gitlab_repository->getId(),
                $tag_name
            );

            $this->logger->info("Tag data for $tag_name deleted in database");
        });
    }
}
