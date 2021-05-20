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

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_Exception;
use Tracker_NoChangeException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use UserManager;

class PostPushCommitArtifactUpdater
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var StatusValueRetriever
     */
    private $status_value_retriever;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        StatusValueRetriever $status_value_retriever,
        UserManager $user_manager,
        LoggerInterface $logger
    ) {
        $this->status_value_retriever = $status_value_retriever;
        $this->user_manager           = $user_manager;
        $this->logger                 = $logger;
    }

    public function closeTuleapArtifact(
        Artifact $artifact,
        PFUser $tracker_workflow_user,
        PostPushCommitWebhookData $commit,
        WebhookTuleapReference $tuleap_reference,
        \Tracker_FormElement_Field_List $status_field,
        GitlabRepositoryIntegration $gitlab_repository_integration
    ): void {
        try {
            if (! $artifact->isOpen()) {
                $this->logger->info(
                    "|  |  |_ Artifact #{$artifact->getId()} is already closed and can not be closed automatically by GitLab commit #{$commit->getSha1()}"
                );
                return;
            }
            $first_closed_value = $this->status_value_retriever->getFirstClosedValueUserCanRead($artifact->getTracker(), $tracker_workflow_user);
            $fields_data        = [
                $status_field->getId() => $status_field->getFieldData($first_closed_value->getLabel())
            ];

            $new_followups = $artifact->createNewChangeset(
                $fields_data,
                PostPushTuleapArtifactCommentBuilder::buildComment(
                    $this->getTuleapUserNameFromGitlabCommitter($commit),
                    $commit,
                    $tuleap_reference,
                    $gitlab_repository_integration,
                    $artifact
                ),
                $tracker_workflow_user
            );

            if ($new_followups === null) {
                $this->logger->error("|  |  |_ No new comment was created");
            }
        } catch (Tracker_NoChangeException | Tracker_Exception $e) {
            $this->logger->error("|  |  |_ An error occurred during the creation of the comment");
        } catch (SemanticStatusClosedValueNotFoundException $e) {
            $this->addTuleapArtifactCommentNoSemanticDefined($artifact, $tracker_workflow_user, $commit);
        }
    }

    public function addTuleapArtifactCommentNoSemanticDefined(
        Artifact $artifact,
        PFUser $tracker_workflow_user,
        PostPushCommitWebhookData $commit
    ): void {
        try {
            $committer           = $this->getTuleapUserNameFromGitlabCommitter($commit);
            $no_semantic_comment = "$committer attempts to close this artifact from GitLab but no status semantic defined.";

            $new_followups = $artifact->createNewChangeset([], $no_semantic_comment, $tracker_workflow_user);

            if ($new_followups === null) {
                $this->logger->error("|  |  |_ No new comment was created");
            }
        } catch (Tracker_NoChangeException | Tracker_Exception $e) {
            $this->logger->error("|  |  |_ An error occurred during the creation of the comment");
        }
    }

    private function getTuleapUserNameFromGitlabCommitter(PostPushCommitWebhookData $commit): string
    {
        $tuleap_user = $this->user_manager->getUserByEmail($commit->getAuthorEmail());

        if (! $tuleap_user) {
            $committer = $commit->getAuthorName();
        } else {
            $committer = '@' . $tuleap_user->getUserName();
        }

        return $committer;
    }
}
