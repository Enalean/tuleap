<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Closure;

use Psr\Log\LoggerInterface;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\NeverThrow\Fault;
use Tuleap\Reference\ExtractReferences;
use Tuleap\Reference\ReferenceInstance;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveViewableArtifact;
use Tuleap\User\RetrieveUserById;

final class ArtifactClosingReferencesHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private ExtractReferences $reference_extractor,
        private RetrieveViewableArtifact $artifact_retriever,
        private RetrieveUserById $user_retriever,
        private ArtifactCloser $artifact_closer,
    ) {
    }

    /**
     * @throws \UserNotExistException
     */
    public function handlePotentialReferencesReceived(PotentialReferencesReceived $event): void
    {
        $workflow_user = $this->user_retriever->getUserById(\Tracker_Workflow_WorkflowUser::ID);
        if (! $workflow_user) {
            throw new \UserNotExistException('Tracker Workflow Manager does not exist, unable to close artifacts');
        }
        $reference_instances = $this->reference_extractor->extractReferences(
            $event->text_with_potential_references,
            (int) $event->project->getID()
        );
        foreach ($reference_instances as $instance) {
            $this->handleSingleReference($event, $workflow_user, $instance);
        }
    }

    private function handleSingleReference(
        PotentialReferencesReceived $event,
        \PFUser $workflow_user,
        ReferenceInstance $reference_instance,
    ): void {
        if ($reference_instance->getReference()->getNature() !== Artifact::REFERENCE_NATURE) {
            return;
        }
        if ((int) $event->project->getID() !== (int) $reference_instance->getReference()->getGroupId()) {
            return;
        }
        $closing_keyword = ClosingKeyword::fromString($reference_instance->getContextWord());
        if (! $closing_keyword) {
            return;
        }
        $artifact = $this->artifact_retriever->getArtifactByIdUserCanView($event->user, (int) $reference_instance->getValue());
        if (! $artifact) {
            return;
        }

        $closing_comment = ArtifactClosingCommentInCommonMarkFormat::fromParts(
            '@' . $event->user->getUserName(),
            $closing_keyword,
            $artifact->getTracker(),
            $event->back_reference
        );

        $this->artifact_closer->closeArtifact(
            $artifact,
            $workflow_user,
            $closing_comment,
            BadSemanticComment::fromUser($event->user)
        )->match(function () use ($artifact) {
            $this->logger->debug(sprintf('Closed artifact #%d', $artifact->getId()));
        }, function (Fault $fault) use ($artifact) {
            $this->logger->error(sprintf('Could not close artifact #%d: %s', $artifact->getId(), (string) $fault));
        });
    }
}
