<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use DateTimeImmutable;
use Tracker;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class PostPushTuleapArtifactCommentBuilderTest extends TestCase
{
    public function testReturnEmptyStringWhenKeywordIsNull(): void
    {
        $commit     = new PostPushCommitWebhookData(
            "123aze",
            "commit",
            "",
            "branch_name",
            1620725174,
            "user@example.fr",
            "user"
        );
        $reference  = new WebhookTuleapReference(12, null);
        $repository = new GitlabRepository(1, 12, "MyRepo", "", "https://example", new DateTimeImmutable());
        $artifact   = new Artifact(10, 1, 'submitter', 10050, false);
        $comment    = PostPushTuleapArtifactCommentBuilder::buildComment(
            "user",
            $commit,
            $reference,
            $repository,
            $artifact
        );
        self::assertEquals("", $comment);
    }

    public function testReturnEmptyStringWhenKeywordIsNotHandled(): void
    {
        $commit     = new PostPushCommitWebhookData(
            "123aze",
            "commit",
            "",
            "branch_name",
            1620725174,
            "user@example.fr",
            "user"
        );
        $reference  = new WebhookTuleapReference(12, "solved");
        $repository = new GitlabRepository(1, 12, "MyRepo", "", "https://example", new DateTimeImmutable());
        $artifact   = new Artifact(10, 1, 'submitter', 10050, false);
        $comment    = PostPushTuleapArtifactCommentBuilder::buildComment(
            "user",
            $commit,
            $reference,
            $repository,
            $artifact
        );
        self::assertEquals("", $comment);
    }

    public function testReturnCommentWhenKeywordIsResolves(): void
    {
        $commit     = new PostPushCommitWebhookData(
            "123aze",
            "commit",
            "",
            "branch_name",
            1620725174,
            "user@example.fr",
            "user"
        );
        $reference  = new WebhookTuleapReference(12, "resolves");
        $repository = new GitlabRepository(1, 12, "MyRepo", "", "https://example", new DateTimeImmutable());
        $artifact   = new Artifact(10, 1, 'submitter', 10050, false);
        $comment    = PostPushTuleapArtifactCommentBuilder::buildComment(
            "user",
            $commit,
            $reference,
            $repository,
            $artifact
        );
        self::assertEquals("solved by user with gitlab_commit #MyRepo/123aze", $comment);
    }

    public function testReturnCommentWhenKeywordIsCloses(): void
    {
        $commit     = new PostPushCommitWebhookData(
            "123aze",
            "commit",
            "",
            "branch_name",
            1620725174,
            "user@example.fr",
            "user"
        );
        $reference  = new WebhookTuleapReference(12, "closes");
        $repository = new GitlabRepository(1, 12, "MyRepo", "", "https://example", new DateTimeImmutable());
        $artifact   = new Artifact(10, 1, 'submitter', 10050, false);
        $comment    = PostPushTuleapArtifactCommentBuilder::buildComment(
            "user",
            $commit,
            $reference,
            $repository,
            $artifact
        );
        self::assertEquals("closed by user with gitlab_commit #MyRepo/123aze", $comment);
    }

    public function testReturnCommentWhenKeywordIsFixes(): void
    {
        $commit     = new PostPushCommitWebhookData(
            "123aze",
            "commit",
            "",
            "branch_name",
            1620725174,
            "user@example.fr",
            "user"
        );
        $reference  = new WebhookTuleapReference(12, "fixes");
        $repository = new GitlabRepository(1, 12, "MyRepo", "", "https://example", new DateTimeImmutable());

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getItemName')->willReturn("tracker_isetta");
        $artifact = $this->createMock(Artifact::class);
        $artifact->method("getTracker")->willReturn($tracker);

        $comment = PostPushTuleapArtifactCommentBuilder::buildComment(
            "user",
            $commit,
            $reference,
            $repository,
            $artifact
        );
        self::assertEquals("tracker_isetta fixed by user with gitlab_commit #MyRepo/123aze", $comment);
    }
}
