<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Tests\Builders;

use Tuleap\PullRequest\Comment\NewComment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Test\Builders\UserTestBuilder;

final class NewCommentTestBuilder
{
    private PullRequest $pull_request;
    private int $project_id = 117;
    private int $parent_id  = 0;
    private \PFUser $author;
    private \DateTimeImmutable $post_date;

    private function __construct(
        private readonly string $content,
        private readonly string $format,
    ) {
        $this->pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();
        $this->author       = UserTestBuilder::buildWithDefaults();
        $this->post_date    = new \DateTimeImmutable('@1475084282');
    }

    public static function aMarkdownComment(string $content): self
    {
        return new self($content, PullRequest\Timeline\TimelineComment::FORMAT_MARKDOWN);
    }

    public function onPullRequest(PullRequest $pull_request): self
    {
        $this->pull_request = $pull_request;
        return $this;
    }

    public function childOf(int $parent_id): self
    {
        $this->parent_id = $parent_id;
        return $this;
    }

    public function build(): NewComment
    {
        return new NewComment(
            $this->pull_request,
            $this->project_id,
            $this->content,
            $this->format,
            $this->parent_id,
            $this->author,
            $this->post_date
        );
    }
}
