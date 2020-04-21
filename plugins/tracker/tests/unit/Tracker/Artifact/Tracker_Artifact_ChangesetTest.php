<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_CommentDao;
use Tracker_FormElement_Field_String;
use Tracker_FormElementFactory;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValuePermissionsOnArtifactFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueTextRepresentation;
use UserManager;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Artifact_ChangesetTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;

    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\MockInterface|Tracker_Artifact_Changeset_CommentDao
     */
    private $comment_dao;

    /**
     * @var Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Mockery\MockInterface|Tracker_FormElement_Field_String
     */
    private $string_field;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_PermissionsOnArtifact
     */
    private $permissions_field;

    protected function setUp(): void
    {
        parent::setUp();

        $this->changeset = Mockery::mock(Tracker_Artifact_Changeset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->user = Mockery::mock(PFUser::class);

        $this->comment_dao = Mockery::mock(Tracker_Artifact_Changeset_CommentDao::class);
        $this->changeset->shouldReceive('getCommentDao')->andReturn($this->comment_dao);

        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);

        $tracker  = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(102);
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->changeset->shouldReceive('getArtifact')->andReturn($artifact);

        $this->string_field      = Mockery::mock(Tracker_FormElement_Field_String::class);
        $this->permissions_field = Mockery::mock(\Tracker_FormElement_Field_PermissionsOnArtifact::class);

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->with($tracker)
            ->andReturn([
                $this->permissions_field,
                $this->string_field
            ]);

        $user_manager = Mockery::mock(UserManager::class);
        $user_manager->shouldReceive('getUserById')->with(138)->andReturn($this->user);
        $user_manager->shouldReceive('getCurrentUser')->andReturn($this->user);
        UserManager::setInstance($user_manager);

        $this->user->shouldReceive('getPreference')->andReturn('');
        $this->user->shouldReceive('isAnonymous')->andReturnFalse();
        $this->user->shouldReceive('getId')->andReturn(138);
        $this->user->shouldReceive('getLdapId')->andReturn(20138);
        $this->user->shouldReceive('getUserName')->andReturn('user');
        $this->user->shouldReceive('getName')->andReturn('User');
        $this->user->shouldReceive('getRealName')->andReturn('User');
        $this->user->shouldReceive('getAvatarUrl')->andReturn('link_to_avatar');
        $this->user->shouldReceive('hasAvatar')->andReturnTrue();
        $this->user->shouldReceive('isNone')->andReturnFalse();
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();

        parent::tearDown();
    }

    public function testGetFullRESTValueReturnsAllTheFieldValues()
    {
        $this->mockChangesetComment();

        $permissions_on_artifact_full_representation = Mockery::mock(ArtifactFieldValuePermissionsOnArtifactFullRepresentation::class);
        $this->permissions_field->shouldReceive('getRESTValue')
            ->once()
            ->with($this->user, $this->changeset)
            ->andReturn($permissions_on_artifact_full_representation);

        $field_value_text_representation = Mockery::mock(ArtifactFieldValueTextRepresentation::class);
        $this->string_field->shouldReceive('getRESTValue')
            ->once()
            ->with($this->user, $this->changeset)
            ->andReturn($field_value_text_representation);

        $changeset_representation = $this->changeset->getFullRESTValue($this->user);

        $expected_result = [
            0 => $permissions_on_artifact_full_representation,
            1 => $field_value_text_representation
        ];

        $this->assertSame($expected_result, $changeset_representation->values);
    }

    public function testGetFullRESTValueReturnsFieldValuesNotNullWithArrayKeyReset()
    {
        $this->mockChangesetComment();

        $this->permissions_field->shouldReceive('getRESTValue')
            ->once()
            ->with($this->user, $this->changeset)
            ->andReturnNull();

        $field_value_text_representation = Mockery::mock(ArtifactFieldValueTextRepresentation::class);
        $this->string_field->shouldReceive('getRESTValue')
            ->once()
            ->with($this->user, $this->changeset)
            ->andReturn($field_value_text_representation);

        $changeset_representation = $this->changeset->getFullRESTValue($this->user);

        $expected_result = [0 => $field_value_text_representation];

        $this->assertSame($expected_result, $changeset_representation->values);
    }

    private function mockChangesetComment(): void
    {
        $dar = Mockery::mock(LegacyDataAccessResultInterface::class);
        $row = [
            'id' => 300,
            'comment_type_id' => null,
            'canned_response_id' => null,
            'submitted_by' => 138,
            'submitted_on' => 1565339978,
            'body' => '',
            'body_format' => 'text',
            'parent_id' => 0,
        ];
        $dar->shouldReceive('valid')->andReturnTrue();
        $dar->shouldReceive('getRow')->andReturn($row);

        $this->comment_dao->shouldReceive('searchLastVersion')->andReturn($dar);
    }
}
