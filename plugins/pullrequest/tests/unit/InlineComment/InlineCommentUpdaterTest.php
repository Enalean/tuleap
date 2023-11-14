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

use Tuleap\PullRequest\FileNullDiff;
use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\UniDiffLine;

final class InlineCommentUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FileUniDiff $original_diff;
    private FileUniDiff $changes_diff;
    private FileUniDiff $targeted_diff;

    protected function setUp(): void
    {
        $this->original_diff = new FileUniDiff();
        $this->changes_diff  = new FileUniDiff();
        $this->targeted_diff = new FileUniDiff();
    }

    private function update(): array
    {
        $comments = [
            InlineCommentTestBuilder::aTextComment('nonanesthetized Panak')
                ->onUnidiffOffset(1)
                ->build(),
        ];

        $dest_diff = new FileNullDiff();

        $updater = new InlineCommentUpdater();
        return $updater->updateWhenSourceChanges(
            $comments,
            $this->original_diff,
            $this->changes_diff,
            $dest_diff,
            $this->targeted_diff
        );
    }

    public function testItShouldBeObsoleteIfLineWasAddedAndLineIsDeleted(): void
    {
        $this->original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');
        $this->changes_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, '');

        $updated_comments = $this->update();

        $this->assertCount(1, $updated_comments);
        $this->assertTrue($updated_comments[0]->isOutdated());
    }

    public function testItShouldBeObsoleteIfLineWasKeptAndLineIsDeleted(): void
    {
        $this->original_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');
        $this->changes_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, '');

        $updated_comments = $this->update();

        $this->assertCount(1, $updated_comments);
        $this->assertTrue($updated_comments[0]->isOutdated());
    }

    public function testItShouldBeObsoleteIfLineWasAddedAndLineContentHasChanged(): void
    {
        $this->original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $this->changes_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, 'une ligne');
        $this->changes_diff->addLine(UniDiffLine::ADDED, 2, null, 1, 'une ligne avec changement');

        $this->targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne avec changement');

        $updated_comments = $this->update();

        $this->assertCount(1, $updated_comments);
        $this->assertTrue($updated_comments[0]->isOutdated());
    }

    public function testItShouldBeMovedIfLineWasAddedAndLineIsMoved(): void
    {
        $this->original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $this->changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $this->changes_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $this->changes_diff->addLine(UniDiffLine::KEPT, 3, 1, 3, 'une ligne');

        $this->targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $this->targeted_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $this->targeted_diff->addLine(UniDiffLine::ADDED, 3, null, 3, 'une ligne');

        $updated_comments = $this->update();

        $this->assertCount(1, $updated_comments);
        $this->assertFalse($updated_comments[0]->isOutdated());
        $this->assertSame(3, $updated_comments[0]->getUnidiffOffset());
    }

    public function testItShouldBeMovedIfLineWasKeptAndLineIsMoved(): void
    {
        $this->original_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');

        $this->changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $this->changes_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $this->changes_diff->addLine(UniDiffLine::KEPT, 3, 1, 3, 'une ligne');

        $this->targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $this->targeted_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $this->targeted_diff->addLine(UniDiffLine::ADDED, 3, null, 3, 'une ligne');

        $updated_comments = $this->update();

        $this->assertCount(1, $updated_comments);
        $this->assertFalse($updated_comments[0]->isOutdated());
        $this->assertSame(3, $updated_comments[0]->getUnidiffOffset());
    }

    public function testItShouldBeObsoleteIfLineWasDeletedAndLineIsNoMoreDeleted(): void
    {
        $this->original_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, 'une ligne');

        $this->changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $this->targeted_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');

        $updated_comments = $this->update();

        $this->assertCount(1, $updated_comments);
        $this->assertTrue($updated_comments[0]->isOutdated());
    }

    public function testItShouldBeKeptIfLineWasDeletedAndLineIsMoved(): void
    {
        $this->original_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, 'une ligne');

        $this->changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');

        $this->targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $this->targeted_diff->addLine(UniDiffLine::REMOVED, 2, 1, null, 'une ligne');

        $updated_comments = $this->update();

        $this->assertCount(1, $updated_comments);
        $this->assertFalse($updated_comments[0]->isOutdated());
        $this->assertSame(2, $updated_comments[0]->getUnidiffOffset());
    }
}
