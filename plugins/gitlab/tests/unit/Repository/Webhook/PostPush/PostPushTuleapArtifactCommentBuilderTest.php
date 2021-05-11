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
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;

class PostPushTuleapArtifactCommentBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testReturnEmptyStringWhenKeywordIsNull(): void
    {
        $commit     = new PostPushCommitWebhookData("123aze", "commit", "", "branch_name", 1620725174, "user@example.fr", "user");
        $reference  = new WebhookTuleapReference(12, null);
        $repository = new GitlabRepository(1, 12, "MyRepo", "", "https://example", new DateTimeImmutable());
        $comment    = PostPushTuleapArtifactCommentBuilder::buildComment("user", $commit, $reference, $repository);
        self::assertEquals("", $comment);
    }

    public function testReturnEmptyStringWhenKeywordIsNotResolve(): void
    {
        $commit     = new PostPushCommitWebhookData("123aze", "commit", "", "branch_name", 1620725174, "user@example.fr", "user");
        $reference  = new WebhookTuleapReference(12, "solved");
        $repository = new GitlabRepository(1, 12, "MyRepo", "", "https://example", new DateTimeImmutable());
        $comment    = PostPushTuleapArtifactCommentBuilder::buildComment("user", $commit, $reference, $repository);
        self::assertEquals("", $comment);
    }

    public function testReturnCommentWhenKeywordIsResolve(): void
    {
        $commit     = new PostPushCommitWebhookData("123aze", "commit", "", "branch_name", 1620725174, "user@example.fr", "user");
        $reference  = new WebhookTuleapReference(12, "resolve");
        $repository = new GitlabRepository(1, 12, "MyRepo", "", "https://example", new DateTimeImmutable());
        $comment    = PostPushTuleapArtifactCommentBuilder::buildComment("user", $commit, $reference, $repository);
        self::assertEquals("solved by user with gitlab_commit #MyRepo/123aze", $comment);
    }
}
