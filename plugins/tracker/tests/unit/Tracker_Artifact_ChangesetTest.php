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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_ChangesetTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\Mock|Tracker_Artifact_Changeset
     */
    private $changeset;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset_ValueDao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;


    protected function setUp(): void
    {
        $this->changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->dao  = \Mockery::spy(\Tracker_Artifact_Changeset_ValueDao::class);
        $this->user = Mockery::mock(PFUser::class);
    }

    public function testItContainsChanges(): void
    {
        $artifact      = Mockery::mock(Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            'user@example.com',
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), 'user@example.com', $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), 'user@example.com', $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), 'user@example.com', $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), 'user@example.com', $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-with_changes') . '/';
        $this->assertMatchesRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes', $this->user));
        $this->assertMatchesRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes', $this->user));
        $this->assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes', $this->user));
        $this->assertMatchesRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes', $this->user));

        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false, $this->user));
    }

    public function testItContainsComment(): void
    {
        $artifact      = Mockery::mock(Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            'user@example.com',
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), 'user@example.com', $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), 'user@example.com', $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), 'user@example.com', $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), 'user@example.com', $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-with_comment') . '/';
        $this->assertMatchesRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false, $this->user));
        $this->assertMatchesRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes', $this->user));
        $this->assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes', $this->user));
        $this->assertMatchesRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes', $this->user));

        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes', $this->user));
    }

    public function testItContainsSystemUser(): void
    {
        $artifact      = Mockery::mock(Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            'user@example.com',
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), 'user@example.com', $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), 'user@example.com', $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), 'user@example.com', $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), 'user@example.com', $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-by_system_user') . '/';
        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false, $this->user));
        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes', $this->user));
        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes', $this->user));
        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes', $this->user));

        $this->assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes', $this->user));
    }

    /**
     * @return \Mockery\Mock | Tracker_Artifact_Changeset
     */
    private function buildChangeset(
        int $id,
        Artifact $artifact,
        ?int $submitted_by,
        int $submitted_on,
        string $email,
        $comment,
    ) {
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class, [$id, $artifact, $submitted_by, $submitted_on, $email])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $changeset->shouldReceive('getComment')->andReturn($comment);

        return $changeset;
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset_Comment
     */
    private function getEmptyComment()
    {
        $empty_comment = Mockery::mock(Tracker_Artifact_Changeset_Comment::class);
        $empty_comment->shouldReceive('hasEmptyBody')->andReturn(true);
        $empty_comment->shouldReceive('hasEmptyBodyForUser')->andReturn(true);
        $empty_comment->shouldReceive('fetchFollowUp')->andReturn(null);

        return $empty_comment;
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset_Comment
     */
    private function getComment()
    {
        $comment = Mockery::mock(Tracker_Artifact_Changeset_Comment::class);
        $comment->shouldReceive('hasEmptyBody')->andReturn(false);
        $comment->shouldReceive('hasEmptyBodyForUser')->andReturn(false);

        return $comment;
    }

    public function testGetValue(): void
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Date::class);

        $this->dao->shouldReceive('searchByFieldId')->once()->andReturns(['changeset_id' => 1, 'field_id' => 2, 'id' => 3, 'has_changed' => 0]);
        $field->shouldReceive('getId')->andReturns(2);
        $field->shouldReceive('getChangesetValue')->once()->andReturns($value);

        $this->changeset->shouldReceive('getId')->andReturns(12);
        $this->changeset->shouldReceive('getValueDao')->once()->andReturns($this->dao);

        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_Date::class, $this->changeset->getValue($field));
    }

    public function testGetChangesetValuesHasChanged(): void
    {
        $field   = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $value   = $this->createMock(\Tracker_Artifact_ChangesetValue_Date::class);
        $factory = $this->createMock(Tracker_FormElementFactory::class);

        $this->dao
            ->shouldReceive('getAllChangedValueFromChangesetId')
            ->once()
            ->andReturns([
                ['field_id' => 2, 'id' => 3],
                ['field_id' => 666, 'id' => 666],
            ]);
        $field->method('getId')->willReturn(2);
        $field->expects($this->once())->method('getChangesetValue')->willReturn($value);

        $factory->method('getFieldById')->willReturnOnConsecutiveCalls($field, null);

        $this->changeset->shouldReceive('getId')->once()->andReturns(12);
        $this->changeset->shouldReceive('getValueDao')->once()->andReturns($this->dao);
        $this->changeset->shouldReceive('getFormElementFactory')->andReturn($factory);

        $changesets = $this->changeset->getChangesetValuesHasChanged();
        $this->assertCount(1, $changesets);
        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_Date::class, $changesets[0]);
    }

    public function testDiffToPrevious(): void
    {
        $field1             = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $value1_previous    = \Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value1_current     = \Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $field2             = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $value2_previous    = \Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value2_current     = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $fact               = \Mockery::spy(\Tracker_FormElementFactory::class);
        $artifact           = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $previous_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $current_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();

        $previous_changeset->shouldReceive('getValue')->once()->with($field1)->andReturns($value1_previous);
        $previous_changeset->shouldReceive('getValue')->never()->with($field2);
        $artifact->shouldReceive('getPreviousChangeset')->once()->with(66)->andReturns($previous_changeset);

        $this->dao->shouldReceive('searchById')->once()->andReturns([
            ['changeset_id' => 66, 'field_id' => 1, 'id' => 11, 'has_changed' => 1],
            ['changeset_id' => 66, 'field_id' => 2, 'id' => 21, 'has_changed' => 0],
        ]);

        $fact->shouldReceive('getFieldById')->with(1)->andReturns($field1);
        $fact->shouldReceive('getFieldById')->with(2)->andReturns($field2);

        $field1->shouldReceive('getId')->once()->andReturns(1);
        $field1->shouldReceive('getLabel')->once()->andReturns('field1');
        $field1->shouldReceive('userCanRead')->once()->andReturns(true);
        $field1->shouldReceive('getChangesetValue')->once()->with(Mockery::any(), 11, 1)->andReturns($value1_current);
        $value1_previous->shouldReceive('hasChanged')->never();
        $value1_current->shouldReceive('hasChanged')->once()->andReturns(true);
        $value1_current->shouldReceive('diff')->once()->with($value1_previous, Mockery::any(), null)->andReturns(
            'has changed'
        );
        $field2->shouldReceive('getId')->once()->andReturns(2);
        $field2->shouldReceive('getLabel')->never();
        $field2->shouldReceive('userCanRead')->once()->andReturns(true);
        $field2->shouldReceive('getChangesetValue')->once()->with(Mockery::any(), 21, 0)->andReturns($value2_current);

        $value2_previous->shouldReceive('hasChanged')->never();
        $value2_current->shouldReceive('hasChanged')->once()->andReturns(false);
        $value2_current->shouldReceive('diff')->never();

        $current_changeset->shouldReceive('getId')->andReturns(66);
        $current_changeset->shouldReceive('getValueDao')->once()->andReturns($this->dao);
        $current_changeset->shouldReceive('getFormElementFactory')->andReturns($fact);
        $current_changeset->shouldReceive('getArtifact')->once()->andReturns($artifact);

        $result = $current_changeset->diffToprevious();

        $this->assertMatchesRegularExpression('/field1/', $result);
        $this->assertDoesNotMatchRegularExpression('/field2/', $result);
    }

    public function testDisplayDiffShouldNotStripHtmlTagsInPlainTextFormat(): void
    {
        $diff   = "@@ -1 +1 @@
- Quelle est la couleur <b> du <i> cheval blanc d'Henri IV?
+ Quelle est la couleur <b> du <i> <s> cheval blanc d'Henri IV?";
        $format = 'text';
        $field  = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $field->shouldReceive('getLabel')->andReturns('Summary');

        $changeset = new Tracker_Artifact_Changeset(1, null, null, null, null);
        $result    = $changeset->displayDiff($diff, $format, $field);
        $this->assertMatchesRegularExpression('%Quelle est la couleur <b> du <i> <s> cheval blanc%', $result);
        $this->assertMatchesRegularExpression('%Summary%', $result);
    }

    public function testItDeletesCommentsValuesAndChangeset(): void
    {
        $user = \Mockery::spy(\PFUser::class)->shouldReceive('isSuperUser')->andReturns(true)->getMock();

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($user)->andReturns(true);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $comment = $this->getComment();

        $changeset_id = 1234;

        $changeset = $this->buildChangeset($changeset_id, $artifact, 101, time(), 'user@example.com', $comment);

        $changeset_dao = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $changeset_dao->shouldReceive('delete')->with($changeset_id)->once();
        $changeset->shouldReceive('getChangesetDao')->andReturns($changeset_dao);

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('delete')->with($changeset_id)->once();
        $changeset->shouldReceive('getCommentDao')->andReturns($comment_dao);

        $value_dao = \Mockery::spy(\Tracker_Artifact_Changeset_ValueDao::class);
        $value_dao->shouldReceive('delete')->with($changeset_id)->once();
        $changeset->shouldReceive('getValueDao')->andReturns($value_dao);

        $value_dao->shouldReceive('searchById')->with($changeset_id)->andReturns(
            [['id' => 1025, 'field_id' => 125], ['id' => 1026, 'field_id' => 126]]
        );

        $formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $field_text          = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $field_text->shouldReceive('deleteChangesetValue')->with(Mockery::any(), 1025)->once();
        $formelement_factory->shouldReceive('getFieldById')->with(125)->andReturns($field_text);
        $field_float = \Mockery::spy(\Tracker_FormElement_Field_Float::class);
        $field_float->shouldReceive('deleteChangesetValue')->with(Mockery::any(), 1026)->once();
        $formelement_factory->shouldReceive('getFieldById')->with(126)->andReturns($field_float);

        $changeset->shouldReceive('getFormElementFactory')->andReturns($formelement_factory);

        $changeset->delete($user);
    }

    public function testItGetNullIfNoChangesAndNoComment(): void
    {
        $user = \Mockery::spy(\PFUser::class)->shouldReceive('isSuperUser')->andReturns(true)->getMock();

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($user)->andReturns(true);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $comment = $this->getEmptyComment();
        $comment->shouldReceive('fetchFollowUp')->andReturn(null);

        $changeset = $this->buildChangeset(1234, $artifact, 101, time(), 'user@example.com', $comment);

        $changeset->shouldReceive('getValues')->once()->andReturn([]);

        $follow_up_content = $changeset->getFollowUpHTML($user, $changeset);

        self::assertNull($follow_up_content);
    }

    public function testItGetFollowUpWithOnlyChanges(): void
    {
        $user = \Mockery::spy(\PFUser::class)->shouldReceive('isSuperUser')->andReturns(true)->getMock();

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($user)->andReturns(true);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $comment = $this->getEmptyComment();
        $comment->shouldReceive('fetchFollowUp')->andReturn(null);

        $changeset = $this->buildChangeset(1234, $artifact, 101, time(), 'user@example.com', $comment);

        $changeset
            ->shouldReceive('diffToPreviousArtifactView')
            ->once()
            ->andReturn('<div></div>');

        $changeset
            ->shouldReceive('fetchFollowUp')
            ->once()
            ->andReturn("<div class='tracker_followup_changes'></div>");

        $follow_up_content = $changeset->getFollowUpHTML($user, $changeset);

        self::assertStringContainsString('tracker_artifact_followup-with_changes', $follow_up_content);
        self::assertStringNotContainsString('tracker_artifact_followup-with_comments', $follow_up_content);
    }

    public function testItGetFollowUpWithOnlyComments(): void
    {
        $user = \Mockery::spy(\PFUser::class)->shouldReceive('isSuperUser')->andReturns(true)->getMock();

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($user)->andReturns(true);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $comment = $this->getComment();
        $comment->shouldReceive('fetchFollowUp')->andReturn('<div></div>');

        $changeset = $this->buildChangeset(1234, $artifact, 101, time(), 'user@example.com', $comment);

        $changeset
            ->shouldReceive('diffToPreviousArtifactView')
            ->once()
            ->andReturn('');

        $changeset
            ->shouldReceive('fetchFollowUp')
            ->once()
            ->andReturn("<div class='tracker_followup_changes'></div>");

        $follow_up_content = $changeset->getFollowUpHTML($user, $changeset);

        self::assertStringNotContainsString('tracker_artifact_followup-with_changes', $follow_up_content);
        self::assertStringContainsString('tracker_artifact_followup-with_comment', $follow_up_content);
    }

    public function testItGetEmptyFollowUpIfNoFollowUpContent(): void
    {
        $user = \Mockery::spy(\PFUser::class)->shouldReceive('isSuperUser')->andReturns(true)->getMock();

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($user)->andReturns(true);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $comment = $this->getEmptyComment();

        $changeset = $this->buildChangeset(1234, $artifact, 101, time(), 'user@example.com', $comment);

        $changeset
            ->shouldReceive('getFollowupContent')
            ->once()
            ->andReturn('');

        $follow_up_content = $changeset->fetchFollowUp('', $user);

        self::assertEquals('', $follow_up_content);
    }

    public function testItGetFollowUpIfThereIsFollowUpContent(): void
    {
        $user = \Mockery::spy(\PFUser::class)->shouldReceive('isSuperUser')->andReturns(true)->getMock();

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($user)->andReturns(true);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $comment = $this->getEmptyComment();

        $changeset = $this->buildChangeset(1234, $artifact, 101, time(), 'user@example.com', $comment);

        $changeset
            ->shouldReceive('getFollowupContent')
            ->once()
            ->andReturn("<div class='tracker-followup'></div>");

        $changeset
            ->shouldReceive('getAvatar')
            ->once()
            ->andReturn("<div class='tracker-avatar'></div>");
        $changeset
            ->shouldReceive('fetchChangesetActionButtons')
            ->once()
            ->andReturn('');
        $changeset
            ->shouldReceive('fetchImportedFromXmlData')
            ->once()
            ->andReturn('');
        $changeset
            ->shouldReceive('getUserLink')
            ->once()
            ->andReturn('');
        $changeset
            ->shouldReceive('getTimeAgo')
            ->once()
            ->andReturn('');

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

        $changeset = $this->buildChangeset(1234, $artifact, 101, time(), 'user@example.com', $comment);

        $changeset
            ->shouldReceive('getAvatar')
            ->once()
            ->andReturn("<div class='tracker-avatar'></div>");
        $changeset
            ->shouldReceive('fetchChangesetActionButtons')
            ->once()
            ->andReturn('');
        $changeset
            ->shouldReceive('fetchImportedFromXmlData')
            ->once()
            ->andReturn('');
        $changeset
            ->shouldReceive('getUserLink')
            ->once()
            ->andReturn('');
        $changeset
            ->shouldReceive('getTimeAgo')
            ->once()
            ->andReturn('');

        $follow_up_content = $changeset->fetchFollowUp('<div></div>', $user);

        self::assertStringContainsString('<div class="tracker_artifact_followup_comment" data-read-only-comment data-test="follow-up-comment"></div>', $follow_up_content);
    }
}
