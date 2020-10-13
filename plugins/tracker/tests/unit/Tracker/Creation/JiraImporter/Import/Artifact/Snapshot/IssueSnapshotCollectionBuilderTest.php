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

use DateTimeImmutable;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\Comment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraAuthorRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class IssueSnapshotCollectionBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var IssueSnapshotCollectionBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ChangelogEntriesBuilder
     */
    private $changelog_entries_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InitialSnapshotBuilder
     */
    private $initial_snapshot_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ChangelogSnapshotBuilder
     */
    private $changelog_snapshot_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var string[]
     */
    private $jira_issue_api;

    /**
     * @var FieldMappingCollection
     */
    private $jira_field_mapping_collection;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CurrentSnapshotBuilder
     */
    private $current_snapshot_builder;

    /**
     * @var string
     */
    private $jira_base_url;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CommentValuesBuilder
     */
    private $comment_values_builder;

    /**
     * @var array
     */
    private $attachment_collection;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraAuthorRetriever
     */
    private $jira_author_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->changelog_entries_builder  = Mockery::mock(ChangelogEntriesBuilder::class);
        $this->current_snapshot_builder   = Mockery::mock(CurrentSnapshotBuilder::class);
        $this->initial_snapshot_builder   = Mockery::mock(InitialSnapshotBuilder::class);
        $this->changelog_snapshot_builder = Mockery::mock(ChangelogSnapshotBuilder::class);
        $this->comment_values_builder     = Mockery::mock(CommentValuesBuilder::class);
        $this->logger                     = Mockery::mock(LoggerInterface::class);
        $this->jira_author_retriever      = Mockery::mock(JiraAuthorRetriever::class);

        $this->builder = new IssueSnapshotCollectionBuilder(
            $this->changelog_entries_builder,
            $this->current_snapshot_builder,
            $this->initial_snapshot_builder,
            $this->changelog_snapshot_builder,
            $this->comment_values_builder,
            $this->logger,
            $this->jira_author_retriever
        );

        $this->user           = Mockery::mock(PFUser::class);
        $this->jira_issue_api = new IssueAPIRepresentation(
            "key01",
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
        $this->logger->shouldReceive('debug');
        $this->jira_author_retriever->shouldReceive('retrieveArtifactSubmitter')->andReturn(
            $this->user
        );
        $this->jira_author_retriever->shouldReceive('retrieveJiraAuthor')->andReturn($this->user);

        $this->changelog_entries_builder->shouldReceive('buildEntriesCollectionForIssue')
            ->with('key01')
            ->andReturn(
                $this->buildChangelogEntriesCollection()
            );

        $this->current_snapshot_builder->shouldReceive('buildCurrentSnapshot')
            ->once()
            ->andReturn(
                $this->buildCurrentSnapshot($this->user)
            );

        $this->initial_snapshot_builder->shouldReceive('buildInitialSnapshot')
            ->once()
            ->andReturn(
                $this->buildInitialSnapshot($this->user)
            );

        $this->changelog_snapshot_builder->shouldReceive('buildSnapshotFromChangelogEntry')->andReturn(
            $this->buildFirstChangelogSnapshot($this->user),
            $this->buildSecondChangelogSnapshot($this->user)
        );

        $this->comment_values_builder->shouldReceive('buildCommentCollectionForIssue')->andReturn(
            [
                $this->buildCommentSnapshot()
            ]
        );

        $collection = $this->builder->buildCollectionOfSnapshotsForIssue(
            $this->jira_issue_api,
            $this->attachment_collection,
            $this->jira_field_mapping_collection,
            $this->jira_base_url
        );

        $this->assertCount(4, $collection);
        $this->assertSame(
            [
                1585141750,
                1585141810,
                1585141870,
                1585141930
            ],
            array_keys($collection)
        );
    }

    public function testItSkipsInCollectionSnapshotsWithoutChangedFileds(): void
    {
        $this->logger->shouldReceive('debug');
        $this->jira_author_retriever->shouldReceive('retrieveArtifactSubmitter')->andReturn(
            $this->user
        );
        $this->jira_author_retriever->shouldReceive('retrieveJiraAuthor')->andReturn($this->user);

        $this->changelog_entries_builder->shouldReceive('buildEntriesCollectionForIssue')
            ->with('key01')
            ->andReturn(
                $this->buildChangelogEntriesCollection()
            );

        $this->initial_snapshot_builder->shouldReceive('buildInitialSnapshot')
            ->once()
            ->andReturn(
                $this->buildInitialSnapshot($this->user)
            );

        $this->current_snapshot_builder->shouldReceive('buildCurrentSnapshot')
            ->once()
            ->andReturn(
                $this->buildCurrentSnapshotInEmptyTestCase($this->user)
            );

        $this->changelog_snapshot_builder->shouldReceive('buildSnapshotFromChangelogEntry')->andReturn(
            $this->buildFirstChangelogSnapshot($this->user),
            $this->buildEmptySecondChangelogSnapshot($this->user)
        );

        $this->comment_values_builder->shouldReceive('buildCommentCollectionForIssue')->andReturn([]);

        $collection = $this->builder->buildCollectionOfSnapshotsForIssue(
            $this->jira_issue_api,
            $this->attachment_collection,
            $this->jira_field_mapping_collection,
            $this->jira_base_url
        );

        $this->assertCount(2, $collection);
        $this->assertSame(
            [
                1585141750,
                1585141810
            ],
            array_keys($collection)
        );
    }

    public function testItBuildCollectionOfSnapshotWithFieldAndCommentIfSameTimestamp(): void
    {
        $this->logger->shouldReceive('debug');
        $this->jira_author_retriever->shouldReceive('retrieveArtifactSubmitter')->andReturn(
            $this->user
        );
        $this->jira_author_retriever->shouldReceive('retrieveJiraAuthor')->andReturn($this->user);

        $this->changelog_entries_builder->shouldReceive('buildEntriesCollectionForIssue')
            ->with('key01')
            ->andReturn(
                $this->buildChangelogEntriesCollection()
            );

        $this->current_snapshot_builder->shouldReceive('buildCurrentSnapshot')
            ->once()
            ->andReturn(
                $this->buildCurrentSnapshot($this->user)
            );

        $this->initial_snapshot_builder->shouldReceive('buildInitialSnapshot')
            ->once()
            ->andReturn(
                $this->buildInitialSnapshot($this->user)
            );

        $this->changelog_snapshot_builder->shouldReceive('buildSnapshotFromChangelogEntry')->andReturn(
            $this->buildFirstChangelogSnapshot($this->user),
            $this->buildSecondChangelogSnapshot($this->user)
        );

        $this->comment_values_builder->shouldReceive('buildCommentCollectionForIssue')->andReturn(
            [
                $this->buildCommentSnapshotWithSameTimestampOfInitialChangelog()
            ]
        );

        $collection = $this->builder->buildCollectionOfSnapshotsForIssue(
            $this->jira_issue_api,
            $this->attachment_collection,
            $this->jira_field_mapping_collection,
            $this->jira_base_url
        );

        $this->assertCount(3, $collection);
        $this->assertSame(
            [
                1585141750,
                1585141810,
                1585141870
            ],
            array_keys($collection)
        );
        $this->assertEquals($this->buildCommentSnapshotWithSameTimestampOfInitialChangelog(), $collection[1585141750]->getCommentSnapshot());
        $this->assertEquals($this->buildInitialSnapshot($this->user)->getAllFieldsSnapshot(), $collection[1585141750]->getAllFieldsSnapshot());
    }

    private function buildInitialSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable("2020-03-25T14:09:10.823+0100"),
            [
                new FieldSnapshot(
                    new FieldMapping(
                        "description",
                        "Fdescription",
                        "Description",
                        "text",
                        null
                    ),
                    'aaaaaaaa',
                    'aaaaaaaa'
                )
            ],
            null
        );
    }

    private function buildCurrentSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable("2020-03-25T14:11:10.823+0100"),
            [
                new FieldSnapshot(
                    new FieldMapping(
                        "description",
                        "Fdescription",
                        "Description",
                        "text",
                        null
                    ),
                    'aaaaaaaa',
                    'aaaaaaaa'
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        "customfield_10036",
                        "Fcustomfield_10036",
                        "Field 01",
                        "float",
                        null
                    ),
                    '11',
                    null
                )
            ],
            null
        );
    }

    private function buildCurrentSnapshotInEmptyTestCase($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable("2020-03-25T14:11:10.823+0100"),
            [
                new FieldSnapshot(
                    new FieldMapping(
                        "description",
                        "Fdescription",
                        "Description",
                        "text",
                        null
                    ),
                    'aaaaaaaa',
                    'aaaaaaaa'
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        "customfield_10036",
                        "Fcustomfield_10036",
                        "Field 01",
                        "float",
                        null
                    ),
                    '9',
                    null
                )
            ],
            null
        );
    }

    private function buildFirstChangelogSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable("2020-03-25T14:10:10.823+0100"),
            [
                new FieldSnapshot(
                    new FieldMapping(
                        "customfield_10036",
                        "Fcustomfield_10036",
                        "Field 01",
                        "float",
                        null
                    ),
                    '9',
                    null
                )
            ],
            null
        );
    }

    private function buildSecondChangelogSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable("2020-03-25T14:11:10.823+0100"),
            [
                new FieldSnapshot(
                    new FieldMapping(
                        "customfield_10036",
                        "Fcustomfield_10036",
                        "Field 01",
                        "float",
                        null
                    ),
                    '11',
                    null
                )
            ],
            null
        );
    }

    private function buildEmptySecondChangelogSnapshot($user): Snapshot
    {
        return new Snapshot(
            $user,
            new DateTimeImmutable("2020-03-25T14:11:10.823+0100"),
            [],
            null
        );
    }

    private function buildCommentSnapshot(): Comment
    {
        return new Comment(
            new JiraUser([
                'displayName' => 'userO1',
                'accountId' => 'e12ds5123sw'
            ]),
            new DateTimeImmutable("2020-03-25T14:12:10.823+0100"),
            "Comment 01"
        );
    }

    private function buildCommentSnapshotWithSameTimestampOfInitialChangelog(): Comment
    {
        return new Comment(
            new JiraUser([
                'displayName' => 'userO1',
                'accountId' => 'e12ds5123sw'
            ]),
            new DateTimeImmutable("2020-03-25T14:09:10.823+0100"),
            "Comment 01"
        );
    }

    private function buildChangelogEntriesCollection(): array
    {
        return [
            ChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "100",
                    "created" => "2020-03-25T14:10:10.823+0100",
                    "items" => [
                        0 => [
                            "fieldId" => "customfield_10036",
                            "from" => null,
                            "fromString" => null,
                            "to" => null,
                            "toString" => "9"
                        ]
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com'
                    ]
                ]
            ),
            ChangelogEntryValueRepresentation::buildFromAPIResponse(
                [
                    "id" => "101",
                    "created" => "2020-03-25T14:11:10.823+0100",
                    "items" => [
                        0 => [
                            "fieldId" => "customfield_10036",
                            "from" => null,
                            "fromString" => "9",
                            "to" => null,
                            "toString" => "11"
                        ]
                    ],
                    'author' => [
                        'accountId' => 'e8a7dbae5',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'john.doe@example.com'
                    ]
                ]
            )
        ];
    }
}
