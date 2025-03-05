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

use DateTimeImmutable;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\User\REST\MinimalUserRepresentation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentRepresentationBuilderTest extends TestCase
{
    private ContentInterpretorStub $interpreter;

    protected function setUp(): void
    {
        $this->interpreter = ContentInterpretorStub::withInterpretedText('');
    }

    private function build(Comment $comment): CommentRepresentation
    {
        $purifier = \Codendi_HTMLPurifier::instance();
        $user     = MinimalUserRepresentation::build(UserTestBuilder::anActiveUser()->build(), ProvideUserAvatarUrlStub::build());
        $builder  = new CommentRepresentationBuilder($purifier, $this->interpreter);
        return $builder->buildRepresentation(101, $user, $comment);
    }

    public function testItBuildsRepresentationForText(): void
    {
        $comment        = CommentTestBuilder::aTextComment('Galant AMG')->build();
        $representation = $this->build($comment);
        self::assertNull($representation->last_edition_date);
        self::assertSame('Galant AMG', $representation->post_processed_content);
    }

    public function testItBuildsRepresentationForMarkdown(): void
    {
        $this->interpreter = ContentInterpretorStub::withInterpretedText('<b>Galant AMG</b>');
        $comment           = CommentTestBuilder::aMarkdownComment('**Galant AMG**')
            ->editedOn(new DateTimeImmutable())
            ->build();
        $representation    = $this->build($comment);
        self::assertSame('<b>Galant AMG</b>', $representation->post_processed_content);
        self::assertSame(TimelineComment::FORMAT_MARKDOWN, $representation->format);
        self::assertNotNull($representation->last_edition_date);
    }
}
