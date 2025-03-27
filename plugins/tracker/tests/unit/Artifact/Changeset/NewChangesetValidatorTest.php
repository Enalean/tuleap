<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinkToParentWithoutCurrentArtifactChangeException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewChangesetValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private \Tracker_Artifact_Changeset_FieldsValidator&\PHPUnit\Framework\MockObject\MockObject $fields_validator;
    private \Tracker_Artifact_Changeset_ChangesetDataInitializator&\PHPUnit\Framework\MockObject\MockObject $fields_initializator;
    private ParentLinkAction&\PHPUnit\Framework\MockObject\MockObject $parent_link_action;
    private \Workflow&\PHPUnit\Framework\MockObject\MockObject $workflow;
    private \PFUser $user;
    private NewChangeset $new_changeset;
    private \Tuleap\Tracker\Artifact\Artifact $artifact;
    private NewChangesetValidator $new_changeset_validator;

    private \Tracker_Artifact_Changeset&\PHPUnit\Framework\MockObject\MockObject $changeset;


    protected function setUp(): void
    {
        $this->fields_validator     = $this->createMock(\Tracker_Artifact_Changeset_FieldsValidator::class);
        $this->fields_initializator = $this->createMock(\Tracker_Artifact_Changeset_ChangesetDataInitializator::class);
        $this->parent_link_action   = $this->createMock(ParentLinkAction::class);

        $this->user      = UserTestBuilder::anActiveUser()->build();
        $this->changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $this->artifact  = ArtifactTestBuilder::anArtifact(10)->withChangesets($this->changeset)->build();

        $this->new_changeset = NewChangeset::fromFieldsDataArrayWithEmptyComment(
            $this->artifact,
            [],
            $this->user,
            1234567890,
        );

        $this->workflow = $this->createMock(\Workflow::class);

        $this->new_changeset_validator = new NewChangesetValidator(
            $this->fields_validator,
            $this->fields_initializator,
            $this->parent_link_action
        );
    }

    public function testChangesetIsInvalidWhenUserIsAnonymous(): void
    {
        $user          = UserTestBuilder::anAnonymousUser()->build();
        $new_changeset = NewChangeset::fromFieldsDataArrayWithEmptyComment(
            $this->artifact,
            [],
            $user,
            1234567890,
        );

        $this->expectException(\Tracker_Exception::class);

        $this->new_changeset_validator->validateNewChangeset(
            $new_changeset,
            null,
            $this->workflow
        );
    }

    public function testItThrowsWhenAFieldIsNotValid(): void
    {
        $this->fields_validator->method('validate')->willReturn(false);

        $GLOBALS['Response']->method('getFeedbackErrors')->willReturn([]);
        $this->expectException(FieldValidationException::class);

        $this->new_changeset_validator->validateNewChangeset(
            $this->new_changeset,
            null,
            $this->workflow
        );
    }

    public function testItThrowWhenNoChangeIsDetectedAndParentIsLinked(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);
        $this->parent_link_action->method('linkParent')->willReturn(true);
        $this->changeset->method('hasChanges')->willReturn(false);

        $this->expectException(LinkToParentWithoutCurrentArtifactChangeException::class);

        $this->new_changeset_validator->validateNewChangeset(
            $this->new_changeset,
            null,
            $this->workflow
        );
    }

    public function testItThrowsOnNoChange(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);
        $this->changeset->method('hasChanges')->willReturn(false);
        $this->parent_link_action->method('linkParent')->willReturn(false);

        $this->expectException(\Tracker_NoChangeException::class);

        $this->new_changeset_validator->validateNewChangeset(
            $this->new_changeset,
            null,
            $this->workflow
        );
    }

    public function testItValidateFields(): void
    {
        $this->fields_validator->method('validate')->willReturn(true);
        $this->changeset->method('hasChanges')->willReturn(true);

        $new_changeset = NewChangeset::fromFieldsDataArray(
            $this->artifact,
            [],
            'stuff',
            CommentFormatIdentifier::COMMONMARK,
            [],
            $this->user,
            123456789,
            new CreatedFileURLMapping(),
        );

        $this->fields_initializator->expects($this->once())->method('process');

        $this->workflow->expects($this->once())->method('validate');
        $this->workflow->expects($this->once())->method('before');
        $this->workflow->expects($this->once())->method('checkGlobalRules');

        $this->new_changeset_validator->validateNewChangeset(
            $new_changeset,
            null,
            $this->workflow
        );
    }
}
