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

namespace Tuleap\PullRequest\REST\v1\InlineComment;

use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\User\REST\MinimalUserRepresentation;

final class SingleRepresentationBuilderTest extends TestCase
{
    private ContentInterpretorStub $interpreter;

    protected function setUp(): void
    {
        $this->interpreter = ContentInterpretorStub::build();
    }

    private function build(InlineComment $comment): InlineCommentRepresentation
    {
        $user     = MinimalUserRepresentation::build(UserTestBuilder::buildWithDefaults());
        $purifier = \Codendi_HTMLPurifier::instance();
        $builder  = new SingleRepresentationBuilder($purifier, $this->interpreter);
        return $builder->build(141, $user, $comment);
    }

    public function testItBuildsRepresentationForMarkdownComment(): void
    {
        $comment        = InlineCommentTestBuilder::aMarkdownComment('preparoxysmal bibliognostic')->build();
        $representation = $this->build($comment);
        self::assertSame(1, $this->interpreter->getInterpretedContentWithReferencesCount());
        self::assertSame(TimelineComment::FORMAT_MARKDOWN, $representation->format);
    }

    public function testItBuildsRepresentationForTextComment(): void
    {
        $comment        = InlineCommentTestBuilder::aTextComment('waybread subinflammation')->build();
        $representation = $this->build($comment);
        self::assertSame(0, $this->interpreter->getInterpretedContentWithReferencesCount());
    }
}
