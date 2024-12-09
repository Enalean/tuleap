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

namespace Tuleap\Tracker\REST\Artifact\Changeset\Comment;

use Project;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\InvalidCommentFormatException;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\UserIsNotAllowedToSeeUGroups;

class CommentRepresentationBuilder
{
    /**
     * @var ContentInterpretor
     */
    private $interpreter;

    public function __construct(ContentInterpretor $interpreter)
    {
        $this->interpreter = $interpreter;
    }

    /**
     * @param \ProjectUGroup[]|UserIsNotAllowedToSeeUGroups $ugroups
     * @throws InvalidCommentFormatException
     */
    public function buildRepresentation(\Tracker_Artifact_Changeset_Comment $comment, $ugroups): CommentRepresentation
    {
        $format = $comment->bodyFormat;
        if (
            $format === CommentFormatIdentifier::HTML->value
            || $format === CommentFormatIdentifier::TEXT->value
        ) {
            return new HTMLOrTextCommentRepresentation(
                $comment->body,
                $comment->getPurifiedBodyForHTML(),
                $format,
                $this->buildMinimalUserGroupRepresentation($comment->changeset->getArtifact()->getTracker()->getProject(), $ugroups)
            );
        }
        if ($format === CommentFormatIdentifier::COMMONMARK->value) {
            $interpreted = $this->interpreter->getInterpretedContentWithReferences(
                $comment->body,
                (int) $comment->changeset->getArtifact()->getTracker()->getGroupId()
            );
            return new CommonMarkCommentRepresentation(
                $interpreted,
                $comment->body,
                $this->buildMinimalUserGroupRepresentation($comment->changeset->getArtifact()->getTracker()->getProject(), $ugroups)
            );
        }

        throw new InvalidCommentFormatException($format);
    }

    /**
     * @param \ProjectUGroup[]|UserIsNotAllowedToSeeUGroups $ugroups
     * @return MinimalUserGroupRepresentation[]|null
     */
    public function buildMinimalUserGroupRepresentation(Project $project, $ugroups): ?array
    {
        if ($ugroups instanceof UserIsNotAllowedToSeeUGroups) {
            return null;
        }

        $minimal_representation_ugroups = [];

        foreach ($ugroups as $ugroup) {
            $minimal_representation_ugroups[] = new MinimalUserGroupRepresentation((int) $project->getID(), $ugroup);
        }

        return $minimal_representation_ugroups;
    }
}
