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
use Tracker_UserWithReadAllPermission;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\UserIsNotAllowedToSeeUGroups;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueTextRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\HTMLOrTextCommentRepresentation;

final class ChangesetRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
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
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PermissionChecker
     */
    private $comment_permission_checker;

    protected function setUp(): void
    {
        $this->user_manager               = \Mockery::mock(\UserManager::class);
        $this->form_element_factory       = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->comment_permission_checker = \Mockery::mock(PermissionChecker::class);
        $this->builder                    = new ChangesetRepresentationBuilder(
            $this->user_manager,
            $this->form_element_factory,
            new CommentRepresentationBuilder(
                \Mockery::spy(ContentInterpretor::class)
            ),
            $this->comment_permission_checker
        );

        $this->comment_permission_checker
            ->shouldReceive('isPrivateCommentForUser')
            ->andReturnFalse()
            ->byDefault();

        $this->comment_permission_checker
            ->shouldReceive('getUgroupsThatUserCanSeeOnComment')
            ->andReturn(new UserIsNotAllowedToSeeUGroups())
            ->byDefault();

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

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            null
        );

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

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            null
        );

        self::assertSame([0 => $string_value], $representation->values);
    }

    public function testBuildWithFieldsSkipsFieldsUserCanNotRead(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $int_field_user_can_not_read = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $int_field_user_can_not_read->shouldReceive('userCanRead')->andReturnFalse();
        $string_field = \Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('userCanRead')->andReturnTrue();
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'text');
        $string_field->shouldReceive('getRESTValue')->andReturn($string_value);

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->andReturn([$int_field_user_can_not_read, $string_field]);

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            null
        );

        self::assertSame([0 => $string_value], $representation->values);
    }

    public function testBuildWithFieldReturnsNullIfCommentIsPrivateAndNoVisibleChangesAndFilterModeIsOnAll(): void
    {
        $user               = $this->buildUser();
        $changeset          = $this->mockChangeset();
        $previous_changeset = $this->mockChangeset();

        $this->comment_permission_checker
            ->shouldReceive('isPrivateCommentForUser')
            ->once()
            ->andReturnTrue();

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            $previous_changeset
        );

        self::assertNull($representation);
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Artifact_Changeset
     */
    private function mockChangeset()
    {
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $comment = new \Tracker_Artifact_Changeset_Comment(
            201,
            $changeset,
            null,
            null,
            101,
            1234567890,
            'A text comment',
            'text',
            0,
            []
        );

        $changeset->shouldReceive('getComment')->andReturn($comment);
        $changeset->shouldReceive('getSubmittedBy')->andReturn(101);
        $changeset->shouldReceive('getSubmittedOn')->andReturn(1234567890);

        $string_field = \Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('userCanRead')->andReturnFalse();

        $value = \Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $value->shouldReceive('getField')->andReturn($string_field);
        $value->shouldReceive('hasChanged')->andReturnTrue();
        $changeset->shouldReceive('getValues')->andReturn([$value]);
        $changeset->shouldReceive('getChangesetValuesHasChanged')->andReturn([$value]);

        return $changeset;
    }

    public function testBuildWithOnlyComments(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user,
            null
        );

        $this->form_element_factory->shouldNotHaveReceived('getUsedFieldsForREST');
        self::assertEmpty($representation->values);
        self::assertNotEmpty($representation->last_comment);
    }

    public function testItSetsSubmittedByToNullWhenThereIsNone(): void
    {
        $user = $this->buildUser();
        $this->user_manager->shouldReceive('getUserById')->with(0)->andReturnNull();
        $changeset = $this->buildChangeset(true, 0);

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user,
            null
        );
        self::assertNull($representation->submitted_by_details);
    }

    public function testItSetsLastModifiedByToNullWhenThereIsNone(): void
    {
        $user = $this->buildUser();
        $this->user_manager->shouldReceive('getUserById')->with(0)->andReturnNull();
        $changeset = $this->buildChangeset(true, 101, 0);

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user,
            null
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
            $user,
            null
        );

        self::assertNull($representation);
    }

    public function testItReturnsNullIfUserCanNotSeeTheCommentAndFilterModeIsOnFieldsComments(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')->never();

        $this->comment_permission_checker
            ->shouldReceive('isPrivateCommentForUser')
            ->once()
            ->andReturnTrue();

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user,
            null
        );

        self::assertNull($representation);
    }

    public function testItReturnsEmptyCommentIfUserCanNotSeeItAndFilterModeIsOnFieldsAll(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->andReturn([]);

        $this->comment_permission_checker
            ->shouldReceive('isPrivateCommentForUser')
            ->once()
            ->andReturnTrue();

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            null
        );

        self::assertNotNull($representation->last_comment);
        self::assertInstanceOf(HTMLOrTextCommentRepresentation::class, $representation->last_comment);
        self::assertEquals("", $representation->last_comment->body);
        self::assertEquals("text", $representation->last_comment->format);
        self::assertNull($representation->last_comment->ugroups);
        self::assertEquals("", $representation->last_comment->post_processed_body);
    }

    public function testItReturnsCommentIfUserCanSeeIt(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')
            ->once()
            ->andReturn([]);

        $project_ugroup = \Mockery::mock(\ProjectUGroup::class, ['getId' => 1, 'getName' => 'MyGroup', 'getNormalizedName' => 'MyGroup']);

        $this->comment_permission_checker
            ->shouldReceive('getUgroupsThatUserCanSeeOnComment')
            ->once()
            ->andReturn([$project_ugroup]);

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            null
        );

        self::assertNotNull($representation->last_comment);
        self::assertInstanceOf(HTMLOrTextCommentRepresentation::class, $representation->last_comment);
        self::assertEquals("A text comment", $representation->last_comment->body);
        self::assertEquals("text", $representation->last_comment->format);
        self::assertNotNull($representation->last_comment->ugroups);
        self::assertEquals('MyGroup', $representation->last_comment->ugroups[0]->label);
        self::assertEquals("A text comment", $representation->last_comment->post_processed_body);
    }

    public function testBuildWithFieldsWithoutPermissions(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $string_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'text');
        $string_field
            ->method('getRESTValue')
            ->with(self::isInstanceOf(Tracker_UserWithReadAllPermission::class), $changeset)
            ->willReturn($string_value);

        $int_field = $this->createMock(\Tracker_FormElement_Field_Integer::class);
        $int_value = new ArtifactFieldValueFullRepresentation();
        $int_value->build(10001, 'int', 'Initial effort', 8);
        $int_field
            ->method('getRESTValue')
            ->with(self::isInstanceOf(Tracker_UserWithReadAllPermission::class), $changeset)
            ->willReturn($int_value);

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
        int $last_modified_by = 101,
    ): \Tracker_Artifact_Changeset {
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(110);
        $tracker->shouldReceive('getProject')->andReturn(ProjectTestBuilder::aProject()->build());
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
            0,
            []
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
