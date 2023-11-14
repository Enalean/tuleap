<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment\Notification;

use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\UniDiffLine;

final class InlineCommentCodeContextExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject & FileUniDiffBuilder $file_unidiff_builder;
    private MockObject & GitRepositoryFactory $git_repository_factory;
    private InlineComment $inline_comment;
    private PullRequest $pull_request;

    protected function setUp(): void
    {
        $this->file_unidiff_builder   = $this->createMock(FileUniDiffBuilder::class);
        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);

        $this->pull_request   = PullRequestTestBuilder::aPullRequestInReview()->withId(56)->build();
        $this->inline_comment = InlineCommentTestBuilder::aTextComment('unrepiqued macroelement')
            ->onPullRequest($this->pull_request)
            ->build();
    }

    private function extract(): string
    {
        $code_context_extractor = new InlineCommentCodeContextExtractor(
            $this->file_unidiff_builder,
            $this->git_repository_factory
        );
        return $code_context_extractor->getCodeContext($this->inline_comment, $this->pull_request);
    }

    public function testCodeContextIsExtracted(): void
    {
        $this->inline_comment = InlineCommentTestBuilder::aTextComment('unrepiqued macroelement')
            ->onPullRequest($this->pull_request)
            ->onUnidiffOffset(8)
            ->build();

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('getFullPath')->willReturn('/repo_path');
        $this->git_repository_factory->method('getRepositoryById')->willReturn($repository);

        $unidiff = new FileUniDiff();
        $unidiff->addLine(UniDiffLine::KEPT, 1, 1, 1, 'A');
        $unidiff->addLine(UniDiffLine::KEPT, 2, 2, 2, 'B');
        $unidiff->addLine(UniDiffLine::KEPT, 3, 3, 3, 'C');
        $unidiff->addLine(UniDiffLine::KEPT, 4, 4, 4, 'D');
        $unidiff->addLine(UniDiffLine::KEPT, 5, 5, 5, 'E');
        $unidiff->addLine(UniDiffLine::KEPT, 6, 6, 6, 'F');
        $unidiff->addLine(UniDiffLine::REMOVED, 7, 7, 7, 'Foo');
        $unidiff->addLine(UniDiffLine::ADDED, 8, 8, 8, 'Bar');
        $unidiff->addLine(UniDiffLine::KEPT, 9, 9, 9, 'Should not be present');
        $this->file_unidiff_builder->method('buildFileUniDiffFromCommonAncestor')->willReturn($unidiff);

        self::assertEquals(
            <<<EOF
             C
             D
             E
             F
            -Foo
            +Bar
            EOF,
            $this->extract()
        );
    }

    public function testCodeContextIsExtractedOnASmallFile(): void
    {
        $this->inline_comment = InlineCommentTestBuilder::aTextComment('unrepiqued macroelement')
            ->onPullRequest($this->pull_request)
            ->onUnidiffOffset(1)
            ->build();

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('getFullPath')->willReturn('/repo_path');
        $this->git_repository_factory->method('getRepositoryById')->willReturn($repository);

        $unidiff = new FileUniDiff();
        $unidiff->addLine(UniDiffLine::ADDED, 1, null, 1, 'Baz');
        $this->file_unidiff_builder->method('buildFileUniDiffFromCommonAncestor')->willReturn($unidiff);

        self::assertEquals('+Baz', $this->extract());
    }

    public function testCodeContextEndingWithEmptyLinesIsKept(): void
    {
        $this->inline_comment = InlineCommentTestBuilder::aTextComment('unrepiqued macroelement')
            ->onPullRequest($this->pull_request)
            ->onUnidiffOffset(5)
            ->build();

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('getFullPath')->willReturn('/repo_path');
        $this->git_repository_factory->method('getRepositoryById')->willReturn($repository);

        $unidiff = new FileUniDiff();
        $unidiff->addLine(UniDiffLine::KEPT, 1, 1, 1, '');
        $unidiff->addLine(UniDiffLine::KEPT, 2, 2, 2, '');
        $unidiff->addLine(UniDiffLine::KEPT, 3, 3, 3, '');
        $unidiff->addLine(UniDiffLine::KEPT, 4, 4, 4, '');
        $unidiff->addLine(UniDiffLine::KEPT, 5, 5, 5, '');
        $this->file_unidiff_builder->method('buildFileUniDiffFromCommonAncestor')->willReturn($unidiff);

        self::assertEquals(" \n \n \n \n ", $this->extract());
    }

    public function testRefusesToExtractWhenTheInlineCommentDoesNotMatchTheGivenPullRequest(): void
    {
        $this->inline_comment = InlineCommentTestBuilder::aTextComment('unrepiqued macroelement')
            ->onPullRequest(PullRequestTestBuilder::aPullRequestInReview()->withId(60)->build())
            ->build();

        $this->expectException(\LogicException::class);
        $this->extract();
    }

    public function testDoesNotExtractWhenTheRepositoryCannotBeFound(): void
    {
        $this->git_repository_factory->method('getRepositoryById')->willReturn(null);

        $this->expectException(InlineCommentCodeContextRepositoryNotFoundException::class);
        $this->extract();
    }
}
