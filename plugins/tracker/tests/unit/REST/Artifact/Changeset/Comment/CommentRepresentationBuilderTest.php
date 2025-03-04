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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ProjectUGroup;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\InvalidCommentFormatException;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\UserIsNotAllowedToSeeUGroups;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CommentRepresentationBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ContentInterpretor
     */
    private $interpreter;

    protected function setUp(): void
    {
        $this->interpreter = \Mockery::spy(ContentInterpretor::class);
        $this->builder     = new CommentRepresentationBuilder($this->interpreter);
    }

    public function testItBuildsTextCommentRepresentation(): void
    {
        $representation = $this->builder->buildRepresentation(
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
        $representation = $this->builder->buildRepresentation(
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
        $representation = $this->builder->buildRepresentation(
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
        $representation = $this->builder->buildRepresentation(
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
        $this->interpreter->shouldReceive('getInterpretedContentWithReferences')
            ->andReturn('<p>A <strong>CommonMark</strong> comment');

        $representation = $this->builder->buildRepresentation(
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
        $this->interpreter->shouldReceive('getInterpretedContentWithReferences')
            ->andReturn('<p>A <strong>CommonMark</strong> comment');

        $representation = $this->builder->buildRepresentation(
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
        $this->expectException(InvalidCommentFormatException::class);
        $this->builder->buildRepresentation($this->buildComment('Irrelevant', 'invalid'), []);
    }

    private function buildComment(string $body, string $format): \Tracker_Artifact_Changeset_Comment
    {
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(110);
        $tracker->shouldReceive('getProject')->andReturn(ProjectTestBuilder::aProject()->build());
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getArtifact')->andReturn($artifact);
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
