<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\PullRequest\REST\v1\BrokenGitReferenceCheck;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\Test\PHPUnit\TestCase;

final class BrokenGitReferenceCheckTest extends TestCase
{
    private const TITLE       = "A title";
    private const DESCRIPTION = "A description";

    public function testItWillCheckIfPATCHRepresentationContainsATitle(): void
    {
        $check = BrokenGitReferenceCheck::fromPATCHRepresentation(
            new PullRequestPATCHRepresentation(
                PullRequestRepresentation::STATUS_MERGE,
                self::TITLE,
                self::DESCRIPTION,
                TimelineComment::FORMAT_MARKDOWN,
            )
        );

        self::assertTrue($check->isValue());
    }

    public function testItWillCheckIfPATCHRepresentationContainsADescription(): void
    {
        $check = BrokenGitReferenceCheck::fromPATCHRepresentation(
            new PullRequestPATCHRepresentation(
                PullRequestRepresentation::STATUS_MERGE,
                '',
                self::DESCRIPTION,
                TimelineComment::FORMAT_MARKDOWN,
            )
        );

        self::assertTrue($check->isValue());
    }

    public function testItWillCheckIfPATCHRepresentationContainsADescriptionFormat(): void
    {
        $check = BrokenGitReferenceCheck::fromPATCHRepresentation(
            new PullRequestPATCHRepresentation(
                PullRequestRepresentation::STATUS_MERGE,
                '',
                '',
                TimelineComment::FORMAT_MARKDOWN,
            )
        );

        self::assertTrue($check->isValue());
    }

    public function testItWillCheckIfPATCHRepresentationContainsAMergeStatus(): void
    {
        $check = BrokenGitReferenceCheck::fromPATCHRepresentation(
            new PullRequestPATCHRepresentation(
                PullRequestRepresentation::STATUS_MERGE,
                '',
                '',
                '',
            )
        );

        self::assertTrue($check->isValue());
    }

    public function testItWillCheckIfPATCHRepresentationContainsAReviewStatus(): void
    {
        $check = BrokenGitReferenceCheck::fromPATCHRepresentation(
            new PullRequestPATCHRepresentation(
                PullRequestRepresentation::STATUS_REVIEW,
                '',
                '',
                '',
            )
        );

        self::assertTrue($check->isValue());
    }

    public function testItWillSkipCheckIfPATCHRepresentationContainsOnlyAnAbandonStatus(): void
    {
        $check = BrokenGitReferenceCheck::fromPATCHRepresentation(
            new PullRequestPATCHRepresentation(
                PullRequestRepresentation::STATUS_ABANDON,
                '',
                '',
                '',
            )
        );

        self::assertTrue($check->isNothing());
    }
}
