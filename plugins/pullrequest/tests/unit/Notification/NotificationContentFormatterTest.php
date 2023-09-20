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

namespace Tuleap\PullRequest\Tests\Notification;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\PullRequest\Notification\NotificationContentFormatter;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\Test\Stubs\ContentInterpretorStub;

final class NotificationContentFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \GitRepositoryFactory & Stub $git_repository_factory;
    private PullRequest $pull_request;
    private ContentInterpretorStub $content_interpreter;

    protected function setUp(): void
    {
        $this->content_interpreter    = ContentInterpretorStub::build();
        $this->git_repository_factory = $this->createStub(\GitRepositoryFactory::class);
        $this->pull_request           = PullRequestTestBuilder::aPullRequestInReview()->build();
    }

    public function testItReturnsTheCommentContentWithLineReturnsReplacedWhenTheCommentIsInTextFormat(): void
    {
        $comment = CommentTestBuilder::aTextComment("Some\ncontent<script>alert(true)</script>")->build();
        self::assertSame(
            <<<EOF
            Some<br />
            content&lt;script&gt;alert(true)&lt;/script&gt;
            EOF,
            $this->getFormattedContent($comment)
        );
    }

    public function testItReturnsTheCommentContentWithLineReturnsReplacedWhenTheRepositoryCantBeFound(): void
    {
        $content = "**Some content**";

        $this->git_repository_factory->method("getRepositoryById")->willReturn(null);

        self::assertSame(
            $content,
            $this->getFormattedContent(CommentTestBuilder::aMarkdownComment($content)->build())
        );
    }

    public function testItReturnsTheContentInterpretedWhenTheCommentIsWrittenInMarkdownFormat(): void
    {
        $repository = $this->createStub(\GitRepository::class);
        $repository->method("getProjectId")->willReturn(105);

        $this->git_repository_factory->method("getRepositoryById")->willReturn($repository);
        $this->getFormattedContent(CommentTestBuilder::aMarkdownComment("**Some content**")->build());

        self::assertEquals(
            1,
            $this->content_interpreter->getInterpretedContentWithReferencesCount()
        );
    }

    private function getFormattedContent(TimelineComment $comment): string
    {
        $formatter = new NotificationContentFormatter(
            $this->content_interpreter,
            $this->git_repository_factory,
            \Codendi_HTMLPurifier::instance()
        );

        return $formatter->getFormattedAndPurifiedNotificationContent(
            $this->pull_request,
            $comment
        );
    }
}
