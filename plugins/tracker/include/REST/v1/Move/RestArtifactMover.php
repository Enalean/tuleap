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

use Psr\Log\LoggerInterface;
use Tracker;
use Tuleap\Tracker\Action\BuildArtifactLinksMappingForDuckTypedMove;
use Tuleap\Tracker\Action\CollectDryRunTypingField;
use Tuleap\Tracker\Action\MoveArtifactByDuckTyping;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionLimitReachedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionOfArtifactsIsNotAllowedException;
use Tuleap\Tracker\Exception\MoveArtifactNotDoneException;
use Tuleap\Tracker\Exception\MoveArtifactNoValuesToProcessException;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;
use Tuleap\Tracker\Exception\MoveArtifactTargetProjectNotActiveException;

final class RestArtifactMover implements MoveRestArtifact
{
    public function __construct(
        private readonly AddPostMoveArtifactFeedback $post_move_action,
        private readonly MoveArtifactByDuckTyping $duck_typing_move,
        private readonly CollectDryRunTypingField $collector,
        private readonly BuildArtifactLinksMappingForDuckTypedMove $collect_artifact_links_for_duck_typed_move,
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
    public function move(Tracker $source_tracker, Tracker $target_tracker, Artifact $artifact, \PFUser $user, bool $should_populate_feedback_on_success, LoggerInterface $logger): void
    {
        $this->performMoveBasedOnDuckTyping($source_tracker, $target_tracker, $artifact, $user, $should_populate_feedback_on_success, $logger);
    }

    /**
     * @throws MoveArtifactNoValuesToProcessException
     */
    private function performMoveBasedOnDuckTyping(Tracker $source_tracker, Tracker $target_tracker, Artifact $artifact, \PFUser $user, bool $should_populate_feedback_on_success, LoggerInterface $logger): void
    {
        $field_collection           = $this->collector->collect($source_tracker, $target_tracker, $artifact, $user, $logger);
        $artifacts_links_collection = $this->collect_artifact_links_for_duck_typed_move->buildMapping($source_tracker, $artifact, $user);

        if (empty($field_collection->mapping_fields)) {
            throw new MoveArtifactNoValuesToProcessException();
        }

        $this->duck_typing_move->move($artifact, $source_tracker, $target_tracker, $user, $field_collection, $artifacts_links_collection, $logger);
        $this->populateFeedBackIfNeeded($should_populate_feedback_on_success, $source_tracker, $target_tracker, $artifact, $user);
    }

    private function populateFeedBackIfNeeded(bool $should_populate_feedback_on_success, Tracker $source_tracker, Tracker $target_tracker, Artifact $artifact, \PFUser $user): void
    {
        if ($should_populate_feedback_on_success) {
            $this->post_move_action->addFeedback($source_tracker, $target_tracker, $artifact, $user);
        }
    }
}
