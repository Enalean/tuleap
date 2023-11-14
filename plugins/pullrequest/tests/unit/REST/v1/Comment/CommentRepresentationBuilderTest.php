<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\REST\v1\Comment;

use Codendi_HTMLPurifier;
use DateTimeImmutable;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\User\REST\MinimalUserRepresentation;

final class CommentRepresentationBuilderTest extends TestCase
{
    private Codendi_HTMLPurifier $purifier;
    private ContentInterpretorStub $interpreter;
    private MinimalUserRepresentation $user;

    protected function setUp(): void
    {
        $this->purifier    = Codendi_HTMLPurifier::instance();
        $this->interpreter = ContentInterpretorStub::build();
        $this->user        = MinimalUserRepresentation::build(UserTestBuilder::anActiveUser()->build());
    }

    private function build(Comment $comment): CommentRepresentation
    {
        $builder = new CommentRepresentationBuilder($this->purifier, $this->interpreter);
        return $builder->buildRepresentation(101, $this->user, $comment);
    }

    public function testItBuildsRepresentationForText(): void
    {
        $comment        = CommentTestBuilder::aTextComment("Galant AMG")->editedOn(new DateTimeImmutable())->build();
        $representation = $this->build($comment);
        self::assertSame($this->interpreter->getInterpretedContentWithReferencesCount(), 0);
        self::assertNotNull($representation->last_edition_date);
    }

    public function testItBuildsRepresentationForMarkdown(): void
    {
        $comment        = CommentTestBuilder::aMarkdownComment("Galant AMG")->build();
        $representation = $this->build($comment);
        self::assertSame($this->interpreter->getInterpretedContentWithReferencesCount(), 1);
        self::assertSame('commonmark', $representation->format);
        self::assertNull($representation->last_edition_date);
    }
}
