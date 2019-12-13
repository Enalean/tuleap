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

declare(strict_types = 1);

class Tracker_Artifact_ChangesetTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\Mock
     */
    private $changeset;

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
        $this->assertRegExp($pattern, $changeset_with_changes->getFollowUpClassnames('The changes'));
        $this->assertRegExp($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertRegExp($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
        $this->assertRegExp($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertNotRegExp($pattern, $changeset_with_comment->getFollowUpClassnames(false));
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
        $this->assertRegExp($pattern, $changeset_with_comment->getFollowUpClassnames(false));
        $this->assertRegExp($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertRegExp($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
        $this->assertRegExp($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertNotRegExp($pattern, $changeset_with_changes->getFollowUpClassnames('The changes'));
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
        $this->assertNotRegExp($pattern, $changeset_with_comment->getFollowUpClassnames(false));
        $this->assertNotRegExp($pattern, $changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertNotRegExp($pattern, $changeset_with_changes->getFollowUpClassnames('The changes'));
        $this->assertNotRegExp($pattern, $changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertRegExp($pattern, $changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
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
}
