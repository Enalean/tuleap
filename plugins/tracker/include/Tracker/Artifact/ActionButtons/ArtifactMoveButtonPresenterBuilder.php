<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use PFUser;
use Tracker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionLimitReachedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionOfArtifactsIsNotAllowedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\RetrieveActionDeletionLimit;
use Tuleap\Tracker\REST\v1\MoveArtifactSemanticFeatureFlag;

class ArtifactMoveButtonPresenterBuilder
{
    public function __construct(
        private readonly RetrieveActionDeletionLimit $deletion_limit_retriever,
        private readonly \EventManager $event_manager,
    ) {
    }

    public function getMoveArtifactButton(PFUser $user, Artifact $artifact): ?ArtifactMoveButtonPresenter
    {
        if (! $artifact->getTracker()->userIsAdmin($user)) {
            return null;
        }

        $errors = [];

        $limit_error = $this->collectErrorRelatedToDeletionLimit($user);
        if ($limit_error) {
            $errors[] = $limit_error;
        }

        $event = new MoveArtifactActionAllowedByPluginRetriever($artifact, $user);
        $this->event_manager->processEvent($event);

        if (MoveArtifactSemanticFeatureFlag::isEnabled()) {
            $semantic_error = $this->collectErrorRelatedToSemantics($artifact->getTracker(), $event);
            if ($semantic_error) {
                $errors[] = $semantic_error;
            }
        }

        $external_errors = $this->collectErrorsThrownByExternalPlugins($event);
        if ($external_errors) {
            $errors[] = $external_errors;
        }

        if (MoveArtifactSemanticFeatureFlag::isEnabled()) {
            $links_error = $this->collectErrorsRelatedToArtifactLinks($artifact, $user);
            if ($links_error) {
                $errors[] = $links_error;
            }
        }

        return new ArtifactMoveButtonPresenter(
            dgettext('tuleap-tracker', "Move this artifact"),
            $errors
        );
    }

    public function getMoveArtifactModal(Artifact $artifact)
    {
        return new ArtifactMoveModalPresenter($artifact);
    }

    private function collectErrorRelatedToDeletionLimit(PFUser $user): ?string
    {
        try {
            $this->deletion_limit_retriever->getNumberOfArtifactsAllowedToDelete($user);
        } catch (DeletionOfArtifactsIsNotAllowedException | ArtifactsDeletionLimitReachedException $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    private function collectErrorRelatedToSemantics(
        Tracker $tracker,
        MoveArtifactActionAllowedByPluginRetriever $event,
    ): ?string {
        if (
            $tracker->hasSemanticsTitle() ||
            $tracker->hasSemanticsDescription() ||
            $tracker->hasSemanticsStatus() ||
            $tracker->getContributorField() !== null ||
            $event->hasExternalSemanticDefined()
        ) {
            return null;
        }

        return dgettext("tuleap-tracker", "No semantic defined in this tracker.");
    }

    private function collectErrorsRelatedToArtifactLinks(Artifact $artifact, PFUser $user): ?string
    {
        if ($artifact->getLinkedAndReverseArtifacts($user)) {
            return dgettext("tuleap-tracker", "Artifacts with artifact links can not be moved.");
        }

        return null;
    }

    private function collectErrorsThrownByExternalPlugins(MoveArtifactActionAllowedByPluginRetriever $event): ?string
    {
        if ($event->doesAnExternalPluginForbiddenTheMove()) {
            return $event->getErrorMessage();
        }

        return null;
    }
}
