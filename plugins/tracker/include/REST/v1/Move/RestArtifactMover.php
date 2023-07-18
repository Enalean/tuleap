<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Move;

use Tracker;
use Tuleap\Tracker\Action\CollectDryRunTypingField;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Action\MoveArtifact;
use Tuleap\Tracker\Action\MoveArtifactByDuckTyping;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionLimitReachedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionOfArtifactsIsNotAllowedException;
use Tuleap\Tracker\Exception\MoveArtifactNotDoneException;
use Tuleap\Tracker\Exception\MoveArtifactNoValuesToProcessException;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;
use Tuleap\Tracker\Exception\MoveArtifactTargetProjectNotActiveException;
use Tuleap\Tracker\REST\v1\MoveArtifactSemanticFeatureFlag;

final class RestArtifactMover implements MoveRestArtifact
{
    public function __construct(
        private readonly MoveArtifact $move_action,
        private readonly AddPostMoveArtifactFeedback $post_move_action,
        private readonly MoveArtifactByDuckTyping $duck_typing_move,
        private readonly FeedbackFieldCollectorInterface $feedback_collector,
        private readonly CollectDryRunTypingField $collector,
    ) {
    }

    /**
     * @throws MoveArtifactNotDoneException
     * @throws MoveArtifactTargetProjectNotActiveException
     * @throws DeletionOfArtifactsIsNotAllowedException
     * @throws MoveArtifactSemanticsException
     * @throws ArtifactsDeletionLimitReachedException
     * @throws MoveArtifactNoValuesToProcessException
     */
    public function move(Tracker $source_tracker, Tracker $target_tracker, Artifact $artifact, \PFUser $user, bool $should_populate_feedback_on_success): int
    {
        if (MoveArtifactSemanticFeatureFlag::isEnabled()) {
            return $this->perfomMoveBasedOnSemantic($artifact, $target_tracker, $user, $should_populate_feedback_on_success, $source_tracker);
        }
        return $this->performMoveBasedOnDuckTyping($source_tracker, $target_tracker, $artifact, $user, $should_populate_feedback_on_success);
    }

    /**
     * @throws ArtifactsDeletionLimitReachedException
     * @throws DeletionOfArtifactsIsNotAllowedException
     * @throws MoveArtifactNotDoneException
     * @throws MoveArtifactSemanticsException
     * @throws MoveArtifactTargetProjectNotActiveException
     */
    private function perfomMoveBasedOnSemantic(Artifact $artifact, Tracker $target_tracker, \PFUser $user, bool $should_populate_feedback_on_success, Tracker $source_tracker): int
    {
        $remaining_deletions = $this->move_action->move($artifact, $target_tracker, $user, $this->feedback_collector);
        $this->populateFeedBackIfNeeded($should_populate_feedback_on_success, $source_tracker, $target_tracker, $artifact, $user);

        return $remaining_deletions;
    }

    /**
     * @throws MoveArtifactNoValuesToProcessException
     */
    private function performMoveBasedOnDuckTyping(Tracker $source_tracker, Tracker $target_tracker, Artifact $artifact, \PFUser $user, bool $should_populate_feedback_on_success): int
    {
        $field_collection = $this->collector->collect($source_tracker, $target_tracker, $artifact, $user);

        if (empty($field_collection->mapping_fields)) {
            throw new MoveArtifactNoValuesToProcessException();
        }

        $remaining_deletions = $this->duck_typing_move->move($artifact, $source_tracker, $target_tracker, $user, $field_collection);
        $this->populateFeedBackIfNeeded($should_populate_feedback_on_success, $source_tracker, $target_tracker, $artifact, $user);
        return $remaining_deletions;
    }

    private function populateFeedBackIfNeeded(bool $should_populate_feedback_on_success, Tracker $source_tracker, Tracker $target_tracker, Artifact $artifact, \PFUser $user): void
    {
        if ($should_populate_feedback_on_success) {
            $this->post_move_action->addFeedback($source_tracker, $target_tracker, $artifact, $user);
        }
    }
}
