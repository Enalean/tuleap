<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Comment;

use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\Factory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ParentIdValidatorForCommentTest extends TestCase
{
    /**
     * @var Factory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $comment_factory;
    private ParentIdValidatorForComment $validator;
    private const PULL_REQUEST_ID = 10;

    protected function setUp(): void
    {
        $this->comment_factory = $this->createMock(Factory::class);
        $this->validator       = new ParentIdValidatorForComment($this->comment_factory);
    }

    public function testItDoesNothingIfParentIdIsZero(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->checkParentValidity(0, self::PULL_REQUEST_ID);
    }

    public function testItThrowsAnExceptionWhenCommentIsNotAddedOnARootComment(): void
    {
        $parent_id = 1;
        $comment   = new Comment(
            1,
            self::PULL_REQUEST_ID,
            (int) UserTestBuilder::anActiveUser()->build()->getId(),
            time(),
            "My content",
            1234,
            "graffiti-yellow",
            Comment::FORMAT_TEXT
        );
        $this->comment_factory->method('getCommentByID')->willReturn($comment);

        $this->expectExceptionCode(400);
        $this->expectDeprecationMessage("first comment of thread");
        $this->validator->checkParentValidity($parent_id, self::PULL_REQUEST_ID);
    }

    public function testITThrowsAnExceptionWhenCommentIsNotAddedOnTheSamePullRequestThanTheProvidedOne(): void
    {
        $parent_id = 1;
        $comment   = new Comment(
            1,
            1234,
            (int) UserTestBuilder::anActiveUser()->build()->getId(),
            time(),
            "My content",
            0,
            "graffiti-yellow",
            Comment::FORMAT_TEXT
        );
        $this->comment_factory->method('getCommentByID')->willReturn($comment);

        $this->expectExceptionCode(400);
        $this->expectDeprecationMessage("must be the same than provided comment");
        $this->validator->checkParentValidity($parent_id, self::PULL_REQUEST_ID);
    }

    public function testItDoesNotThrowIfParentIdIsValidForComment(): void
    {
        $this->expectNotToPerformAssertions();

        $parent_id = 1;
        $comment   = new Comment(
            1,
            self::PULL_REQUEST_ID,
            (int) UserTestBuilder::anActiveUser()->build()->getId(),
            time(),
            "My content",
            0,
            "graffiti-yellow",
            Comment::FORMAT_TEXT
        );
        $this->comment_factory->method('getCommentByID')->willReturn($comment);

        $this->validator->checkParentValidity($parent_id, self::PULL_REQUEST_ID);
    }
}
