<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Codendi_HTMLPurifier;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\User\REST\MinimalUserRepresentation;

final class PullRequestInlineCommentRepresentationTest extends TestCase
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

    public function testItBuildsRepresentationForText(): void
    {
        PullRequestInlineCommentRepresentation::build(
            $this->purifier,
            $this->interpreter,
            1,
            $this->user,
            123456789,
            "My content",
            101,
            "left",
            1,
            213,
            "path/to/file",
            "placid-blue",
            TimelineComment::FORMAT_TEXT
        );
        self::assertSame($this->interpreter->getInterpretedContentWithReferencesCount(), 0);
    }

    public function testItBuildsRepresentationForMarkdown(): void
    {
        PullRequestInlineCommentRepresentation::build(
            $this->purifier,
            $this->interpreter,
            1,
            $this->user,
            123456789,
            "My content",
            101,
            "left",
            1,
            213,
            "path/to/file",
            "placid-blue",
            TimelineComment::FORMAT_MARKDOWN
        );
        self::assertSame($this->interpreter->getInterpretedContentWithReferencesCount(), 1);
    }
}
