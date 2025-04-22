<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\Changeset\Comment;

use ProjectUGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\InvalidCommentFormatException;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\UserIsNotAllowedToSeeUGroups;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsTextCommentRepresentation(): void
    {
        $interpreter    = ContentInterpretorStub::withInterpretedText('Blah');
        $builder        = new CommentRepresentationBuilder($interpreter);
        $representation = $builder->buildRepresentation(
            $this->buildComment('A text comment', CommentFormatIdentifier::TEXT->value),
            new UserIsNotAllowedToSeeUGroups()
        );
        self::assertSame('text', $representation->format);
        self::assertSame('A text comment', $representation->body);
        self::assertNull($representation->ugroups);
        self::assertNotEmpty($representation->post_processed_body);
    }

    public function testItBuildsTextCommentRepresentationWithPrivateUgroups(): void
    {
        $interpreter    = ContentInterpretorStub::withInterpretedText('Blah');
        $builder        = new CommentRepresentationBuilder($interpreter);
        $representation = $builder->buildRepresentation(
            $this->buildComment('A text comment', CommentFormatIdentifier::TEXT->value),
            [$this->buildProjectUGroup()]
        );
        self::assertSame('text', $representation->format);
        self::assertSame('A text comment', $representation->body);
        self::assertNotNull($representation->ugroups);
        self::assertEquals('developers', $representation->ugroups[0]->label);
        self::assertNotEmpty($representation->post_processed_body);
    }

    public function testItBuildsHTMLCommentRepresentation(): void
    {
        $interpreter    = ContentInterpretorStub::withInterpretedText('Blah');
        $builder        = new CommentRepresentationBuilder($interpreter);
        $representation = $builder->buildRepresentation(
            $this->buildComment('<p>An HTML comment</p>', CommentFormatIdentifier::HTML->value),
            new UserIsNotAllowedToSeeUGroups()
        );
        self::assertSame('html', $representation->format);
        self::assertSame('<p>An HTML comment</p>', $representation->body);
        self::assertNull($representation->ugroups);
        self::assertNotEmpty($representation->post_processed_body);
    }

    public function testItBuildsHTMLCommentRepresentationWithPrivateComment(): void
    {
        $interpreter    = ContentInterpretorStub::withInterpretedText('Blah');
        $builder        = new CommentRepresentationBuilder($interpreter);
        $representation = $builder->buildRepresentation(
            $this->buildComment('<p>An HTML comment</p>', CommentFormatIdentifier::HTML->value),
            [$this->buildProjectUGroup()]
        );
        self::assertSame('html', $representation->format);
        self::assertSame('<p>An HTML comment</p>', $representation->body);
        self::assertNotNull($representation->ugroups);
        self::assertEquals('developers', $representation->ugroups[0]->label);
        self::assertNotEmpty($representation->post_processed_body);
    }

    public function testItBuildsCommonMarkCommentRepresentation(): void
    {
        $interpreter    = ContentInterpretorStub::withInterpretedText('<p>A <strong>CommonMark</strong> comment');
        $builder        = new CommentRepresentationBuilder($interpreter);
        $representation = $builder->buildRepresentation(
            $this->buildComment('A **CommonMark** comment', CommentFormatIdentifier::COMMONMARK->value),
            new UserIsNotAllowedToSeeUGroups()
        );
        self::assertSame('html', $representation->format);
        self::assertSame('<p>A <strong>CommonMark</strong> comment', $representation->body);
        self::assertNull($representation->ugroups);
        self::assertSame($representation->body, $representation->post_processed_body);
    }

    public function testItBuildsCommonMarkCommentRepresentationWithPrivateUgroups(): void
    {
        $interpreter    = ContentInterpretorStub::withInterpretedText('<p>A <strong>CommonMark</strong> comment');
        $builder        = new CommentRepresentationBuilder($interpreter);
        $representation = $builder->buildRepresentation(
            $this->buildComment('A **CommonMark** comment', CommentFormatIdentifier::COMMONMARK->value),
            [$this->buildProjectUGroup()]
        );
        self::assertSame('html', $representation->format);
        self::assertSame('<p>A <strong>CommonMark</strong> comment', $representation->body);
        self::assertNotNull($representation->ugroups);
        self::assertEquals('developers', $representation->ugroups[0]->label);
        self::assertSame($representation->body, $representation->post_processed_body);
    }

    public function testItThrowsWhenFormatIsUnknown(): void
    {
        $interpreter = ContentInterpretorStub::withInterpretedText('Blah');
        $builder     = new CommentRepresentationBuilder($interpreter);
        $this->expectException(InvalidCommentFormatException::class);
        $builder->buildRepresentation($this->buildComment('Irrelevant', 'invalid'), []);
    }

    private function buildComment(string $body, string $format): \Tracker_Artifact_Changeset_Comment
    {
        $tracker   = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(110)->build())
            ->build();
        $artifact  = ArtifactTestBuilder::anArtifact(1001)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset(10001)->ofArtifact($artifact)->build();

        return new \Tracker_Artifact_Changeset_Comment(
            23,
            $changeset,
            null,
            null,
            101,
            1234567890,
            $body,
            $format,
            0,
            []
        );
    }

    private function buildProjectUGroup(): ProjectUGroup
    {
        return new ProjectUGroup(['ugroup_id' => 112, 'name' => 'developers', 'group_id' => 101]);
    }
}
