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

use ColinODell\PsrTestLogger\TestLogger;
use DateTimeImmutable;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraCloudChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\Comment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraCloudComment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\ActiveJiraCloudUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;

#[DisableReturnValueGenerationForTestDoubles]
final class IssueSnapshotCollectionBuilderTest extends TestCase
{
    private TestLogger $logger;
    private IssueSnapshotCollectionBuilder $builder;
    private ChangelogEntriesBuilder&MockObject $changelog_entries_builder;
    private InitialSnapshotBuilder&MockObject $initial_snapshot_builder;
    private ChangelogSnapshotBuilder&MockObject $changelog_snapshot_builder;
    private PFUser $user;
    private IssueAPIRepresentation $jira_issue_api;
    private FieldMappingCollection $jira_field_mapping_collection;
    private CurrentSnapshotBuilder&MockObject $current_snapshot_builder;
    private string $jira_base_url;
    private CommentValuesBuilder&MockObject $comment_values_builder;
    private AttachmentCollection $attachment_collection;
    private JiraUserRetriever&MockObject $jira_user_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->changelog_entries_builder  = $this->createMock(ChangelogEntriesBuilder::class);
        $this->current_snapshot_builder   = $this->createMock(CurrentSnapshotBuilder::class);
        $this->initial_snapshot_builder   = $this->createMock(InitialSnapshotBuilder::class);
        $this->changelog_snapshot_builder = $this->createMock(ChangelogSnapshotBuilder::class);
        $this->comment_values_builder     = $this->createMock(CommentValuesBuilder::class);
        $this->logger                     = new TestLogger();
        $this->jira_user_retriever        = $this->createMock(JiraUserRetriever::class);

        $this->builder = new IssueSnapshotCollectionBuilder(
            $this->changelog_entries_builder,
            $this->current_snapshot_builder,
            $this->initial_snapshot_builder,
            $this->changelog_snapshot_builder,
            $this->comment_values_builder,
            $this->logger,
            $this->jira_user_retriever
        );

        $this->user           = UserTestBuilder::buildWithDefaults();
        $this->jira_issue_api = new IssueAPIRepresentation(
            'key01',
            10001,
            [],
            []
        );

        $this->jira_field_mapping_collection = new FieldMappingCollection();
        $this->jira_base_url                 = 'URL';
        $this->attachment_collection         = new AttachmentCollection([]);
    }

    public function testItBuildsACollectionOfSnapshotsForIssueOrderedByTimestamp(): void
    {
        $this->jira_user_retriever->method('retrieveUserFromAPIData')->willReturn($this->user);
        $this->jira_user_retriever->method('retrieveJiraAuthor')->willReturn($this->user);

        $this->changelog_entries_builder->method('buildEntriesCollectionForIssue')
            ->with('key01')->willReturn($this->buildChangelogEntriesCollection());

        $this->current_snapshot_builder->expects($this->once())->method('buildCurrentSnapshot')
            ->willReturn($this->buildCurrentSnapshot($this->user));

        $this->initial_snapshot_builder->expects($this->once())->method('buildInitialSnapshot')
            ->willReturn($this->buildInitialSnapshot($this->user));

        $this->changelog_snapshot_builder->method('buildSnapshotFromChangelogEntry')->willReturnOnConsecutiveCalls(
            $this->buildFirstChangelogSnapshot($this->user),
            $this->buildSecondChangelogSnapshot($this->user)
        );

        $this->comment_values_builder->method('buildCommentCollectionForIssue')
            ->willReturn([$this->buildCommentSnapshot()]);

        $collection = $this->builder->buildCollectionOfSnapshotsForIssue(
            $this->jira_issue_api,
            $this->attachment_collection,
            $this->jira_field_mapping_collection,
            new LinkedIssuesCollection(),
            $this->jira_base_url
        );

        self::assertCount(4, $collection);
        self::assertSame(
            [
                1585141750,
                1585141810,
                1585141870,
                1585141930,
            ],
            array_map(static fn(Snapshot $snapshot) => $snapshot->getDate()->getTimestamp(), $collection),
        );
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItSkipsInCollectionSnapshotsWithoutChangedFileds(): void
    {
        $this->jira_user_retriever->method('retrieveUserFromAPIData')->willReturn($this->user);

        $this->changelog_entries_builder->method('buildEntriesCollectionForIssue')
            ->with('key01')->willReturn($this->buildChangelogEntriesCollection());

        $this->initial_snapshot_builder->expects($this->once())->method('buildInitialSnapshot')
            ->willReturn($this->buildInitialSnapshot($this->user));

        $this->current_snapshot_builder->expects($this->once())->method('buildCurrentSnapshot')
            ->willReturn($this->buildCurrentSnapshotInEmptyTestCase($this->user));

        $this->changelog_snapshot_builder->method('buildSnapshotFromChangelogEntry')->willReturnOnConsecutiveCalls(
            $this->buildFirstChangelogSnapshot($this->user),
            $this->buildEmptySecondChangelogSnapshot($this->user)
        );

        $this->comment_values_builder->method('buildCommentCollectionForIssue')->willReturn([]);

        $collection = $this->builder->buildCollectionOfSnapshotsForIssue(
            $this->jira_issue_api,
            $this->attachment_collection,
            $this->jira_field_mapping_collection,
            new LinkedIssuesCollection(),
            $this->jira_base_url
        );

        self::assertCount(2, $collection);
        self::assertSame(
            [
                1585141750,
                1585141810,
            ],
            array_map(static fn(Snapshot $snapshot) => $snapshot->getDate()->getTimestamp(), $collection),
        );
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItBuildCollectionOfSnapshotWithFieldAndCommentIfSameTimestamp(): void
    {
        $this->jira_user_retriever->method('retrieveUserFromAPIData')->willReturn($this->user);

        $this->changelog_entries_builder->method('buildEntriesCollectionForIssue')
            ->with('key01')->willReturn($this->buildChangelogEntriesCollection());

        $this->current_snapshot_builder->expects($this->once())->method('buildCurrentSnapshot')
            ->willReturn($this->buildCurrentSnapshot($this->user));

        $this->initial_snapshot_builder->expects($this->once())->method('buildInitialSnapshot')
            ->willReturn($this->buildInitialSnapshot($this->user));

        $this->changelog_snapshot_builder->method('buildSnapshotFromChangelogEntry')->willReturnOnConsecutiveCalls(
            $this->buildFirstChangelogSnapshot($this->user),
            $this->buildSecondChangelogSnapshot($this->user)
        );

        $this->comment_values_builder->method('buildCommentCollectionForIssue')
            ->willReturn([$this->buildCommentSnapshotWithSameTimestampOfFirstChangelog()]);

        $this->jira_user_retriever->method('retrieveJiraAuthor')->willReturn($this->user);

        $collection = $this->builder->buildCollectionOfSnapshotsForIssue(
            $this->jira_issue_api,
            $this->attachment_collection,
            $this->jira_field_mapping_collection,
            new LinkedIssuesCollection(),
            $this->jira_base_url
        );

        self::assertCount(3, $collection);
        self::assertSame(
            [
                1585141750,
                1585141810,
                1585141870,
            ],
            array_map(static fn(Snapshot $snapshot) => $snapshot->getDate()->getTimestamp(), $collection),
        );

        $snapshot_1585141810 = array_values(array_filter($collection, fn(Snapshot $snapshot) => $snapshot->getDate()->getTimestamp() === 1585141810));
        self::assertCount(1, $snapshot_1585141810);

        self::assertEquals($this->buildCommentSnapshotWithSameTimestampOfFirstChangelog(), $snapshot_1585141810[0]->getCommentSnapshot());
        self::assertEquals($this->buildFirstChangelogSnapshot($this->user)->getAllFieldsSnapshot(), $snapshot_1585141810[0]->getAllFieldsSnapshot());
        self::assertTrue($this->logger->hasDebugRecords());
    }

    private function buildInitialSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable('2020-03-25T14:09:10.823+0100'),
            [
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'description',
                        'Description',
                        null,
                        'Fdescription',
                        'description',
                        'text'
                    ),
                    'aaaaaaaa',
                    'aaaaaaaa'
                ),
            ],
            null
        );
    }

    private function buildCurrentSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable('2020-03-25T14:11:10.823+0100'),
            [
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'description',
                        'Description',
                        null,
                        'Fdescription',
                        'description',
                        'text',
                    ),
                    'aaaaaaaa',
                    'aaaaaaaa'
                ),
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'customfield_10036',
                        'Field 01',
                        null,
                        'Fcustomfield_10036',
                        'customfield_10036',
                        'float',
                    ),
                    '11',
                    null
                ),
            ],
            null
        );
    }

    private function buildCurrentSnapshotInEmptyTestCase($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable('2020-03-25T14:11:10.823+0100'),
            [
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'description',
                        'Description',
                        null,
                        'Fdescription',
                        'description',
                        'text',
                    ),
                    'aaaaaaaa',
                    'aaaaaaaa'
                ),
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'customfield_10036',
                        'Field 01',
                        null,
                        'Fcustomfield_10036',
                        'customfield_10036',
                        'float',
                    ),
                    '9',
                    null
                ),
            ],
            null
        );
    }

    private function buildFirstChangelogSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable('2020-03-25T14:10:10.823+0100'),
            [
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'customfield_10036',
                        'Field 01',
                        null,
                        'Fcustomfield_10036',
                        'customfield_10036',
                        'float',
                    ),
                    '9',
                    null
                ),
            ],
            null
        );
    }

    private function buildSecondChangelogSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable('2020-03-25T14:11:10.823+0100'),
            [
                new FieldSnapshot(
                    new ScalarFieldMapping(
                        'customfield_10036',
                        'Field 01',
                        null,
                        'Fcustomfield_10036',
                        'customfield_10036',
                        'float',
                    ),
                    '11',
                    null
                ),
            ],
            null
        );
    }

    private function buildEmptySecondChangelogSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable('2020-03-25T14:11:10.823+0100'),
            [],
            null
        );
    }

    private function buildCommentSnapshot(): Comment
    {
        return new JiraCloudComment(
            new ActiveJiraCloudUser([
                'displayName' => 'userO1',
                'accountId'   => 'e12ds5123sw',
            ]),
            new DateTimeImmutable('2020-03-25T14:12:10.823+0100'),
            'Comment 01'
        );
    }

    private function buildCommentSnapshotWithSameTimestampOfFirstChangelog(): Comment
    {
        return new JiraCloudComment(
            new ActiveJiraCloudUser([
                'displayName' => 'userO1',
                'accountId'   => 'e12ds5123sw',
            ]),
            new DateTimeImmutable('2020-03-25T14:10:10.823+0100'),
            'Comment 01'
        );
    }

    private function buildChangelogEntriesCollection(): array
    {
        return [
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    'id'      => '100',
                    'created' => '2020-03-25T14:10:10.823+0100',
                    'items'   => [
                        0 => [
                            'fieldId'    => 'customfield_10036',
                            'from'       => null,
                            'fromString' => null,
                            'to'         => null,
                            'toString'   => '9',
                        ],
                    ],
                    'author'  => [
                        'accountId'    => 'e8a7dbae5',
                        'displayName'  => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
            JiraCloudChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    'id'      => '101',
                    'created' => '2020-03-25T14:11:10.823+0100',
                    'items'   => [
                        0 => [
                            'fieldId'    => 'customfield_10036',
                            'from'       => null,
                            'fromString' => '9',
                            'to'         => null,
                            'toString'   => '11',
                        ],
                    ],
                    'author'  => [
                        'accountId'    => 'e8a7dbae5',
                        'displayName'  => 'John Doe',
                        'emailAddress' => 'john.doe@example.com',
                    ],
                ]
            ),
        ];
    }
}
