<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraAuthorRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class IssueSnapshotCollectionBuilder
{
    /**
     * @var InitialSnapshotBuilder
     */
    private $initial_snapshot_builder;

    /**
     * @var ChangelogEntriesBuilder
     */
    private $changelog_entries_builder;

    /**
     * @var ChangelogSnapshotBuilder
     */
    private $changelog_snapshot_builder;

    /**
     * @var CurrentSnapshotBuilder
     */
    private $current_snapshot_builder;

    /**
     * @var CommentValuesBuilder
     */
    private $comment_values_builder;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JiraAuthorRetriever
     */
    private $jira_author_retriever;

    public function __construct(
        ChangelogEntriesBuilder $changelog_entries_builder,
        CurrentSnapshotBuilder $current_snapshot_builder,
        InitialSnapshotBuilder $initial_snapshot_builder,
        ChangelogSnapshotBuilder $changelog_snapshot_builder,
        CommentValuesBuilder $comment_values_builder,
        LoggerInterface $logger,
        JiraAuthorRetriever $jira_author_retriever
    ) {
        $this->initial_snapshot_builder   = $initial_snapshot_builder;
        $this->changelog_entries_builder  = $changelog_entries_builder;
        $this->changelog_snapshot_builder = $changelog_snapshot_builder;
        $this->current_snapshot_builder   = $current_snapshot_builder;
        $this->comment_values_builder     = $comment_values_builder;
        $this->logger                     = $logger;
        $this->jira_author_retriever      = $jira_author_retriever;
    }

    /**
     * @return Snapshot[]
     * @throws JiraConnectionException
     */
    public function buildCollectionOfSnapshotsForIssue(
        IssueAPIRepresentation $issue_api_representation,
        AttachmentCollection $attachment_collection,
        FieldMappingCollection $jira_field_mapping_collection,
        string $jira_base_url
    ): array {
        $this->logger->debug("Start build collection of snapshot ...");

        $jira_issue_key   = $issue_api_representation->getKey();
        $artifact_creator = $this->jira_author_retriever->retrieveArtifactSubmitter($issue_api_representation);

        $snapshots_collection = [];
        $changelog_entries    = $this->changelog_entries_builder->buildEntriesCollectionForIssue($jira_issue_key);

        $current_snapshot = $this->current_snapshot_builder->buildCurrentSnapshot(
            $artifact_creator,
            $issue_api_representation,
            $jira_field_mapping_collection
        );

        $initial_snapshot = $this->initial_snapshot_builder->buildInitialSnapshot(
            $artifact_creator,
            $current_snapshot,
            $changelog_entries,
            $jira_field_mapping_collection,
            $issue_api_representation,
            $attachment_collection,
            $jira_base_url
        );

        $snapshots_collection[$initial_snapshot->getDate()->getTimestamp()] = $initial_snapshot;

        foreach ($changelog_entries as $changelog_entry) {
            $changelog_snapshot = $this->changelog_snapshot_builder->buildSnapshotFromChangelogEntry(
                $current_snapshot,
                $changelog_entry,
                $attachment_collection,
                $jira_field_mapping_collection
            );

            if (count($changelog_snapshot->getAllFieldsSnapshot()) > 0) {
                $snapshots_collection[$changelog_snapshot->getDate()->getTimestamp()] = $changelog_snapshot;
            }
        }

        $comments_collection = $this->comment_values_builder->buildCommentCollectionForIssue($jira_issue_key);
        foreach ($comments_collection as $comment) {
            if (isset($snapshots_collection[$comment->getDate()->getTimestamp()])) {
                $snapshots_collection[$comment->getDate()->getTimestamp()] = new Snapshot(
                    $snapshots_collection[$comment->getDate()->getTimestamp()]->getUser(),
                    $snapshots_collection[$comment->getDate()->getTimestamp()]->getDate(),
                    $snapshots_collection[$comment->getDate()->getTimestamp()]->getAllFieldsSnapshot(),
                    $comment
                );

                continue;
            }

            $commenter = $comment->getUpdateAuthor();

            $comment_snapshot = new Snapshot(
                $this->jira_author_retriever->retrieveJiraAuthor($commenter),
                $comment->getDate(),
                [],
                $comment
            );

            if ($comment_snapshot->getCommentSnapshot() !== null) {
                $snapshots_collection[$comment_snapshot->getDate()->getTimestamp()] = $comment_snapshot;
            }
        }

        ksort($snapshots_collection);

        $this->logger->debug("End build collection of snapshot");

        return $snapshots_collection;
    }
}
