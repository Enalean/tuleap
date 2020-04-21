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

final class Tracker_Artifact_ChangesetTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
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


    protected function setUp(): void
    {
        $this->changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->dao   = \Mockery::spy(\Tracker_Artifact_Changeset_ValueDao::class);
    }

    public function testItContainsChanges(): void
    {
        $artifact      = Mockery::mock(Tracker_Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            "user@example.com",
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), "user@example.com", $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), "user@example.com", $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), "user@example.com", $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), "user@example.com", $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-with_changes') . '/';
        $this->assertMatchesRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes'));
        $this->assertMatchesRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
        $this->assertMatchesRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false));
    }

    public function testItContainsComment(): void
    {
        $artifact      = Mockery::mock(Tracker_Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            "user@example.com",
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), "user@example.com", $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), "user@example.com", $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), "user@example.com", $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), "user@example.com", $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-with_comment') . '/';
        $this->assertMatchesRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false));
        $this->assertMatchesRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
        $this->assertMatchesRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes'));
    }

    public function testItContainsSystemUser(): void
    {
        $artifact      = Mockery::mock(Tracker_Artifact::class);
        $empty_comment = $this->getEmptyComment();
        $comment       = $this->getComment();

        $changeset_with_both_changes_and_comment = $this->buildChangeset(
            2,
            $artifact,
            101,
            time(),
            "user@example.com",
            $comment
        );

        $changeset_with_changes     = $this->buildChangeset(1, $artifact, 101, time(), "user@example.com", $empty_comment);
        $changeset_by_workflowadmin = $this->buildChangeset(3, $artifact, 90, time(), "user@example.com", $comment);
        $changeset_by_anonymous     = $this->buildChangeset(4, $artifact, null, time(), "user@example.com", $comment);
        $changeset_with_comment     = $this->buildChangeset(5, $artifact, 101, time(), "user@example.com", $comment);

        $pattern = '/' . preg_quote('tracker_artifact_followup-by_system_user') . '/';
        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_comment->getFollowUpClassnames(false));
        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_with_changes->getFollowUpClassnames('The changes'));
        $this->assertDoesNotMatchRegularExpression($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertMatchesRegularExpression($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
    }

    /**
     * @return \Mockery\Mock | Tracker_Artifact_Changeset
     */
    private function buildChangeset(
        int $id,
        Tracker_artifact $artifact,
        ?int $submitted_by,
        int $submitted_on,
        string $email,
        $comment
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

        return $empty_comment;
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset_Comment
     */
    private function getComment()
    {
        $comment = Mockery::mock(Tracker_Artifact_Changeset_Comment::class);
        $comment->shouldReceive('hasEmptyBody')->andReturn(false);

        return $comment;
    }

    public function testGetValue(): void
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Date::class);

        $dar = TestHelper::arrayToDar(['changeset_id' => 1, 'field_id' => 2, 'id' => 3, 'has_changed' => 0]);
        $this->dao->shouldReceive('searchByFieldId')->once()->andReturns($dar);

        $field->shouldReceive('getId')->andReturns(2);
        $field->shouldReceive('getChangesetValue')->once()->andReturns($value);

        $this->changeset->shouldReceive('getValueDao')->once()->andReturns($this->dao);

        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_Date::class, $this->changeset->getValue($field));
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
        $artifact           = \Mockery::spy(\Tracker_Artifact::class);
        $previous_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $current_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();

        $previous_changeset->shouldReceive('getValue')->once()->with($field1)->andReturns($value1_previous);
        $previous_changeset->shouldReceive('getValue')->never()->with($field2);
        $artifact->shouldReceive('getPreviousChangeset')->once()->with(66)->andReturns($previous_changeset);

        $dar = TestHelper::arrayToDar(
            ['changeset_id' => 66, 'field_id' => 1, 'id' => 11, 'has_changed' => 1],
            ['changeset_id' => 66, 'field_id' => 2, 'id' => 21, 'has_changed' => 0]
        );
        $this->dao->shouldReceive('searchById')->once()->andReturns($dar);

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

        $current_changeset->shouldReceive('getId')->once()->andReturns(66);
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

        $changeset = new Tracker_Artifact_Changeset(null, null, null, null, null);
        $result    = $changeset->displayDiff($diff, $format, $field);
        $this->assertMatchesRegularExpression('%Quelle est la couleur <b> du <i> <s> cheval blanc%', $result);
        $this->assertMatchesRegularExpression('%Summary%', $result);
    }

    public function testItDeletesCommentsValuesAndChangeset(): void
    {
        $user = \Mockery::spy(\PFUser::class)->shouldReceive('isSuperUser')->andReturns(true)->getMock();

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($user)->andReturns(true);

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $comment = $this->getComment();

        $changeset_id = 1234;

        $changeset = $this->buildChangeset($changeset_id, $artifact, 101, time(), "user@example.com", $comment);

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
            \TestHelper::arrayToDar(['id' => 1025, 'field_id' => 125], ['id' => 1026, 'field_id' => 126])
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
}
