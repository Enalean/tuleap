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
use Tuleap\Reference\ReferenceString;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\User\RetrieveUserById;
use Tuleap\User\UserName;

final class ArtifactClosingReferencesHandler
{
    private const REFERENCE_HANDLING_LIMIT = 50;

    public function __construct(
        private LoggerInterface $logger,
        private ExtractReferences $reference_extractor,
        private RetrieveArtifact $artifact_retriever,
        private RetrieveUserById $user_retriever,
        private ArtifactWasClosedCache $closed_cache,
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
        $counter = 0;
        foreach ($event->text_with_potential_references as $text_with_potential_reference) {
            $reference_instances = $this->reference_extractor->extractReferences(
                $text_with_potential_reference->text,
                (int) $event->project->getID()
            );
            foreach ($reference_instances as $instance) {
                if ($counter >= self::REFERENCE_HANDLING_LIMIT) {
                    $this->logger->info(
                        sprintf(
                            'Found more than %d references, the rest will be skipped.',
                            self::REFERENCE_HANDLING_LIMIT
                        )
                    );
                    return;
                }
                $this->handleSingleReference(
                    $event,
                    $text_with_potential_reference->back_reference,
                    $workflow_user,
                    $instance,
                    $text_with_potential_reference->user_name
                );
                $counter++;
            }
        }
    }

    private function handleSingleReference(
        PotentialReferencesReceived $event,
        ReferenceString $back_reference,
        \PFUser $workflow_user,
        ReferenceInstance $reference_instance,
        UserName $user_closing_the_artifact,
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
        $artifact = $this->artifact_retriever->getArtifactById(
            (int) $reference_instance->getValue()
        );
        if (! $artifact) {
            return;
        }
        $tracker = $artifact->getTracker();
        if ((int) $tracker->getGroupId() !== (int) $event->project->getID()) {
            return;
        }
        if ($tracker->isDeleted()) {
            return;
        }
        if ($this->closed_cache->isClosed($artifact)) {
            return;
        }

        $closing_comment = ArtifactClosingCommentInCommonMarkFormat::fromParts(
            $user_closing_the_artifact->getName(),
            $closing_keyword,
            $artifact->getTracker(),
            $back_reference
        );

        $this->artifact_closer->closeArtifact(
            $artifact,
            $workflow_user,
            $closing_comment,
            BadSemanticComment::fromUser($user_closing_the_artifact)
        )->match(function () use ($artifact) {
            $this->logger->debug(sprintf('Closed artifact #%d', $artifact->getId()));
            $this->closed_cache->addClosedArtifact($artifact);
        }, function (Fault $fault) use ($artifact) {
            $this->logger->error(sprintf('Could not close artifact #%d: %s', $artifact->getId(), (string) $fault));
        });
    }
}
