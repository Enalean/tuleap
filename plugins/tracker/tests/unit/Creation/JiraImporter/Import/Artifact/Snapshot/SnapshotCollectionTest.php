<?php
/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SnapshotCollectionTest extends TestCase
{
    public function testItHasAnInitialSnapshot(): void
    {
        $snapshot = SnapshotTestBuilder::aSnapshot()->build();

        $snapshot_collection = new SnapshotCollection(new NullLogger());
        $snapshot_collection->setInitialSnapshot($snapshot);

        self::assertSame([$snapshot], $snapshot_collection->toArray());
    }

    public function testItHasAnInitialSnapshotAndOneEntryInChangeLog(): void
    {
        $initial_snapshot = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01')->build();
        $second_snapshot  = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-20 20:30:01')->build();

        $snapshot_collection = new SnapshotCollection(new NullLogger());
        $snapshot_collection->setInitialSnapshot($initial_snapshot);
        $snapshot_collection->appendChangelogSnapshot($second_snapshot);

        self::assertSame([$initial_snapshot, $second_snapshot], $snapshot_collection->toArray());
    }

    public function testItHasSeveralChangelogEntriesThatGetReordered(): void
    {
        $initial_snapshot = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01')->build();
        $changelog_1      = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-20 20:30:01')->build();
        $changelog_2      = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-22 20:30:01')->build();
        $changelog_3      = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-21 20:30:01')->build();
        $changelog_4      = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-21 14:00:00')->build();

        $snapshot_collection = new SnapshotCollection(new NullLogger());
        $snapshot_collection->setInitialSnapshot($initial_snapshot);
        $snapshot_collection->appendChangelogSnapshot($changelog_1);
        $snapshot_collection->appendChangelogSnapshot($changelog_2);
        $snapshot_collection->appendChangelogSnapshot($changelog_3);
        $snapshot_collection->appendChangelogSnapshot($changelog_4);

        self::assertSame(
            [
                $initial_snapshot,
                $changelog_1,
                $changelog_4,
                $changelog_3,
                $changelog_2,
            ],
            $snapshot_collection->toArray()
        );
    }

    public function testItHasSeveralChangelogEntriesThatHappensInTheSameSecond(): void
    {
        $snapshot_collection = new SnapshotCollection(new NullLogger());
        $snapshot_collection->setInitialSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('One')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01.302')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Two')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01.405')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Three')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01.802')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Four')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-21 20:30:02')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Five')->build())->build());

        self::assertEquals(
            [
                'One',
                'Two',
                'Three',
                'Four',
                'Five',
            ],
            array_map(fn (Snapshot $snapshot): ?string => $snapshot->getFieldInSnapshot('description')?->getValue(), $snapshot_collection->toArray()),
        );

        self::assertEquals(
            [
                '2024-09-19 20:30:01',
                '2024-09-19 20:30:02',
                '2024-09-19 20:30:03',
                '2024-09-19 20:30:04',
                '2024-09-21 20:30:02',
            ],
            array_map(fn (Snapshot $snapshot) => $snapshot->getDate()->format('Y-m-d H:i:s'), $snapshot_collection->toArray()),
        );
    }

    public function testItWarnsAdminsWhenChangelogEntriesAreYoungerThanInitialEntry(): void
    {
        $logger              = new TestLogger();
        $snapshot_collection = new SnapshotCollection($logger);
        $snapshot_collection->setInitialSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('One')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:00')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Two')->build())->build());

        self::assertTrue($logger->hasRecords(LogLevel::WARNING));
    }

    public function testItAddsCommentAsNewChangelogEntry(): void
    {
        $initial_snapshot = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01')->build();
        $second_snapshot  = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-20 20:30:01')->build();

        $snapshot_collection = new SnapshotCollection(new NullLogger());
        $snapshot_collection->setInitialSnapshot($initial_snapshot);
        $snapshot_collection->appendChangelogSnapshot($second_snapshot);
        $snapshot_collection->addComment(UserTestBuilder::aUser()->build(), CommentTestBuilder::aJiraCloudComment()->withDate('2024-09-20 20:35:00')->build());

        self::assertSame(
            [
                '2024-09-19 20:30:01',
                '2024-09-20 20:30:01',
                '2024-09-20 20:35:00',
            ],
            array_map(fn (Snapshot $snapshot) => $snapshot->getDate()->format('Y-m-d H:i:s'), $snapshot_collection->toArray()),
        );
    }

    public function testItDoesntAddCommentThatAreEmpty(): void
    {
        $initial_snapshot = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01')->build();
        $second_snapshot  = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-20 20:30:01')->build();

        $snapshot_collection = new SnapshotCollection(new NullLogger());
        $snapshot_collection->setInitialSnapshot($initial_snapshot);
        $snapshot_collection->appendChangelogSnapshot($second_snapshot);
        $snapshot_collection->addComment(UserTestBuilder::aUser()->build(), CommentTestBuilder::aJiraCloudComment()->withDate('2024-09-20 20:35:00')->withComment('')->build());

        self::assertSame(
            [
                '2024-09-19 20:30:01',
                '2024-09-20 20:30:01',
            ],
            array_map(fn (Snapshot $snapshot) => $snapshot->getDate()->format('Y-m-d H:i:s'), $snapshot_collection->toArray()),
        );
    }

    public function testItUpdatesExistingSnapshotWhenCommentTimeIsTheSame(): void
    {
        $initial_snapshot = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01')->build();
        $second_snapshot  = SnapshotTestBuilder::aSnapshot()->withDate('2024-09-20 20:30:01')->build();

        $snapshot_collection = new SnapshotCollection(new NullLogger());
        $snapshot_collection->setInitialSnapshot($initial_snapshot);
        $snapshot_collection->appendChangelogSnapshot($second_snapshot);
        $snapshot_collection->addComment(UserTestBuilder::aUser()->build(), CommentTestBuilder::aJiraCloudComment()->withDate('2024-09-20 20:30:01')->withComment('Foo')->build());

        self::assertSame(
            [
                '2024-09-19 20:30:01',
                '2024-09-20 20:30:01',
            ],
            array_map(fn (Snapshot $snapshot) => $snapshot->getDate()->format('Y-m-d H:i:s'), $snapshot_collection->toArray()),
        );
        self::assertSame(
            [
                null,
                'Foo',
            ],
            array_map(fn (Snapshot $snapshot) => $snapshot->getCommentSnapshot()?->getRenderedValue(), $snapshot_collection->toArray()),
        );
    }

    /**
     * I don't really know how the actual data would look like. AFAIK the comments and the changes are two different
     * things in Jira and I don't know if a comment could happen in the same time of a change. If it generates issues
     * like attaching a comment to an unrelated change, maybe the best option would be disable the feature of associating
     * comments to changes based on dates.
     */
    public function testCommentAtTheSameTimeOfAChangeThatWasInConflict(): void
    {
        $snapshot_collection = new SnapshotCollection(new NullLogger());
        $snapshot_collection->setInitialSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:30:01')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('One')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:40:01.302')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Two')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:40:01.405')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Three')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-19 20:40:01.802')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Four')->build())->build());
        $snapshot_collection->appendChangelogSnapshot(SnapshotTestBuilder::aSnapshot()->withDate('2024-09-21 20:30:02')->withSnapshot(FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('Five')->build())->build());

        $snapshot_collection->addComment(UserTestBuilder::aUser()->build(), CommentTestBuilder::aJiraCloudComment()->withDate('2024-09-19 20:40:01')->withComment('Foo')->build());

        self::assertSame(
            [
                null,
                'Foo',
                null,
                null,
                null,
            ],
            array_map(fn (Snapshot $snapshot) => $snapshot->getCommentSnapshot()?->getRenderedValue(), $snapshot_collection->toArray()),
        );
    }
}
