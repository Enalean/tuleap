<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\InlineComment;

use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\FileNullDiff;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\UniDiffLine;

require_once __DIR__ . '/../bootstrap.php';

class InlineCommentUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var InlineCommentUpdater
     */
    private $updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->updater = new InlineCommentUpdater();
    }

    public function testItShouldBeObsoleteIfLineWasAddedAndLineIsDeleted(): void
    {
        $comments = [new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false, 0, "right", "", TimelineComment::FORMAT_TEXT)];

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $changes_diff = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, '');

        $dest_diff     = new FileNullDiff();
        $targeted_diff = new FileUniDiff();

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments,
            $original_diff,
            $changes_diff,
            $dest_diff,
            $targeted_diff
        );

        $this->assertEquals(1, count($updated_comments));
        $this->assertTrue($updated_comments[0]->isOutdated());
    }

    public function testItShouldBeObsoleteIfLineWasKeptAndLineIsDeleted(): void
    {
        $comments = [new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false, 0, "right", "", TimelineComment::FORMAT_TEXT)];

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');

        $changes_diff = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, '');

        $dest_diff     = new FileNullDiff();
        $targeted_diff = new FileUniDiff();

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments,
            $original_diff,
            $changes_diff,
            $dest_diff,
            $targeted_diff
        );

        $this->assertEquals(1, count($updated_comments));
        $this->assertTrue($updated_comments[0]->isOutdated());
    }

    public function testItShouldBeObsoleteIfLineWasAddedAndLineContentHasChanged(): void
    {
        $comments = [new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false, 0, "right", "", TimelineComment::FORMAT_TEXT)];

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $changes_diff = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, 'une ligne');
        $changes_diff->addLine(UniDiffLine::ADDED, 2, null, 1, 'une ligne avec changement');

        $dest_diff = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne avec changement');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments,
            $original_diff,
            $changes_diff,
            $dest_diff,
            $targeted_diff
        );

        $this->assertEquals(1, count($updated_comments));
        $this->assertTrue($updated_comments[0]->isOutdated());
    }

    public function testItShouldBeMovedIfLineWasAddedAndLineIsMoved(): void
    {
        $comments = [new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false, 0, "right", "", TimelineComment::FORMAT_TEXT)];

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $changes_diff = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $changes_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $changes_diff->addLine(UniDiffLine::KEPT, 3, 1, 3, 'une ligne');

        $dest_diff = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $targeted_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $targeted_diff->addLine(UniDiffLine::ADDED, 3, null, 3, 'une ligne');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments,
            $original_diff,
            $changes_diff,
            $dest_diff,
            $targeted_diff
        );

        $this->assertEquals(1, count($updated_comments));
        $this->assertFalse($updated_comments[0]->isOutdated());
        $this->assertEquals(3, $updated_comments[0]->getUnidiffOffset());
    }

    public function testItShouldBeMovedIfLineWasKeptAndLineIsMoved(): void
    {
        $comments = [new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false, 0, "right", "", TimelineComment::FORMAT_TEXT)];

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');

        $changes_diff = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $changes_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $changes_diff->addLine(UniDiffLine::KEPT, 3, 1, 3, 'une ligne');

        $dest_diff = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $targeted_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $targeted_diff->addLine(UniDiffLine::ADDED, 3, null, 3, 'une ligne');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments,
            $original_diff,
            $changes_diff,
            $dest_diff,
            $targeted_diff
        );

        $this->assertEquals(1, count($updated_comments));
        $this->assertFalse($updated_comments[0]->isOutdated());
        $this->assertEquals(3, $updated_comments[0]->getUnidiffOffset());
    }

    public function testItShouldBeObsoleteIfLineWasDeletedAndLineIsNoMoreDeleted(): void
    {
        $comments = [new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false, 0, "right", "", TimelineComment::FORMAT_TEXT)];

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, 'une ligne');

        $changes_diff = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $dest_diff = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments,
            $original_diff,
            $changes_diff,
            $dest_diff,
            $targeted_diff
        );

        $this->assertEquals(1, count($updated_comments));
        $this->assertTrue($updated_comments[0]->isOutdated());
    }

    public function testItShouldBeKeptIfLineWasDeletedAndLineIsMoved(): void
    {
        $comments = [new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false, 0, "right", "", TimelineComment::FORMAT_TEXT)];

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, 'une ligne');

        $changes_diff = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');

        $dest_diff = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $targeted_diff->addLine(UniDiffLine::REMOVED, 2, 1, null, 'une ligne');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments,
            $original_diff,
            $changes_diff,
            $dest_diff,
            $targeted_diff
        );

        $this->assertEquals(1, count($updated_comments));
        $this->assertFalse($updated_comments[0]->isOutdated());
        $this->assertEquals(2, $updated_comments[0]->getUnidiffOffset());
    }
}
