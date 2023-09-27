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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ActionButtons;

use PFUser;
use Tracker;
use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedChecker;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactMoveButtonPresenterBuilder
{
    public function __construct(
        private readonly \EventManager $event_manager,
        private readonly MoveActionAllowedChecker $move_action_allowed_checker,
    ) {
    }

    public function getMoveArtifactButton(PFUser $user, Artifact $artifact): ?ArtifactMoveButtonPresenter
    {
        if (! $artifact->getTracker()->userIsAdmin($user)) {
            return null;
        }

        $errors = [];

        $forbidden_move_error = $this->collectErrorRelatedToForbiddenMove($artifact->getTracker());
        if ($forbidden_move_error) {
            $errors[] = $forbidden_move_error;
        }

        $event = new MoveArtifactActionAllowedByPluginRetriever($artifact, $user);
        $this->event_manager->processEvent($event);

        $external_errors = $this->collectErrorsThrownByExternalPlugins($event);
        if ($external_errors) {
            $errors[] = $external_errors;
        }

        return new ArtifactMoveButtonPresenter(
            dgettext('tuleap-tracker', "Move this artifact"),
            $errors
        );
    }

    public function getMoveArtifactModal(Artifact $artifact): ArtifactMoveModalPresenter
    {
        return new ArtifactMoveModalPresenter($artifact);
    }

    private function collectErrorRelatedToForbiddenMove(Tracker $tracker): ?string
    {
        return $this->move_action_allowed_checker->checkMoveActionIsAllowedInTracker($tracker)
            ->match(
                fn () => null,
                fn (Fault $move_action_forbidden_fault) => (string) $move_action_forbidden_fault,
            );
    }

    private function collectErrorsThrownByExternalPlugins(MoveArtifactActionAllowedByPluginRetriever $event): ?string
    {
        if ($event->doesAnExternalPluginForbiddenTheMove()) {
            return $event->getErrorMessage();
        }

        return null;
    }
}
