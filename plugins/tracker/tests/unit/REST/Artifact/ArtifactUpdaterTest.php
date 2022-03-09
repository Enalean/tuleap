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
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;

final class ArtifactUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private ArtifactUpdater $updater;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_REST_Artifact_ArtifactValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = \Mockery::mock(\Tracker_REST_Artifact_ArtifactValidator::class);
        $this->updater   = new ArtifactUpdater($this->validator);
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

        $artifact->shouldHaveReceived('createNewChangeset', [$fields_data, '', $user, true, \Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT]);
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

        $comment = new NewChangesetCommentRepresentation('<p>An HTML comment</p>', 'html');
        $this->updater->update($user, $artifact, $values, $comment);

        $artifact->shouldHaveReceived(
            'createNewChangeset',
            [$fields_data, '<p>An HTML comment</p>', $user, true, 'html']
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
