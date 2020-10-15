<?php
/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\TextDiff;

use Codendi_Diff;
use Codendi_UnifiedDiffFormatter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\NotFoundException;

final class TextDiffRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_Text
     */
    private $field_text;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Artifact_Changeset
     */
    private $previous_changeset;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Artifact_Changeset
     */
    private $next_changeset;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BaseLayout
     */
    private $layout;

    /**
     * @var TextDiffRetriever
     */
    private $text_diff_retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ChangesetsForDiffRetriever
     */
    private $changesets_for_diff_retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_factory              = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->changesets_for_diff_retriever = \Mockery::mock(ChangesetsForDiffRetriever::class);

        $this->layout = \Mockery::mock(BaseLayout::class);

        $this->text_diff_retriever = new TextDiffRetriever(
            $this->artifact_factory,
            $this->changesets_for_diff_retriever,
            new DiffProcessor(new \Codendi_UnifiedDiffFormatter())
        );

        $this->next_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $this->previous_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $this->field_text = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
    }

    public function testItThrowsIfUserCanNotReadArtifact(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->getAnArtifact(123, $user, false);

        $this->expectException(NotFoundException::class);

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                "format"       => "text"
            ]
        );
    }

    public function testItThrowsWhenChangesetIsNotATypeText(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->getAnArtifact(123, $user, true);

        $next_changeset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $this->next_changeset->shouldReceive('getValue')->andReturn($next_changeset_value);

        $changesets_for_diff = new ChangesetsForDiff(
            $this->next_changeset,
            $this->field_text,
            $this->previous_changeset
        );
        $this->changesets_for_diff_retriever->shouldReceive('retrieveChangesets')->andReturn($changesets_for_diff);

        $this->expectException(\LogicException::class);

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                "format"       => "text"
            ]
        );
    }

    public function testItReturnsEmptyWhenLastChangesetDoesNotExists(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->getAnArtifact(123, $user, true);

        $previous_changeset = null;

        $next_changeset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $this->next_changeset->shouldReceive('getValue')->andReturn($next_changeset_value);

        $changesets_for_diff = new ChangesetsForDiff($this->next_changeset, $this->field_text, $previous_changeset);
        $this->changesets_for_diff_retriever->shouldReceive('retrieveChangesets')->andReturn($changesets_for_diff);

        $this->layout->shouldReceive('sendJSON')->once()->with("");

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                "format"       => "text"
            ]
        );
    }

    public function testItReturnsEmptyWhenLastChangesetDidNotHaveAVValue(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->getAnArtifact(123, $user, true);

        $next_changeset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $this->next_changeset->shouldReceive('getValue')->andReturn($next_changeset_value);

        $this->previous_changeset->shouldReceive('getValue')->andReturnNull();

        $changesets_for_diff = new ChangesetsForDiff(
            $this->next_changeset,
            $this->field_text,
            $this->previous_changeset
        );
        $this->changesets_for_diff_retriever->shouldReceive('retrieveChangesets')->andReturn($changesets_for_diff);

        $this->layout->shouldReceive('sendJSON')->once()->with("");

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                "format"       => "text"
            ]
        );
    }

    public function testItReturnsTheDiffForTextFormat(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->getAnArtifact(123, $user, true);

        $next_changeset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $this->next_changeset->shouldReceive('getValue')->andReturn($next_changeset_value);
        $next_changeset_value->shouldReceive('getText')->andReturn("this is a test");

        $previous_changeset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $this->previous_changeset->shouldReceive('getValue')->andReturn($previous_changeset_value);
        $previous_changeset_value->shouldReceive('getText')->andReturn("this is not a test");

        $changesets_for_diff = new ChangesetsForDiff(
            $this->next_changeset,
            $this->field_text,
            $this->previous_changeset
        );
        $this->changesets_for_diff_retriever->shouldReceive('retrieveChangesets')->andReturn($changesets_for_diff);

        $diff      = new Codendi_Diff(["this is not a test"], ["this is a test"]);
        $formatter = new Codendi_UnifiedDiffFormatter();

        $this->layout->shouldReceive('sendJSON')->once()->with(PHP_EOL . $formatter->format($diff));

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                "format"       => "text"
            ]
        );
    }

    public function testItReturnsTheDiffForHTMLFormat(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->getAnArtifact(123, $user, true);

        $next_changeset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $this->next_changeset->shouldReceive('getValue')->andReturn($next_changeset_value);
        $next_changeset_value->shouldReceive('getText')->andReturn("this is a test");

        $previous_changeset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Text::class);
        $this->previous_changeset->shouldReceive('getValue')->andReturn($previous_changeset_value);
        $previous_changeset_value->shouldReceive('getText')->andReturn("this is not a test");

        $changesets_for_diff = new ChangesetsForDiff(
            $this->next_changeset,
            $this->field_text,
            $this->previous_changeset
        );
        $this->changesets_for_diff_retriever->shouldReceive('retrieveChangesets')->andReturn($changesets_for_diff);

        $next_changeset_value->shouldReceive('getFormattedDiff')->once()->andReturn("this is a formatted diff");

        $this->layout->shouldReceive('sendJSON')->once()->with("this is a formatted diff");

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                "format"       => "html"
            ]
        );
    }

    private function getAnArtifact(int $artifact_id, \PFUser $user, bool $user_can_view): void
    {
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->once()
            ->with($artifact_id)
            ->andReturn($artifact);

        $artifact->shouldReceive('userCanView')->with($user)->once()->andReturn($user_can_view);
    }
}
