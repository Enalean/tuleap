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

use GitRepository;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\UniDiffLine;

class InlineCommentCodeContextExtractor
{
    private const CODE_CONTEXT_NB_LINES = 5;

    /**
     * @var FileUniDiffBuilder
     */
    private $builder;
    /**
     * @var \GitRepositoryFactory
     */
    private $git_repository_factory;

    public function __construct(FileUniDiffBuilder $builder, \GitRepositoryFactory $git_repository_factory)
    {
        $this->builder                = $builder;
        $this->git_repository_factory = $git_repository_factory;
    }

    /**
     * @throws InlineCommentCodeContextException
     */
    public function getCodeContext(InlineComment $inline_comment, PullRequest $pull_request): string
    {
        if ($inline_comment->getPullRequestId() !== $pull_request->getId()) {
            throw new \LogicException(
                sprintf(
                    'Inline comment is on PR #%d but PR #%d given',
                    $inline_comment->getPullRequestId(),
                    $pull_request->getId()
                )
            );
        }

        $git_repository_id = $pull_request->getRepoDestId();
        $git_repository    = $this->git_repository_factory->getRepositoryById($git_repository_id);
        if ($git_repository === null) {
            throw new InlineCommentCodeContextRepositoryNotFoundException($git_repository_id);
        }

        $unidiff = $this->builder->buildFileUniDiffFromCommonAncestor(
            $this->getGitExec($git_repository),
            $inline_comment->getFilePath(),
            $pull_request->getSha1Dest(),
            $pull_request->getSha1Src()
        );

        $end_offset_context   = $inline_comment->getUnidiffOffset();
        $start_offset_context = $inline_comment->getUnidiffOffset() - self::CODE_CONTEXT_NB_LINES;
        if ($start_offset_context < 1) {
            $start_offset_context = 1;
        }

        $lines_for_context = [];
        $lines             = $unidiff->getLines();
        for ($offset = $start_offset_context; $offset <= $end_offset_context; $offset++) {
            if (isset($lines[$offset])) {
                $lines_for_context[] = $lines[$offset];
            }
        }

        return $this->buildContextFromUniDiffLines(...$lines_for_context);
    }

    private function buildContextFromUniDiffLines(UniDiffLine ...$lines): string
    {
        $context = '';

        foreach ($lines as $line) {
            switch ($line->getType()) {
                case UniDiffLine::ADDED:
                    $start_line_symbol = '+';
                    break;
                case UniDiffLine::REMOVED:
                    $start_line_symbol = '-';
                    break;
                default:
                    $start_line_symbol = ' ';
            }
            $context .= $start_line_symbol . $line->getContent() . "\n";
        }

        return trim($context, "\n");
    }

    private function getGitExec(GitRepository $repository): GitExec
    {
        return new GitExec($repository->getFullPath(), $repository->getFullPath());
    }
}
