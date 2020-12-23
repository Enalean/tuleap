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

namespace Tuleap\Tracker\REST\Artifact\Changeset;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueTextRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;

final class ChangesetRepresentationBuilderTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ChangesetRepresentationBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->user_manager         = \Mockery::mock(\UserManager::class);
        $this->form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->builder              = new ChangesetRepresentationBuilder(
            $this->user_manager,
            $this->form_element_factory,
            new CommentRepresentationBuilder()
        );

        \UserHelper::setInstance(\Mockery::spy(\UserHelper::class));
    }

    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testBuildWithFieldChanges(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $string_field = \Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('userCanRead')->andReturnTrue();
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'text');
        $string_field->shouldReceive('getRESTValue')->andReturn($string_value);
        $int_field = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $int_field->shouldReceive('userCanRead')->andReturnTrue();
        $int_value = new ArtifactFieldValueFullRepresentation();
        $int_value->build(10001, 'int', 'Initial effort', 8);
        $int_field->shouldReceive('getRESTValue')->andReturn($int_value);

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->andReturn([$string_field, $int_field]);

        $representation = $this->builder->buildWithFields($changeset, \Tracker_Artifact_Changeset::FIELDS_ALL, $user);

        self::assertSame(24, $representation->id);
        self::assertSame(101, $representation->submitted_by);
        self::assertNotNull($representation->submitted_by_details);
        self::assertNotEmpty($representation->submitted_on);
        self::assertNull($representation->email);
        self::assertNotEmpty($representation->last_comment);
        self::assertContains($int_value, $representation->values);
        self::assertContains($string_value, $representation->values);
        self::assertNotNull($representation->last_modified_by);
        self::assertNotEmpty($representation->last_modified_date);
    }

    public function testBuildWithFieldSkipsFieldsWithNullRESTValue(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $permission_on_artifact_field = \Mockery::mock(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $permission_on_artifact_field->shouldReceive('userCanRead')->andReturnTrue();
        $permission_on_artifact_field->shouldReceive('getRESTValue')->andReturnNull();
        $string_field = \Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('userCanRead')->andReturnTrue();
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'text');
        $string_field->shouldReceive('getRESTValue')->andReturn($string_value);

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->andReturn([$permission_on_artifact_field, $string_field]);

        $representation = $this->builder->buildWithFields($changeset, \Tracker_Artifact_Changeset::FIELDS_ALL, $user);

        self::assertSame([0 => $string_value], $representation->values);
    }

    public function testBuildWithFieldsSkipsFieldsUserCantRead(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $int_field_user_cant_read = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $int_field_user_cant_read->shouldReceive('userCanRead')->andReturnFalse();
        $string_field = \Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('userCanRead')->andReturnTrue();
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'text');
        $string_field->shouldReceive('getRESTValue')->andReturn($string_value);

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->andReturn([$int_field_user_cant_read, $string_field]);

        $representation = $this->builder->buildWithFields($changeset, \Tracker_Artifact_Changeset::FIELDS_ALL, $user);

        self::assertSame([0 => $string_value], $representation->values);
    }

    public function testBuildWithOnlyComments(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user
        );

        $this->form_element_factory->shouldNotHaveReceived('getUsedFieldsForREST');
        self::assertEmpty($representation->values);
        self::assertNotEmpty($representation->last_comment);
    }

    public function testItSetsSubmittedByToNullWhenThereIsNone(): void
    {
        $user      = $this->buildUser();
        $this->user_manager->shouldReceive('getUserById')->with(0)->andReturnNull();
        $changeset = $this->buildChangeset(true, 0);

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user
        );
        self::assertNull($representation->submitted_by_details);
    }

    public function testItSetsLastModifiedByToNullWhenThereIsNone(): void
    {
        $user      = $this->buildUser();
        $this->user_manager->shouldReceive('getUserById')->with(0)->andReturnNull();
        $changeset = $this->buildChangeset(true, 101, 0);

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user
        );
        self::assertNull($representation->last_modified_by);
    }

    public function testItReturnsNullWhenInCommentsModeAndCommentIsEmpty(): void
    {
        $user      = UserTestBuilder::aUser()->withId(101)->build();
        $changeset = $this->buildChangeset(false);

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user
        );

        self::assertNull($representation);
    }

    public function testBuildWithFieldsWithoutPermissions(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $string_field = \Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'text');
        $string_field->shouldReceive('getRESTValue')->andReturn($string_value);
        $int_field = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $int_value = new ArtifactFieldValueFullRepresentation();
        $int_value->build(10001, 'int', 'Initial effort', 8);
        $int_field->shouldReceive('getRESTValue')->andReturn($int_value);

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->andReturn([$string_field, $int_field]);

        $representation = $this->builder->buildWithFieldValuesWithoutPermissions($changeset, $user);

        self::assertContains($int_value, $representation->values);
        self::assertContains($string_value, $representation->values);
    }

    public function testBuildWithFieldWithoutPermissionsSkipsFieldsWithNullRESTValue(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $permission_on_artifact_field = \Mockery::mock(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $permission_on_artifact_field->shouldReceive('getRESTValue')->andReturnNull();
        $string_field = \Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'text');
        $string_field->shouldReceive('getRESTValue')->andReturn($string_value);

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->andReturn([$permission_on_artifact_field, $string_field]);

        $representation = $this->builder->buildWithFieldValuesWithoutPermissions($changeset, $user);

        self::assertSame([0 => $string_value], $representation->values);
    }

    private function buildChangeset(
        bool $has_comment = true,
        int $submitted_by = 101,
        int $last_modified_by = 101
    ): \Tracker_Artifact_Changeset {
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(110);
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(24, $artifact, $submitted_by, 1234567890, null);
        $comment   = new \Tracker_Artifact_Changeset_Comment(
            201,
            $changeset,
            null,
            null,
            $last_modified_by,
            1234567890,
            ($has_comment) ? 'A text comment' : '',
            'text',
            0
        );
        $changeset->setLatestComment($comment);
        return $changeset;
    }

    private function buildUser(): \PFUser
    {
        $user = UserTestBuilder::aUser()->withId(101)->build();
        $this->user_manager->shouldReceive('getUserById')
            ->with(101)
            ->andReturn($user);
        return $user;
    }
}
