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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use CrossReference;
use Event;
use EventManager;
use Project;
use Psr\Log\LoggerInterface;
use ReferenceManager;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReference;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferencesParser;

class PostPushWebhookActionProcessor
{
    /**
     * @var CommitTuleapReferencesParser
     */
    private $commit_tuleap_references_parser;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GitlabRepositoryProjectRetriever
     */
    private $gitlab_repository_project_retriever;

    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        CommitTuleapReferencesParser $commit_tuleap_references_parser,
        GitlabRepositoryProjectRetriever $gitlab_repository_project_retriever,
        ReferenceManager $reference_manager,
        EventManager $event_manager,
        LoggerInterface $logger
    ) {
        $this->commit_tuleap_references_parser     = $commit_tuleap_references_parser;
        $this->gitlab_repository_project_retriever = $gitlab_repository_project_retriever;
        $this->reference_manager                   = $reference_manager;
        $this->event_manager                       = $event_manager;
        $this->logger                              = $logger;
    }

    public function process(GitlabRepository $gitlab_repository, PostPushWebhookData $webhook_data): void
    {
        foreach ($webhook_data->getCommits() as $commit_webhook_data) {
            $this->parseCommitReferences($gitlab_repository, $commit_webhook_data);
        }
    }

    private function parseCommitReferences(
        GitlabRepository $gitlab_repository,
        PostPushCommitWebhookData $commit_webhook_data
    ): void {
        $references_collection = $this->commit_tuleap_references_parser->extractCollectionOfTuleapReferences(
            $commit_webhook_data
        );

        $projects = $this->gitlab_repository_project_retriever->getProjectsGitlabRepositoryIsIntegratedIn(
            $gitlab_repository
        );

        $this->logger->info(count($references_collection->getTuleapReferences()) . " Tuleap references found in commit " . $commit_webhook_data->getSha1());
        foreach ($references_collection->getTuleapReferences() as $tuleap_reference) {
            $this->logger->info("Reference to Tuleap artifact #" . $tuleap_reference->getId() . " found.");
            $this->saveReferenceInEachIntegratedProject(
                $gitlab_repository,
                $tuleap_reference,
                $commit_webhook_data,
                $projects
            );
        }
    }

    /**
     * @param Project[] $projects
     */
    private function saveReferenceInEachIntegratedProject(
        GitlabRepository $gitlab_repository,
        CommitTuleapReference $tuleap_reference,
        PostPushCommitWebhookData $commit_webhook_data,
        array $projects
    ): void {
        $artifact_project_id = null;
        $this->event_manager->processEvent(
            Event::GET_ARTIFACT_REFERENCE_GROUP_ID,
            [
                'artifact_id' => $tuleap_reference->getId(),
                'group_id'    => &$artifact_project_id
            ]
        );

        if ($artifact_project_id === null) {
            $this->logger->error(
                "Tuleap artifact #" . $tuleap_reference->getId() . " not found, no cross-reference will be added."
            );
            return;
        }

        $this->logger->info(
            "Tuleap artifact #" . $tuleap_reference->getId() . " found, cross-reference will be added for each project the GitLab repository is integrated in."
        );

        $external_reference = $this->reference_manager->loadReferenceFromKeyword(
            'art',
            $tuleap_reference->getId()
        );

        if (! $external_reference) {
            $this->logger->error(
                "No reference found with the keyword 'art', and this must not happen. If you read this, this is really bad."
            );
            return;
        }

        assert($external_reference instanceof \Reference);

        foreach ($projects as $project) {
            $cross_reference = new CrossReference(
                $gitlab_repository->getName() . '/' . $commit_webhook_data->getSha1(),
                $project->getID(),
                'plugin_gitlab_commit',
                'gitlab_commit',
                $tuleap_reference->getId(),
                $artifact_project_id,
                $external_reference->getNature(),
                $external_reference->getKeyword(),
                0
            );

            $this->reference_manager->insertCrossReference($cross_reference);
        }
    }
}
