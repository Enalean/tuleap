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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_FormElementFactory;
use Tracker_UserWithReadAllPermission;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\UserIsNotAllowedToSeeUGroups;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueTextRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\HTMLOrTextCommentRepresentation;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ChangesetRepresentationBuilder $builder;
    private UserManager&MockObject $user_manager;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private PermissionChecker&MockObject $comment_permission_checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->user_manager               = $this->createMock(\UserManager::class);
        $this->form_element_factory       = $this->createMock(\Tracker_FormElementFactory::class);
        $this->comment_permission_checker = $this->createMock(PermissionChecker::class);
        $this->builder                    = new ChangesetRepresentationBuilder(
            $this->user_manager,
            $this->form_element_factory,
            new CommentRepresentationBuilder(
                $this->createMock(ContentInterpretor::class)
            ),
            $this->comment_permission_checker,
            ProvideUserAvatarUrlStub::build(),
        );

        $user_helper = $this->createMock(\UserHelper::class);
        $user_helper->method('getUserUrl');
        $user_helper->method('getDisplayNameFromUser');
        \UserHelper::setInstance($user_helper);
    }

    #[\Override]
    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testBuildWithFieldChanges(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_field->method('userCanRead')->willReturn(true);
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'overcompensation', 'text');
        $string_field->method('getRESTValue')->willReturn($string_value);
        $int_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Integer\IntegerField::class);
        $int_field->method('userCanRead')->willReturn(true);
        $int_value = new ArtifactFieldValueFullRepresentation();
        $int_value->build(10001, 'int', 'Initial effort', 8);
        $int_field->method('getRESTValue')->willReturn($int_value);

        $this->form_element_factory->expects($this->once())->method('getUsedFieldsForREST')
            ->willReturn([$string_field, $int_field]);

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

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

        $permission_on_artifact_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField::class);
        $permission_on_artifact_field->method('userCanRead')->willReturn(true);
        $permission_on_artifact_field->method('getRESTValue')->willReturn(null);
        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_field->method('userCanRead')->willReturn(true);
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'overcompensation', 'text');
        $string_field->method('getRESTValue')->willReturn($string_value);

        $this->form_element_factory->expects($this->once())->method('getUsedFieldsForREST')
            ->willReturn([$permission_on_artifact_field, $string_field]);

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

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

        $int_field_user_can_not_read = $this->createMock(\Tuleap\Tracker\FormElement\Field\Integer\IntegerField::class);
        $int_field_user_can_not_read->method('userCanRead')->willReturn(false);
        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_field->method('userCanRead')->willReturn(true);
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'overcompensation', 'text');
        $string_field->method('getRESTValue')->willReturn($string_value);

        $this->form_element_factory->expects($this->once())->method('getUsedFieldsForREST')
            ->willReturn([$int_field_user_can_not_read, $string_field]);

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

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

        $this->comment_permission_checker->expects($this->once())
            ->method('isPrivateCommentForUser')
            ->willReturn(true);

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            $previous_changeset
        );

        self::assertNull($representation);
    }

    private function mockChangeset(): Tracker_Artifact_Changeset&MockObject
    {
        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);

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

        $changeset->method('getComment')->willReturn($comment);
        $changeset->method('getSubmittedBy')->willReturn(101);
        $changeset->method('getSubmittedOn')->willReturn(1234567890);

        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_field->method('userCanRead')->willReturn(false);

        $value = $this->createMock(\Tracker_Artifact_ChangesetValue::class);
        $value->method('getField')->willReturn($string_field);
        $value->method('hasChanged')->willReturn(true);
        $changeset->method('getValues')->willReturn([$value]);
        $changeset->method('getChangesetValuesHasChanged')->willReturn([$value]);

        return $changeset;
    }

    public function testBuildWithOnlyComments(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_COMMENTS,
            $user,
            null
        );

        $this->form_element_factory->expects($this->never())->method('getUsedFieldsForREST');
        self::assertEmpty($representation->values);
        self::assertNotEmpty($representation->last_comment);
    }

    public function testItSetsSubmittedByToNullWhenThereIsNone(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset(true, 0);

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

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
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset(true, 101, 0);

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

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

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

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

        $this->form_element_factory->expects($this->never())->method('getUsedFieldsForREST');

        $this->comment_permission_checker->expects($this->once())
            ->method('isPrivateCommentForUser')
            ->willReturn(true);

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

        $this->form_element_factory->expects($this->once())->method('getUsedFieldsForREST')
            ->willReturn([]);

        $this->comment_permission_checker->expects($this->once())
            ->method('isPrivateCommentForUser')
            ->willReturn(true);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            null
        );

        self::assertNotNull($representation->last_comment);
        self::assertInstanceOf(HTMLOrTextCommentRepresentation::class, $representation->last_comment);
        self::assertEquals('', $representation->last_comment->body);
        self::assertEquals('text', $representation->last_comment->format);
        self::assertNull($representation->last_comment->ugroups);
        self::assertEquals('', $representation->last_comment->post_processed_body);
    }

    public function testItReturnsCommentIfUserCanSeeIt(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $this->form_element_factory->expects($this->once())->method('getUsedFieldsForREST')
            ->willReturn([]);

        $project_ugroup = $this->createMock(\ProjectUGroup::class);
        $project_ugroup->method('getId')->willReturn(1);
        $project_ugroup->method('getName')->willReturn('MyGroup');
        $project_ugroup->method('getNormalizedName')->willReturn('MyGroup');

        $this->comment_permission_checker->expects($this->once())
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn([$project_ugroup]);

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

        $representation = $this->builder->buildWithFields(
            $changeset,
            \Tracker_Artifact_Changeset::FIELDS_ALL,
            $user,
            null
        );

        self::assertNotNull($representation->last_comment);
        self::assertInstanceOf(HTMLOrTextCommentRepresentation::class, $representation->last_comment);
        self::assertEquals('A text comment', $representation->last_comment->body);
        self::assertEquals('text', $representation->last_comment->format);
        self::assertNotNull($representation->last_comment->ugroups);
        self::assertEquals('MyGroup', $representation->last_comment->ugroups[0]->label);
        self::assertEquals('A text comment', $representation->last_comment->post_processed_body);
    }

    public function testBuildWithFieldsWithoutPermissions(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'overcompensation', 'text');
        $string_field
            ->method('getRESTValue')
            ->with(self::isInstanceOf(Tracker_UserWithReadAllPermission::class), $changeset)
            ->willReturn($string_value);

        $int_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Integer\IntegerField::class);
        $int_value = new ArtifactFieldValueFullRepresentation();
        $int_value->build(10001, 'int', 'Initial effort', 8);
        $int_field
            ->method('getRESTValue')
            ->with(self::isInstanceOf(Tracker_UserWithReadAllPermission::class), $changeset)
            ->willReturn($int_value);

        $this->form_element_factory->expects($this->once())->method('getUsedFieldsForREST')
            ->willReturn([$string_field, $int_field]);

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

        $representation = $this->builder->buildWithFieldValuesWithoutPermissions($changeset, $user);

        self::assertContains($int_value, $representation->values);
        self::assertContains($string_value, $representation->values);
    }

    public function testBuildWithFieldWithoutPermissionsSkipsFieldsWithNullRESTValue(): void
    {
        $user      = $this->buildUser();
        $changeset = $this->buildChangeset();

        $permission_on_artifact_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField::class);
        $permission_on_artifact_field->method('getRESTValue')->willReturn(null);
        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_value = new ArtifactFieldValueTextRepresentation(10000, 'string', 'Title', 'overcompensation', 'overcompensation', 'text');
        $string_field->method('getRESTValue')->willReturn($string_value);

        $this->form_element_factory->expects($this->once())->method('getUsedFieldsForREST')
            ->willReturn([$permission_on_artifact_field, $string_field]);

        $this->comment_permission_checker
            ->method('isPrivateCommentForUser')
            ->willReturn(false);
        $this->comment_permission_checker
            ->method('getUgroupsThatUserCanSeeOnComment')
            ->willReturn(new UserIsNotAllowedToSeeUGroups());

        $representation = $this->builder->buildWithFieldValuesWithoutPermissions($changeset, $user);

        self::assertSame([0 => $string_value], $representation->values);
    }

    private function buildChangeset(
        bool $has_comment = true,
        int $submitted_by = 101,
        int $last_modified_by = 101,
    ): \Tracker_Artifact_Changeset {
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getGroupId')->willReturn(110);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);
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
        $this->user_manager->method('getUserById')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                101 => $user,
                default => null,
            });

        return $user;
    }
}
