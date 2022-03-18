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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\Value\FieldsDataBuilder;

final class ArtifactUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private ArtifactUpdater $updater;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & FieldsDataBuilder
     */
    private $validator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & NewChangesetCreator
     */
    private $changeset_creator;

    protected function setUp(): void
    {
        $this->validator         = \Mockery::mock(FieldsDataBuilder::class);
        $this->changeset_creator = \Mockery::spy(NewChangesetCreator::class);
        $this->updater           = new ArtifactUpdater($this->validator, $this->changeset_creator);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);
    }

    public function testUpdateDefaultsCommentToEmptyCommonmarkFormat(): void
    {
        $artifact = \Mockery::spy(Artifact::class);
        $artifact->shouldReceive('userCanUpdate')->andReturnTrue();
        $user = UserTestBuilder::aUser()->build();

        $values      = ['Irrelevant field values'];
        $fields_data = [1000 => 'Some value'];
        $this->validator->shouldReceive('getFieldsDataOnUpdate')
            ->once()
            ->with($values, $artifact)
            ->andReturn($fields_data);

        $this->changeset_creator->shouldReceive('create')->withArgs(
            function (
                NewChangeset $new_changeset,
                PostCreationContext $context,
            ) use (
                $user,
                $fields_data,
                $artifact
            ) {
                if ($new_changeset->getArtifact() !== $artifact) {
                    return false;
                }
                if ($new_changeset->getFieldsData() !== $fields_data) {
                    return false;
                }
                if ($new_changeset->getSubmitter() !== $user) {
                    return false;
                }
                if ($context->getImportConfig()->isFromXml()) {
                    return false;
                }
                if ($context->shouldSendNotifications() !== true) {
                    return false;
                }
                $comment = $new_changeset->getComment();
                if ($comment->getBody() !== '') {
                    return false;
                }
                if ((string) $comment->getFormat() !== \Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT) {
                    return false;
                }
                return true;
            }
        )->once();

        $this->updater->update($user, $artifact, $values, null);
    }

    public function testUpdatePassesComment(): void
    {
        $artifact = \Mockery::spy(Artifact::class);
        $artifact->shouldReceive('userCanUpdate')->andReturnTrue();
        $user = UserTestBuilder::aUser()->build();

        $values      = ['Irrelevant field values'];
        $fields_data = [1000 => 'Some value'];
        $this->validator->shouldReceive('getFieldsDataOnUpdate')
            ->once()
            ->with($values, $artifact)
            ->andReturn($fields_data);

        $comment_body   = '<p>An HTML comment</p>';
        $comment_format = 'html';
        $comment        = new NewChangesetCommentRepresentation($comment_body, $comment_format);

        $this->changeset_creator->shouldReceive('create')->withArgs(
            function (
                NewChangeset $new_changeset,
                PostCreationContext $context,
            ) use (
                $comment_body,
                $user,
                $fields_data,
                $artifact
            ) {
                if ($new_changeset->getArtifact() !== $artifact) {
                    return false;
                }
                if ($new_changeset->getFieldsData() !== $fields_data) {
                    return false;
                }
                if ($new_changeset->getSubmitter() !== $user) {
                    return false;
                }
                if ($context->getImportConfig()->isFromXml()) {
                    return false;
                }
                $comment = $new_changeset->getComment();
                if ($comment->getBody() !== $comment_body) {
                    return false;
                }
                if ((string) $comment->getFormat() !== \Tracker_Artifact_Changeset_Comment::HTML_COMMENT) {
                    return false;
                }
                if ($context->shouldSendNotifications() !== true) {
                    return false;
                }
                return true;
            }
        )->once();

        $this->updater->update($user, $artifact, $values, $comment);
    }

    public function testUpdateThrowsWhenUserCannotUpdate(): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $user     = UserTestBuilder::anAnonymousUser()->build();
        $artifact = new Artifact(1, 10, 101, 1234567890, true);

        $this->updater->update($user, $artifact, []);
    }

    public function testUpdateThrowsWhenThereWasAConcurrentModification(): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(412);

        $user     = UserTestBuilder::aUser()->build();
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('userCanUpdate')->andReturnTrue();
        $artifact->shouldReceive('getLastUpdateDate')->andReturn(1500000000);

        $_SERVER['HTTP_IF_UNMODIFIED_SINCE'] = '1234567890';

        $this->updater->update($user, $artifact, []);
    }
}
