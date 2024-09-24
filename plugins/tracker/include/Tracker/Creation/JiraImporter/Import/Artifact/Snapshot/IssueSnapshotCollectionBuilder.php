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
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

readonly class IssueSnapshotCollectionBuilder
{
    public function __construct(
        private ChangelogEntriesBuilder $changelog_entries_builder,
        private CurrentSnapshotBuilder $current_snapshot_builder,
        private InitialSnapshotBuilder $initial_snapshot_builder,
        private ChangelogSnapshotBuilder $changelog_snapshot_builder,
        private CommentValuesBuilder $comment_values_builder,
        private LoggerInterface $logger,
        private JiraUserRetriever $jira_user_retriever,
    ) {
    }

    /**
     * @return Snapshot[]
     * @throws JiraConnectionException
     */
    public function buildCollectionOfSnapshotsForIssue(
        IssueAPIRepresentation $issue_api_representation,
        AttachmentCollection $attachment_collection,
        FieldMappingCollection $jira_field_mapping_collection,
        LinkedIssuesCollection $linked_issues_collection,
        string $jira_base_url,
    ): array {
        $this->logger->debug('Start build collection of snapshot ...');

        $jira_issue_key   = $issue_api_representation->getKey();
        $artifact_creator = $this->jira_user_retriever->retrieveUserFromAPIData(
            $issue_api_representation->getFieldByKey('creator')
        );

        $snapshots_collection = new SnapshotCollection($this->logger);
        $changelog_entries    = $this->changelog_entries_builder->buildEntriesCollectionForIssue($jira_issue_key);

        $current_snapshot = $this->current_snapshot_builder->buildCurrentSnapshot(
            $artifact_creator,
            $issue_api_representation,
            $jira_field_mapping_collection,
            $linked_issues_collection
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

        $snapshots_collection->setInitialSnapshot($initial_snapshot);

        foreach ($changelog_entries as $changelog_entry) {
            $changelog_snapshot = $this->changelog_snapshot_builder->buildSnapshotFromChangelogEntry(
                $current_snapshot,
                $changelog_entry,
                $attachment_collection,
                $jira_field_mapping_collection
            );

            if (count($changelog_snapshot->getAllFieldsSnapshot()) > 0) {
                $snapshots_collection->appendChangelogSnapshot($changelog_snapshot);
            }
        }

        $comments_collection = $this->comment_values_builder->buildCommentCollectionForIssue($jira_issue_key);
        foreach ($comments_collection as $comment) {
            $snapshots_collection->addComment(
                $this->jira_user_retriever->retrieveJiraAuthor($comment->getUpdateAuthor()),
                $comment
            );
        }

        $this->logger->debug('End build collection of snapshot');

        return $snapshots_collection->toArray();
    }
}
