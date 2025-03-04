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
use HTTPRequest;
use LogicException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_Text;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TextDiffRetrieverTest extends TestCase
{
    private Tracker_FormElement_Field_Text $field_text;
    private Tracker_Artifact_Changeset $previous_changeset;
    private Tracker_Artifact_Changeset $next_changeset;
    private BaseLayout&MockObject $layout;
    private TextDiffRetriever $text_diff_retriever;
    private ChangesetsForDiffRetriever&MockObject $changesets_for_diff_retriever;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_factory              = $this->createMock(Tracker_ArtifactFactory::class);
        $this->changesets_for_diff_retriever = $this->createMock(ChangesetsForDiffRetriever::class);
        $this->layout                        = $this->createMock(BaseLayout::class);

        $this->text_diff_retriever = new TextDiffRetriever(
            $this->artifact_factory,
            $this->changesets_for_diff_retriever,
            new DiffProcessor(new Codendi_UnifiedDiffFormatter())
        );

        $this->next_changeset     = ChangesetTestBuilder::aChangeset(30)->build();
        $this->previous_changeset = ChangesetTestBuilder::aChangeset(31)->build();
        $this->field_text         = TextFieldBuilder::aTextField(452)->build();
    }

    public function testItThrowsIfUserCanNotReadArtifact(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $request = new HTTPRequest();
        $request->setCurrentUser($user);
        $this->buildAnArtifact($user, false);

        $this->expectException(NotFoundException::class);

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                'format'       => 'text',
            ]
        );
    }

    public function testItThrowsWhenChangesetIsNotATypeText(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $request = new HTTPRequest();
        $request->setCurrentUser($user);
        $this->buildAnArtifact($user, true);

        $this->next_changeset->setFieldValue(
            $this->field_text,
            ChangesetValueIntegerTestBuilder::aValue(1, $this->next_changeset, $this->field_text)->build(),
        );

        $changesets_for_diff = new ChangesetsForDiff(
            $this->next_changeset,
            $this->field_text,
            $this->previous_changeset
        );
        $this->changesets_for_diff_retriever->method('retrieveChangesets')->willReturn($changesets_for_diff);

        $this->expectException(LogicException::class);

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                'format'       => 'text',
            ]
        );
    }

    public function testItReturnsEmptyWhenLastChangesetDoesNotExists(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $request = new HTTPRequest();
        $request->setCurrentUser($user);
        $this->buildAnArtifact($user, true);

        $previous_changeset = null;

        $this->next_changeset->setFieldValue(
            $this->field_text,
            ChangesetValueTextTestBuilder::aValue(1, $this->next_changeset, $this->field_text)->build(),
        );

        $changesets_for_diff = new ChangesetsForDiff($this->next_changeset, $this->field_text, $previous_changeset);
        $this->changesets_for_diff_retriever->method('retrieveChangesets')->willReturn($changesets_for_diff);

        $this->layout->expects(self::once())->method('sendJSON')->with('');

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                'format'       => 'text',
            ]
        );
    }

    public function testItReturnsEmptyWhenLastChangesetDidNotHaveAVValue(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $request = new HTTPRequest();
        $request->setCurrentUser($user);
        $this->buildAnArtifact($user, true);

        $this->next_changeset->setFieldValue(
            $this->field_text,
            ChangesetValueTextTestBuilder::aValue(1, $this->next_changeset, $this->field_text)->build(),
        );
        $this->previous_changeset->setFieldValue($this->field_text, null);

        $changesets_for_diff = new ChangesetsForDiff(
            $this->next_changeset,
            $this->field_text,
            $this->previous_changeset
        );
        $this->changesets_for_diff_retriever->method('retrieveChangesets')->willReturn($changesets_for_diff);

        $this->layout->expects(self::once())->method('sendJSON')->with('');

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                'format'       => 'text',
            ]
        );
    }

    public function testItReturnsTheDiffForTextFormat(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $request = new HTTPRequest();
        $request->setCurrentUser($user);
        $this->buildAnArtifact($user, true);

        $this->next_changeset->setFieldValue(
            $this->field_text,
            ChangesetValueTextTestBuilder::aValue(1, $this->next_changeset, $this->field_text)->withValue('this is a test')->build(),
        );
        $this->previous_changeset->setFieldValue(
            $this->field_text,
            ChangesetValueTextTestBuilder::aValue(1, $this->previous_changeset, $this->field_text)->withValue('this is not a test')->build(),
        );

        $changesets_for_diff = new ChangesetsForDiff(
            $this->next_changeset,
            $this->field_text,
            $this->previous_changeset
        );
        $this->changesets_for_diff_retriever->method('retrieveChangesets')->willReturn($changesets_for_diff);

        $diff      = new Codendi_Diff(['this is not a test'], ['this is a test']);
        $formatter = new Codendi_UnifiedDiffFormatter();

        $this->layout->expects(self::once())->method('sendJSON')->with(PHP_EOL . $formatter->format($diff));

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                'format'       => 'text',
            ]
        );
    }

    public function testItReturnsTheDiffForHTMLFormat(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $request = new HTTPRequest();
        $request->setCurrentUser($user);
        $this->buildAnArtifact($user, true);

        $this->next_changeset->setFieldValue(
            $this->field_text,
            ChangesetValueTextTestBuilder::aValue(1, $this->next_changeset, $this->field_text)->withValue('this is a test')->build(),
        );
        $this->previous_changeset->setFieldValue(
            $this->field_text,
            ChangesetValueTextTestBuilder::aValue(1, $this->previous_changeset, $this->field_text)->withValue('this is not a test')->build(),
        );

        $changesets_for_diff = new ChangesetsForDiff(
            $this->next_changeset,
            $this->field_text,
            $this->previous_changeset
        );
        $this->changesets_for_diff_retriever->method('retrieveChangesets')->willReturn($changesets_for_diff);

        $this->layout->expects(self::once())->method('sendJSON')
            ->with('<div class="block"><div class="difftext"><div class="original"><tt class="prefix">-</tt>this is <del>not </del>a test&nbsp;</div></div><div class="difftext"><div class="final"><tt class="prefix">+</tt>this is a test&nbsp;</div></div></div>');

        $this->text_diff_retriever->process(
            $request,
            $this->layout,
            [
                'changeset_id' => 12,
                'artifact_id'  => 123,
                'field_id'     => 567,
                'format'       => 'html',
            ]
        );
    }

    private function buildAnArtifact(PFUser $user, bool $user_can_view): void
    {
        $builder = ArtifactTestBuilder::anArtifact(123);
        if ($user_can_view) {
            $builder->userCanView($user);
        } else {
            $builder->userCannotView($user);
        }
        $artifact = $builder->build();
        $this->artifact_factory->expects(self::once())->method('getArtifactById')->with(123)->willReturn($artifact);
    }
}
