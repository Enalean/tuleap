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

use PFUser;
use Tracker_UserWithReadAllPermission;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\User\REST\MinimalUserRepresentation;

class ChangesetRepresentationBuilder
{
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var CommentRepresentationBuilder
     */
    private $comment_builder;
    /**
     * @var PermissionChecker
     */
    private $comment_permission_checker;

    public function __construct(
        \UserManager $user_manager,
        \Tracker_FormElementFactory $form_element_factory,
        CommentRepresentationBuilder $comment_builder,
        PermissionChecker $comment_permission_checker,
    ) {
        $this->user_manager               = $user_manager;
        $this->form_element_factory       = $form_element_factory;
        $this->comment_builder            = $comment_builder;
        $this->comment_permission_checker = $comment_permission_checker;
    }

    /**
     * @throws \Tuleap\Tracker\Artifact\Changeset\Comment\InvalidCommentFormatException
     */
    public function buildWithFields(
        \Tracker_Artifact_Changeset $changeset,
        string $filter_mode,
        \PFUser $current_user,
        ?\Tracker_Artifact_Changeset $previous_changeset,
    ): ?ChangesetRepresentation {
        $last_comment = $this->getCommentOrDefaultWithNull($changeset, $current_user);
        if ($filter_mode === \Tracker_Artifact_Changeset::FIELDS_COMMENTS && $last_comment->hasEmptyBody()) {
            return null;
        }

        if (! $this->isThereVisibleChanges($changeset, $current_user, $last_comment, $previous_changeset)) {
            return null;
        }

        $field_values = ($filter_mode === \Tracker_Artifact_Changeset::FIELDS_COMMENTS)
            ? []
            : $this->getRESTFieldValues($changeset, $current_user);

        return $this->buildFromFieldValues($changeset, $last_comment, $field_values, $current_user);
    }

    private function isThereVisibleChanges(
        \Tracker_Artifact_Changeset $changeset,
        PFUser $current_user,
        \Tracker_Artifact_Changeset_Comment $last_comment,
        ?\Tracker_Artifact_Changeset $previous_changeset,
    ): bool {
        if ($previous_changeset === null) {
            return true;
        }

        if ($last_comment->hasEmptyBody() && ! $this->isThereDiffToDisplayWithPreviousChangeset($changeset, $current_user, $previous_changeset)) {
            return false;
        }

        return true;
    }

    private function isThereDiffToDisplayWithPreviousChangeset(
        \Tracker_Artifact_Changeset $changeset,
        PFUser $user,
        \Tracker_Artifact_Changeset $previous_item,
    ): bool {
        foreach ($changeset->getChangesetValuesHasChanged() as $current_changeset_value) {
            $field = $current_changeset_value->getField();
            if (! $field->userCanRead($user)) {
                continue;
            }

            $is_diff = $current_changeset_value->isThereDiffWithPreviousChangeset(
                $previous_item->getValue($field),
                $user
            );

            if ($is_diff) {
                return true;
            }
        }
        return false;
    }

    private function getRESTFieldValues(\Tracker_Artifact_Changeset $changeset, \PFUser $user): array
    {
        $values = [];
        foreach ($this->form_element_factory->getUsedFieldsForREST($changeset->getTracker()) as $field) {
            if ($field && $field->userCanRead($user)) {
                $values[] = $field->getRESTValue($user, $changeset);
            }
        }
        return array_values(array_filter($values));
    }

    /**
     * Returns a REST representation with all fields content.
     * This does not check permissions so use it with caution.
     *
     * "A great power comes with a great responsibility"
     * @throws \Tuleap\Tracker\Artifact\Changeset\Comment\InvalidCommentFormatException
     */
    public function buildWithFieldValuesWithoutPermissions(
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
    ): ChangesetRepresentation {
        //build and provide fake tracker admin user so that all artifact fields values can be read
        $fake_admin_user = new Tracker_UserWithReadAllPermission($user);

        $last_comment = $this->getCommentOrDefaultWithNullWithoutPermission($changeset);
        $field_values = $this->getRESTFieldValuesWithoutPermissions($changeset, $fake_admin_user);
        return $this->buildFromFieldValues($changeset, $last_comment, $field_values, $fake_admin_user);
    }

    private function getRESTFieldValuesWithoutPermissions(\Tracker_Artifact_Changeset $changeset, \PFUser $user): array
    {
        $values = [];
        foreach ($this->form_element_factory->getUsedFieldsForREST($changeset->getTracker()) as $field) {
            $values[] = $field->getRESTValue($user, $changeset);
        }

        return array_values(array_filter($values));
    }

    /**
     * @throws \Tuleap\Tracker\Artifact\Changeset\Comment\InvalidCommentFormatException
     */
    private function buildFromFieldValues(
        \Tracker_Artifact_Changeset $changeset,
        \Tracker_Artifact_Changeset_Comment $last_comment,
        array $values,
        PFUser $user,
    ): ChangesetRepresentation {
        $submitted_by_id      = (int) $changeset->getSubmittedBy();
        $submitted_by_user    = $this->user_manager->getUserById($submitted_by_id);
        $submitted_by_details = ($submitted_by_user !== null)
            ? MinimalUserRepresentation::build($submitted_by_user)
            : null;

        $comment_submitted_by_id   = (int) $last_comment->getSubmittedBy();
        $comment_submitted_by_user = $this->user_manager->getUserById($comment_submitted_by_id);
        $last_modified_by          = ($comment_submitted_by_user !== null)
            ? MinimalUserRepresentation::build($comment_submitted_by_user)
            : null;
        return new ChangesetRepresentation(
            (int) $changeset->getId(),
            $submitted_by_id,
            $submitted_by_details,
            (int) $changeset->getSubmittedOn(),
            $changeset->getEmail(),
            $this->comment_builder->buildRepresentation(
                $last_comment,
                $this->comment_permission_checker->getUgroupsThatUserCanSeeOnComment($user, $last_comment)
            ),
            $values,
            $last_modified_by,
            (int) $last_comment->getSubmittedOn()
        );
    }

    private function getCommentOrDefaultWithNullWithoutPermission(\Tracker_Artifact_Changeset $changeset): \Tracker_Artifact_Changeset_Comment
    {
        return $changeset->getComment() ?: new \Tracker_Artifact_Changeset_CommentNull($changeset);
    }

    private function getCommentOrDefaultWithNull(\Tracker_Artifact_Changeset $changeset, \PFUser $user): \Tracker_Artifact_Changeset_Comment
    {
        $comment = $changeset->getComment();

        if ($comment === null) {
            return new \Tracker_Artifact_Changeset_CommentNull($changeset);
        }

        if ($this->comment_permission_checker->isPrivateCommentForUser($user, $comment)) {
            return new \Tracker_Artifact_Changeset_CommentNull($changeset);
        }

        return $comment;
    }
}
