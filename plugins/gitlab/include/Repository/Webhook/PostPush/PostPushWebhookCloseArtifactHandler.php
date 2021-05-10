<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Psr\Log\LoggerInterface;
use Tracker_Semantic_StatusFactory;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Gitlab\Artifact\ArtifactNotFoundException;
use Tuleap\Gitlab\Artifact\ArtifactRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use UserManager;
use UserNotExistException;

class PostPushWebhookCloseArtifactHandler
{
    /**
     * @var PostPushCommitBotCommenter
     */
    private $commit_bot_commenter;
    /**
     * @var ArtifactRetriever
     */
    private $artifact_retriever;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;

    public function __construct(
        PostPushCommitBotCommenter $commit_bot_commenter,
        ArtifactRetriever $artifact_retriever,
        UserManager $user_manager,
        Tracker_Semantic_StatusFactory $semantic_status_factory,
        LoggerInterface $logger
    ) {
        $this->commit_bot_commenter    = $commit_bot_commenter;
        $this->artifact_retriever      = $artifact_retriever;
        $this->user_manager            = $user_manager;
        $this->semantic_status_factory = $semantic_status_factory;
        $this->logger                  = $logger;
    }

    public function handleArtifactClosure(
        WebhookTuleapReference $tuleap_reference,
        PostPushCommitWebhookData $post_push_commit_webhook_data
    ): void {
        if ($tuleap_reference->getCloseArtifactKeyword() === null) {
            return;
        }

        try {
            $artifact = $this->artifact_retriever->retrieveArtifactById($tuleap_reference);

            $tracker_workflow_user = $this->user_manager->getUserById(Tracker_Workflow_WorkflowUser::ID);
            if (! $tracker_workflow_user) {
                throw new UserNotExistException("Tracker Workflow Manager does not exists, the comment cannot be added");
            }

            $status_semantic = $this->semantic_status_factory->getByTracker(
                $artifact->getTracker()
            );

            if ($status_semantic->getField() !== null) {
                $this->logger->info("Status semantic defined for artifact #{$tuleap_reference->getId()}.");
                return;
            }

            $this->commit_bot_commenter->addTuleapArtifactComment(
                $artifact,
                $tracker_workflow_user,
                $post_push_commit_webhook_data
            );
        } catch (ArtifactNotFoundException $e) {
            $this->logger->error("Artifact #{$tuleap_reference->getId()} not found");
        }
    }
}
