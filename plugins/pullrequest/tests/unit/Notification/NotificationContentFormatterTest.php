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

namespace Tuleap\PullRequest\Notification;

use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\ContentInterpretorStub;

final class NotificationContentFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const COMMENT_SOURCE      = '**Some content**';
    private const INTERPRETED_COMMENT = '<b>Some content</b>';
    private RetrieveGitRepositoryStub $repository_retriever;
    private PullRequest $pull_request;
    private ContentInterpretorStub $content_interpreter;
    private Comment $comment;

    protected function setUp(): void
    {
        $this->content_interpreter  = ContentInterpretorStub::withInterpretedText(self::INTERPRETED_COMMENT);
        $project                    = ProjectTestBuilder::aProject()->withId(105)->build();
        $repository                 = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->build();
        $this->repository_retriever = RetrieveGitRepositoryStub::withGitRepository($repository);
        $this->pull_request         = PullRequestTestBuilder::aPullRequestInReview()->build();
        $this->comment              = CommentTestBuilder::aMarkdownComment(self::COMMENT_SOURCE)->build();
    }

    private function getFormattedContent(): string
    {
        $formatter = new NotificationContentFormatter(
            $this->content_interpreter,
            $this->repository_retriever,
            \Codendi_HTMLPurifier::instance()
        );

        return $formatter->getFormattedAndPurifiedNotificationContent(
            $this->pull_request,
            $this->comment
        );
    }

    public function testItReturnsTheCommentContentWithLineReturnsReplacedWhenTheCommentIsInTextFormat(): void
    {
        $this->comment = CommentTestBuilder::aTextComment("Some\ncontent<script>alert(true)</script>")->build();

        self::assertSame(
            <<<EOF
            Some<br />
            content&lt;script&gt;alert(true)&lt;/script&gt;
            EOF,
            $this->getFormattedContent()
        );
    }

    public function testItReturnsTheCommentContentWithLineReturnsReplacedWhenTheRepositoryCantBeFound(): void
    {
        $this->repository_retriever = RetrieveGitRepositoryStub::withoutGitRepository();
        $this->comment              = CommentTestBuilder::aTextComment("vespertinal\nbimalar")->build();
        self::assertSame(
            <<<EOF
            vespertinal<br />
            bimalar
            EOF,
            $this->getFormattedContent()
        );
    }

    public function testItReturnsTheContentInterpretedWhenTheCommentIsWrittenInMarkdownFormat(): void
    {
        self::assertSame(self::INTERPRETED_COMMENT, $this->getFormattedContent());
    }
}
