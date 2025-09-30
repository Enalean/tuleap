<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_Changeset_CommentDao;
use Tracker_Artifact_Changeset_ValueDao;
use Tracker_Artifact_ChangesetDao;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_ChangesetTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    private Tracker_Artifact_Changeset&MockObject $changeset;
    private Tracker_Artifact_Changeset_ValueDao&MockObject $dao;
    private PFUser $user;


    #[\Override]
    protected function setUp(): void
    {
        $this->changeset = $this->createPartialMock(Tracker_Artifact_Changeset::class, [
            'getId', 'getValueDao', 'getFormElementFactory',
        ]);
        $this->dao       = $this->createMock(Tracker_Artifact_Changeset_ValueDao::class);
        $this->user      = UserTestBuilder::buildWithDefaults();
    }

    public function testItContainsChanges(): void
    {
        $artifact      = $this->createMock(Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-with_changes') . '/';
        self::assertMatchesRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes', $this->user));
        self::assertMatchesRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes', $this->user));
        self::assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes', $this->user));
        self::assertMatchesRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes', $this->user));

        self::assertDoesNotMatchRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false, $this->user));
    }

    public function testItContainsComment(): void
    {
        $artifact      = $this->createMock(Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-with_comment') . '/';
        self::assertMatchesRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false, $this->user));
        self::assertMatchesRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes', $this->user));
        self::assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes', $this->user));
        self::assertMatchesRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes', $this->user));

        self::assertDoesNotMatchRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes', $this->user));
    }

    public function testItContainsSystemUser(): void
    {
        $artifact      = $this->createMock(Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-by_system_user') . '/';
        self::assertDoesNotMatchRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false, $this->user));
        self::assertDoesNotMatchRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes', $this->user));
        self::assertDoesNotMatchRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes', $this->user));
        self::assertDoesNotMatchRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes', $this->user));

        self::assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes', $this->user));
    }

    private function buildChangeset(
        int $id,
        Artifact $artifact,
        ?int $submitted_by,
        int $submitted_on,
        Tracker_Artifact_Changeset_Comment $comment,
    ): Tracker_Artifact_Changeset&MockObject {
        $changeset = $this->getMockBuilder(Tracker_Artifact_Changeset::class)
            ->setConstructorArgs([$id, $artifact, $submitted_by, $submitted_on, 'user@example.com'])
            ->onlyMethods([
                'getComment', 'getChangesetDao', 'getCommentDao', 'getValueDao', 'getFormElementFactory', 'getValues',
                'diffToPreviousArtifactView', 'getAvatar', 'fetchChangesetActionButtons', 'fetchImportedFromXmlData',
                'getUserLink', 'getTimeAgo', 'getFollowupContent', 'fetchFollowUp',
            ])
            ->getMock();
        $changeset->method('getComment')->willReturn($comment);

        return $changeset;
    }

    private function getEmptyComment(): Tracker_Artifact_Changeset_Comment&MockObject
    {
        $empty_comment = $this->createMock(Tracker_Artifact_Changeset_Comment::class);
        $empty_comment->method('hasEmptyBody')->willReturn(true);
        $empty_comment->method('hasEmptyBodyForUser')->willReturn(true);
        $empty_comment->method('fetchFollowUp')->willReturn(null);

        return $empty_comment;
    }

    private function getComment(): Tracker_Artifact_Changeset_Comment&MockObject
    {
        $comment = $this->createMock(Tracker_Artifact_Changeset_Comment::class);
        $comment->method('hasEmptyBody')->willReturn(false);
        $comment->method('hasEmptyBodyForUser')->willReturn(false);

        return $comment;
    }

    public function testGetValue(): void
    {
        $field = $this->createMock(DateField::class);
        $value = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);

        $this->dao->expects($this->once())->method('searchByFieldId')->willReturn(['changeset_id' => 1, 'field_id' => 2, 'id' => 3, 'has_changed' => 0]);
        $field->method('getId')->willReturn(2);
        $field->expects($this->once())->method('getChangesetValue')->willReturn($value);

        $this->changeset->method('getId')->willReturn(12);
        $this->changeset->expects($this->once())->method('getValueDao')->willReturn($this->dao);

        self::assertInstanceOf(Tracker_Artifact_ChangesetValue_Date::class, $this->changeset->getValue($field));
    }

    public function testGetChangesetValuesHasChanged(): void
    {
        $field   = $this->createMock(DateField::class);
        $value   = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);
        $factory = $this->createMock(Tracker_FormElementFactory::class);

        $this->dao->expects($this->once())->method('getAllChangedValueFromChangesetId')
            ->willReturn([
                ['field_id' => 2, 'id' => 3],
                ['field_id' => 666, 'id' => 666],
            ]);
        $field->method('getId')->willReturn(2);
        $field->expects($this->once())->method('getChangesetValue')->willReturn($value);

        $factory->method('getFieldById')->willReturnOnConsecutiveCalls($field, null);

        $this->changeset->expects($this->once())->method('getId')->willReturn(12);
        $this->changeset->expects($this->once())->method('getValueDao')->willReturn($this->dao);
        $this->changeset->method('getFormElementFactory')->willReturn($factory);

        $changesets = $this->changeset->getChangesetValuesHasChanged();
        self::assertCount(1, $changesets);
        self::assertInstanceOf(Tracker_Artifact_ChangesetValue_Date::class, $changesets[0]);
    }

    public function testDiffToPrevious(): void
    {
        $field1             = $this->createMock(DateField::class);
        $value1_previous    = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);
        $value1_current     = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);
        $field2             = $this->createMock(DateField::class);
        $value2_previous    = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);
        $value2_current     = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);
        $factory            = $this->createMock(Tracker_FormElementFactory::class);
        $artifact           = $this->createMock(Artifact::class);
        $previous_changeset = $this->createMock(Tracker_Artifact_Changeset::class);

        $current_changeset = $this->createPartialMock(Tracker_Artifact_Changeset::class, [
            'getId', 'getValueDao', 'getFormElementFactory', 'getArtifact',
        ]);

        $previous_changeset->expects($this->once())->method('getValue')->with($field1)->willReturn($value1_previous);
        $artifact->expects($this->once())->method('getPreviousChangeset')->with(66)->willReturn($previous_changeset);

        $this->dao->expects($this->once())->method('searchById')->willReturn([
            ['changeset_id' => 66, 'field_id' => 1, 'id' => 11, 'has_changed' => 1],
            ['changeset_id' => 66, 'field_id' => 2, 'id' => 21, 'has_changed' => 0],
        ]);

        $factory->method('getFieldById')->willReturnCallback(static fn(int $id) => match ($id) {
            1 => $field1,
            2 => $field2,
        });

        $field1->expects($this->once())->method('getId')->willReturn(1);
        $field1->expects($this->once())->method('getLabel')->willReturn('field1');
        $field1->expects($this->once())->method('userCanRead')->willReturn(true);
        $field1->expects($this->once())->method('getChangesetValue')->with(self::anything(), 11, 1)->willReturn($value1_current);
        $value1_previous->expects($this->never())->method('hasChanged');
        $value1_current->expects($this->once())->method('hasChanged')->willReturn(true);
        $value1_current->expects($this->once())->method('diff')->with($value1_previous, self::anything(), null)->willReturn('has changed');
        $field2->expects($this->once())->method('getId')->willReturn(2);
        $field2->expects($this->never())->method('getLabel');
        $field2->expects($this->once())->method('userCanRead')->willReturn(true);
        $field2->expects($this->once())->method('getChangesetValue')->with(self::anything(), 21, 0)->willReturn($value2_current);

        $value2_previous->expects($this->never())->method('hasChanged');
        $value2_current->expects($this->once())->method('hasChanged')->willReturn(false);

        $current_changeset->method('getId')->willReturn(66);
        $current_changeset->expects($this->once())->method('getValueDao')->willReturn($this->dao);
        $current_changeset->method('getFormElementFactory')->willReturn($factory);
        $current_changeset->expects($this->once())->method('getArtifact')->willReturn($artifact);

        $result = $current_changeset->diffToprevious();

        self::assertMatchesRegularExpression('/field1/', $result);
        self::assertDoesNotMatchRegularExpression('/field2/', $result);
    }

    public function testDisplayDiffShouldNotStripHtmlTagsInPlainTextFormat(): void
    {
        $diff   = "@@ -1 +1 @@
- Quelle est la couleur <b> du <i> cheval blanc d'Henri IV?
+ Quelle est la couleur <b> du <i> <s> cheval blanc d'Henri IV?";
        $format = 'text';
        $field  = $this->createMock(DateField::class);
        $field->method('getLabel')->willReturn('Summary');

        $changeset = new Tracker_Artifact_Changeset(1, ArtifactTestBuilder::anArtifact(45)->build(), null, null, null);
        $result    = $changeset->displayDiff($diff, $format, $field);
        self::assertMatchesRegularExpression('%Quelle est la couleur <b> du <i> <s> cheval blanc%', $result);
        self::assertMatchesRegularExpression('%Summary%', $result);
    }

    public function testItDeletesCommentsValuesAndChangeset(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);
        $comment = $this->getComment();

        $changeset_id = 1234;

        $changeset = $this->buildChangeset($changeset_id, $artifact, 101, time(), $comment);

        $changeset_dao = $this->createMock(Tracker_Artifact_ChangesetDao::class);
        $changeset_dao->expects($this->once())->method('delete')->with($changeset_id);
        $changeset->method('getChangesetDao')->willReturn($changeset_dao);

        $comment_dao = $this->createMock(Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->expects($this->once())->method('delete')->with($changeset_id);
        $changeset->method('getCommentDao')->willReturn($comment_dao);

        $value_dao = $this->createMock(Tracker_Artifact_Changeset_ValueDao::class);
        $value_dao->expects($this->once())->method('delete')->with($changeset_id);
        $changeset->method('getValueDao')->willReturn($value_dao);

        $value_dao->method('searchById')->with($changeset_id)->willReturn(
            [['id' => 1025, 'field_id' => 125], ['id' => 1026, 'field_id' => 126]]
        );

        $formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $field_text          = $this->createMock(TextField::class);
        $field_text->expects($this->once())->method('deleteChangesetValue')->with(self::anything(), 1025);
        $field_float = $this->createMock(FloatField::class);
        $field_float->expects($this->once())->method('deleteChangesetValue')->with(self::anything(), 1026);
        $formelement_factory->method('getFieldById')->willReturnCallback(static fn(int $id) => match ($id) {
            125 => $field_text,
            126 => $field_float,
        });

        $changeset->method('getFormElementFactory')->willReturn($formelement_factory);

        $changeset->delete($user);
    }

    public function testItGetNullIfNoChangesAndNoComment(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);
        $comment = $this->getEmptyComment();
        $comment->method('fetchFollowUp')->willReturn(null);

        $changeset = $this->getMockBuilder(Tracker_Artifact_Changeset::class)
            ->setConstructorArgs([1234, $artifact, 101, time(), 'user@example.com'])
            ->onlyMethods(['getValues', 'getComment'])
            ->getMock();
        $changeset->method('getComment')->willReturn($comment);

        $changeset->expects($this->once())->method('getValues')->willReturn([]);

        $follow_up_content = $changeset->getFollowUpHTML($user, $changeset);

        self::assertNull($follow_up_content);
    }

    public function testItGetFollowUpWithOnlyChanges(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);
        $comment = $this->getEmptyComment();
        $comment->method('fetchFollowUp')->willReturn(null);

        $changeset = $this->buildChangeset(1234, $artifact, 101, time(), $comment);

        $changeset->expects($this->once())->method('diffToPreviousArtifactView')->willReturn('<div></div>');

        $changeset->expects($this->once())->method('fetchFollowUp')->willReturn("<div class='tracker_followup_changes'></div>");

        $follow_up_content = $changeset->getFollowUpHTML($user, $changeset);

        self::assertStringContainsString('tracker_artifact_followup-with_changes', $follow_up_content);
        self::assertStringNotContainsString('tracker_artifact_followup-with_comments', $follow_up_content);
    }

    public function testItGetFollowUpWithOnlyComments(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);
        $comment = $this->getComment();
        $comment->method('fetchFollowUp')->willReturn('<div></div>');

        $changeset = $this->buildChangeset(1234, $artifact, 101, time(), $comment);

        $changeset->expects($this->once())->method('diffToPreviousArtifactView')->willReturn('');

        $changeset->expects($this->once())->method('fetchFollowUp')->willReturn("<div class='tracker_followup_changes'></div>");

        $follow_up_content = $changeset->getFollowUpHTML($user, $changeset);

        self::assertStringNotContainsString('tracker_artifact_followup-with_changes', $follow_up_content);
        self::assertStringContainsString('tracker_artifact_followup-with_comment', $follow_up_content);
    }

    public function testItGetEmptyFollowUpIfNoFollowUpContent(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);
        $comment = $this->getEmptyComment();

        $changeset = $this->getMockBuilder(Tracker_Artifact_Changeset::class)
            ->setConstructorArgs([1234, $artifact, 101, time(), 'user@example.com'])
            ->onlyMethods(['getComment', 'getFollowupContent'])
            ->getMock();
        $changeset->method('getComment')->willReturn($comment);

        $changeset->expects($this->once())->method('getFollowupContent')->willReturn('');

        $follow_up_content = $changeset->fetchFollowUp('', $user);

        self::assertEquals('', $follow_up_content);
    }

    public function testItGetFollowUpIfThereIsFollowUpContent(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);
        $tracker->method('getGroupId')->willReturn(173);
        $tracker->method('isNotificationStopped')->willReturn(false);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);
        $comment = $this->getEmptyComment();

        $changeset = $this->getMockBuilder(Tracker_Artifact_Changeset::class)
            ->setConstructorArgs([1234, $artifact, 101, time(), 'user@example.com'])
            ->onlyMethods([
                'getFollowupContent', 'getAvatar', 'fetchChangesetActionButtons', 'fetchImportedFromXmlData', 'getUserLink', 'getTimeAgo', 'getComment',
            ])
            ->getMock();
        $changeset->method('getComment')->willReturn($comment);

        $changeset->expects($this->once())->method('getFollowupContent')->willReturn("<div class='tracker-followup'></div>");
        $changeset->expects($this->once())->method('getAvatar')->willReturn("<div class='tracker-avatar'></div>");
        $changeset->expects($this->once())->method('fetchChangesetActionButtons')->willReturn('');
        $changeset->expects($this->once())->method('fetchImportedFromXmlData')->willReturn('');
        $changeset->expects($this->once())->method('getUserLink')->willReturn('');
        $changeset->expects($this->once())->method('getTimeAgo')->willReturn('');

        $follow_up_content = $changeset->fetchFollowUp('', $user);

        self::assertStringContainsString("<div class='tracker-followup'></div>", $follow_up_content);
    }

    public function testItGetFollowUpCommentSectionIfThereIsAtLeastFollowUpChanges(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $tracker = $this->createStub(Tracker::class);
        $tracker->method('getId')->willReturn(86);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->method('getGroupId')->willReturn(173);
        $tracker->method('isNotificationStopped')->willReturn(false);

        $artifact = ArtifactTestBuilder::anArtifact(25)->inTracker($tracker)->build();
        $comment  = $this->getEmptyComment();

        $changeset = $this->getMockBuilder(Tracker_Artifact_Changeset::class)
            ->setConstructorArgs([1234, $artifact, 101, time(), 'user@example.com'])
            ->onlyMethods([
                'getAvatar', 'fetchChangesetActionButtons', 'fetchImportedFromXmlData', 'getUserLink', 'getTimeAgo', 'getComment',
            ])
            ->getMock();
        $changeset->method('getComment')->willReturn($comment);

        $changeset->expects($this->once())->method('getAvatar')->willReturn("<div class='tracker-avatar'></div>");
        $changeset->expects($this->once())->method('fetchChangesetActionButtons')->willReturn('');
        $changeset->expects($this->once())->method('fetchImportedFromXmlData')->willReturn('');
        $changeset->expects($this->once())->method('getUserLink')->willReturn('');
        $changeset->expects($this->once())->method('getTimeAgo')->willReturn('');

        $follow_up_content = $changeset->fetchFollowUp('<div></div>', $user);

        self::assertStringContainsString('<div class="tracker_artifact_followup_comment" data-read-only-comment data-test="follow-up-comment"></div>', $follow_up_content);
    }
}
