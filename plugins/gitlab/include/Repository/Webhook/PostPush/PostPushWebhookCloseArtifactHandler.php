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
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\Artifact\ArtifactNotFoundException;
use Tuleap\Gitlab\Artifact\ArtifactRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectDao;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Workflow\NoPossibleValueException;
use UserManager;
use UserNotExistException;

class PostPushWebhookCloseArtifactHandler
{
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
    /**
     * @var GitlabRepositoryProjectDao
     */
    private $repository_project_dao;
    /**
     * @var PostPushCommitArtifactUpdater
     */
    private $artifact_updater;

    private CredentialsRetriever $credentials_retriever;
    private GitlabProjectBuilder $gitlab_project_builder;

    public function __construct(
        PostPushCommitArtifactUpdater $artifact_updater,
        ArtifactRetriever $artifact_retriever,
        UserManager $user_manager,
        Tracker_Semantic_StatusFactory $semantic_status_factory,
        GitlabRepositoryProjectDao $repository_project_dao,
        CredentialsRetriever $credentials_retriever,
        GitlabProjectBuilder $gitlab_project_builder,
        LoggerInterface $logger,
    ) {
        $this->artifact_updater        = $artifact_updater;
        $this->artifact_retriever      = $artifact_retriever;
        $this->user_manager            = $user_manager;
        $this->semantic_status_factory = $semantic_status_factory;
        $this->repository_project_dao  = $repository_project_dao;
        $this->credentials_retriever   = $credentials_retriever;
        $this->gitlab_project_builder  = $gitlab_project_builder;
        $this->logger                  = $logger;
    }

    /**
     * @throws UserNotExistException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     */
    public function handleArtifactClosure(
        WebhookTuleapReference $tuleap_reference,
        PostPushCommitWebhookData $post_push_commit_webhook_data,
        GitlabRepositoryIntegration $gitlab_repository_integration,
    ): void {
        if ($tuleap_reference->getCloseArtifactKeyword() === null) {
            return;
        }

        try {
            $artifact = $this->artifact_retriever->retrieveArtifactById($tuleap_reference);

            $action_enabled_for_repository_in_project = $this->repository_project_dao->isArtifactClosureActionEnabledForRepositoryInProject(
                $gitlab_repository_integration->getId(),
                (int) $artifact->getTracker()->getGroupId()
            );

            if (! $action_enabled_for_repository_in_project) {
                $this->logger->warning(
                    "Artifact #{$tuleap_reference->getId()} cannot be closed. " .
                    "Either this artifact is not in a project where the GitLab repository is integrated in " .
                    "or the artifact closure action is not enabled. " .
                    "Skipping."
                );
                return;
            }

            $tracker_workflow_user = $this->user_manager->getUserById(Tracker_Workflow_WorkflowUser::ID);
            if (! $tracker_workflow_user) {
                throw new UserNotExistException("Tracker Workflow Manager does not exists, the comment cannot be added");
            }

            $credentials = $this->credentials_retriever->getCredentials(
                $gitlab_repository_integration
            );

            if ($credentials === null) {
                $this->logger->warning(
                    "Artifact #{$tuleap_reference->getId()} cannot be closed because no token found for integration. Skipping."
                );
                return;
            }

            $gitlab_project = $this->gitlab_project_builder->getProjectFromGitlabAPI(
                $credentials,
                $gitlab_repository_integration->getGitlabRepositoryId()
            );

            if ($gitlab_project->getDefaultBranch() !== $post_push_commit_webhook_data->getBranchName()) {
                return;
            }

            $committer_username   = $this->getUserClosingTheArtifactFromGitlabWebhook($post_push_commit_webhook_data);
            $closing_comment_body = PostPushTuleapArtifactCommentBuilder::buildComment(
                $committer_username->getName(),
                $post_push_commit_webhook_data,
                $tuleap_reference,
                $gitlab_repository_integration,
                $artifact
            );

            $no_semantic_comment = NewComment::fromParts(
                sprintf(
                    '%s attempts to close this artifact from GitLab but neither done nor status semantic defined.',
                    $committer_username->getName()
                ),
                CommentFormatIdentifier::buildCommonMark(),
                $tracker_workflow_user,
                (new \DateTimeImmutable())->getTimestamp(),
                []
            );

            $status_semantic = $this->semantic_status_factory->getByTracker($artifact->getTracker());

            $this->artifact_updater->closeTuleapArtifact(
                $artifact,
                $tracker_workflow_user,
                $post_push_commit_webhook_data,
                $status_semantic,
                $closing_comment_body,
                $no_semantic_comment,
            );
        } catch (ArtifactNotFoundException $e) {
            $this->logger->error("Artifact #{$tuleap_reference->getId()} not found");
        } catch (NoPossibleValueException $e) {
            $this->logger->error("Artifact #{$tuleap_reference->getId()} cannot be closed. " . $e->getMessage());
        }
    }

    private function getUserClosingTheArtifactFromGitlabWebhook(
        PostPushCommitWebhookData $commit,
    ): UserClosingTheArtifact {
        $tuleap_user = $this->user_manager->getUserByEmail($commit->getAuthorEmail());

        if (! $tuleap_user) {
            return UserClosingTheArtifact::fromUsername($commit->getAuthorName());
        }
        return UserClosingTheArtifact::fromUser($tuleap_user);
    }
}
