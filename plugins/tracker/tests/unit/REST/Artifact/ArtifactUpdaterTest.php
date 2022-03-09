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
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;

final class ArtifactUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private ArtifactUpdater $updater;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & \Tracker_REST_Artifact_ArtifactValidator
     */
    private $validator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & \Tracker_Artifact_Changeset_NewChangesetCreator
     */
    private $changeset_creator;

    protected function setUp(): void
    {
        $this->validator         = \Mockery::mock(\Tracker_REST_Artifact_ArtifactValidator::class);
        $this->changeset_creator = \Mockery::spy(\Tracker_Artifact_Changeset_NewChangesetCreator::class);
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

        $this->updater->update($user, $artifact, $values, null);

        $expected_comment_body = '';
        $this->changeset_creator->shouldHaveReceived(
            'create',
            [
                $artifact,
                $fields_data,
                $expected_comment_body,
                $user,
                \Mockery::type('int'),
                true,
                \Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT,
                \Mockery::type(CreatedFileURLMapping::class),
                \Mockery::type(TrackerNoXMLImportLoggedConfig::class),
                [],
            ]
        );
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

        $this->updater->update($user, $artifact, $values, $comment);

        $this->changeset_creator->shouldHaveReceived(
            'create',
            [
                $artifact,
                $fields_data,
                $comment_body,
                $user,
                \Mockery::type('int'),
                true,
                $comment_format,
                \Mockery::type(CreatedFileURLMapping::class),
                \Mockery::type(TrackerNoXMLImportLoggedConfig::class),
                [],
            ]
        );
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
