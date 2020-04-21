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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\UniDiffLine;

final class InlineCommentCodeContextExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|FileUniDiffBuilder
     */
    private $file_unidiff_builder;
    /**
     * @var GitRepositoryFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $git_repository_factory;

    /**
     * @var InlineCommentCodeContextExtractor
     */
    private $code_context_extractor;

    protected function setUp(): void
    {
        $this->file_unidiff_builder   = \Mockery::mock(FileUniDiffBuilder::class);
        $this->git_repository_factory = \Mockery::mock(GitRepositoryFactory::class);

        $this->code_context_extractor = new InlineCommentCodeContextExtractor(
            $this->file_unidiff_builder,
            $this->git_repository_factory
        );
    }

    public function testCodeContextIsExtracted(): void
    {
        $pr             = $this->buildPullRequest(56);
        $inline_comment = $this->buildInlineComment(125, $pr->getId(), 8);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getFullPath')->andReturn('/repo_path');
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturn($repository);

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
        $this->file_unidiff_builder->shouldReceive('buildFileUniDiffFromCommonAncestor')->andReturn($unidiff);

        $code_context = $this->code_context_extractor->getCodeContext($inline_comment, $pr);

        $this->assertEquals(
            <<<EOF
             C
             D
             E
             F
            -Foo
            +Bar
            EOF,
            $code_context
        );
    }

    public function testCodeContextIsExtractedOnASmallFile(): void
    {
        $pr             = $this->buildPullRequest(57);
        $inline_comment = $this->buildInlineComment(128, $pr->getId(), 1);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getFullPath')->andReturn('/repo_path');
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturn($repository);

        $unidiff = new FileUniDiff();
        $unidiff->addLine(UniDiffLine::ADDED, 1, null, 1, 'Baz');
        $this->file_unidiff_builder->shouldReceive('buildFileUniDiffFromCommonAncestor')->andReturn($unidiff);

        $code_context = $this->code_context_extractor->getCodeContext($inline_comment, $pr);

        $this->assertEquals('+Baz', $code_context);
    }

    public function testCodeContextEndingWithEmptyLinesIsKept(): void
    {
        $pr             = $this->buildPullRequest(57);
        $inline_comment = $this->buildInlineComment(128, $pr->getId(), 5);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getFullPath')->andReturn('/repo_path');
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturn($repository);

        $unidiff = new FileUniDiff();
        $unidiff->addLine(UniDiffLine::KEPT, 1, 1, 1, '');
        $unidiff->addLine(UniDiffLine::KEPT, 2, 2, 2, '');
        $unidiff->addLine(UniDiffLine::KEPT, 3, 3, 3, '');
        $unidiff->addLine(UniDiffLine::KEPT, 4, 4, 4, '');
        $unidiff->addLine(UniDiffLine::KEPT, 5, 5, 5, '');
        $this->file_unidiff_builder->shouldReceive('buildFileUniDiffFromCommonAncestor')->andReturn($unidiff);

        $code_context = $this->code_context_extractor->getCodeContext($inline_comment, $pr);

        $this->assertEquals(" \n \n \n \n ", $code_context);
    }

    public function testRefusesToExtractWhenTheInlineCommentDoesNotMatchTheGivenPullRequest(): void
    {
        $this->expectException(\LogicException::class);
        $this->code_context_extractor->getCodeContext(
            $this->buildInlineComment(129, 60, 5),
            $this->buildPullRequest(59)
        );
    }

    public function testDoesNotExtractWhenTheRepositoryCannotBeFound(): void
    {
        $pr             = $this->buildPullRequest(61);
        $inline_comment = $this->buildInlineComment(130, $pr->getId(), 1);

        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturn(null);

        $this->expectException(InlineCommentCodeContextRepositoryNotFoundException::class);

        $this->code_context_extractor->getCodeContext($inline_comment, $pr);
    }

    private function buildInlineComment(int $id, int $pull_request_id, int $unidiff_offset): InlineComment
    {
        return new InlineComment(
            $id,
            $pull_request_id,
            102,
            12,
            'file/path',
            $unidiff_offset,
            'Comment',
            false
        );
    }

    private function buildPullRequest(int $id): PullRequest
    {
        return new PullRequest(
            $id,
            'Title',
            'Description',
            78,
            102,
            10,
            'dev',
            '103e3d371a6f7ee7013ae64ba0d5879fc330af91',
            78,
            'master',
            'f65cc8e2740a819af60c9f624ae378676291888d'
        );
    }
}
