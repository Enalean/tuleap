<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment;

use TuleapTestCase;
use GitRepository;
use ForgeConfig;
use \Tuleap\PullRequest\FileUniDiff;
use \Tuleap\PullRequest\FileNullDiff;
use \Tuleap\PullRequest\UniDiffLine;

require_once __DIR__ . '/../bootstrap.php';

class InlineCommentUpdaterTest extends TuleapTestCase
{
    public function __construct()
    {
        $this->updater = new InlineCommentUpdater();
    }
}

class WhenSourceChangesTest extends InlineCommentUpdaterTest {
    public function itShouldBeObsoleteIfLineWasAddedAndLineIsDeleted()
    {
        $comments = array(new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false));

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $changes_diff  = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, '');

        $dest_diff     = new FileNullDiff();
        $targeted_diff = new FileUniDiff();

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments, $original_diff, $changes_diff, $dest_diff, $targeted_diff);

        $this->assertEqual(1, count($updated_comments));
        $this->assertEqual(true, $updated_comments[0]->isOutdated());
    }

    public function itShouldBeObsoleteIfLineWasKeptAndLineIsDeleted()
    {
        $comments = array(new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false));

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');

        $changes_diff  = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, '');

        $dest_diff     = new FileNullDiff();
        $targeted_diff = new FileUniDiff();

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments, $original_diff, $changes_diff, $dest_diff, $targeted_diff);

        $this->assertEqual(1, count($updated_comments));
        $this->assertEqual(true, $updated_comments[0]->isOutdated());
    }

    public function itShouldBeObsoleteIfLineWasAddedAndLineContentHasChanged()
    {
        $comments = array(new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false));

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $changes_diff  = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::REMOVED, 1, 1   , null, 'une ligne');
        $changes_diff->addLine(UniDiffLine::ADDED  , 2, null, 1   , 'une ligne avec changement');

        $dest_diff     = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne avec changement');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments, $original_diff, $changes_diff, $dest_diff, $targeted_diff);

        $this->assertEqual(1, count($updated_comments));
        $this->assertEqual(true, $updated_comments[0]->isOutdated());
    }

    public function itShouldBeMovedIfLineWasAddedAndLineIsMoved()
    {
        $comments = array(new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false));

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $changes_diff  = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $changes_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $changes_diff->addLine(UniDiffLine::KEPT , 3, 1   , 3, 'une ligne');

        $dest_diff     = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $targeted_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $targeted_diff->addLine(UniDiffLine::ADDED, 3, null, 3, 'une ligne');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments, $original_diff, $changes_diff, $dest_diff, $targeted_diff);

        $this->assertEqual(1, count($updated_comments));
        $this->assertEqual(false, $updated_comments[0]->isOutdated());
        $this->assertEqual(3, $updated_comments[0]->getUnidiffOffset());
    }

    public function itShouldBeMovedIfLineWasKeptAndLineIsMoved()
    {
        $comments = array(new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false));

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');

        $changes_diff  = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $changes_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $changes_diff->addLine(UniDiffLine::KEPT , 3, 1   , 3, 'une ligne');

        $dest_diff     = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');
        $targeted_diff->addLine(UniDiffLine::ADDED, 2, null, 2, 'header 2');
        $targeted_diff->addLine(UniDiffLine::ADDED, 3, null, 3, 'une ligne');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments, $original_diff, $changes_diff, $dest_diff, $targeted_diff);

        $this->assertEqual(1, count($updated_comments));
        $this->assertEqual(false, $updated_comments[0]->isOutdated());
        $this->assertEqual(3, $updated_comments[0]->getUnidiffOffset());
    }

    public function itShouldBeObsoleteIfLineWasDeletedAndLineIsNoMoreDeleted()
    {
        $comments = array(new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false));

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, 'une ligne');

        $changes_diff  = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'une ligne');

        $dest_diff     = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'une ligne');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments, $original_diff, $changes_diff, $dest_diff, $targeted_diff);

        $this->assertEqual(1, count($updated_comments));
        $this->assertEqual(true, $updated_comments[0]->isOutdated());
    }

    public function itShouldBeKeptIfLineWasDeletedAndLineIsMoved()
    {
        $comments = array(new InlineComment(1, 1, 1, 1, 'file.txt', 1, 'commentaire', false));

        $original_diff = new FileUniDiff();
        $original_diff->addLine(UniDiffLine::REMOVED, 1, 1, null, 'une ligne');

        $changes_diff  = new FileUniDiff();
        $changes_diff->addLine(UniDiffLine::ADDED, 1, null, 1, 'header 1');

        $dest_diff     = new FileNullDiff();

        $targeted_diff = new FileUniDiff();
        $targeted_diff->addLine(UniDiffLine::ADDED,    1, null, 1,    'header 1');
        $targeted_diff->addLine(UniDiffLine::REMOVED,  2, 1   , null, 'une ligne');

        $updated_comments = $this->updater->updateWhenSourceChanges(
            $comments, $original_diff, $changes_diff, $dest_diff, $targeted_diff);

        $this->assertEqual(1, count($updated_comments));
        $this->assertEqual(false, $updated_comments[0]->isOutdated());
        $this->assertEqual(2, $updated_comments[0]->getUnidiffOffset());
    }
}
