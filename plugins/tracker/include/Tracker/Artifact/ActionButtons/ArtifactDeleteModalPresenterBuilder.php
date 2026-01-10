<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Tracker\Admin\ArtifactsDeletion\ConfigurationArtifactsDeletion;
use Tuleap\Tracker\Admin\ArtifactsDeletion\RetrieveUserDeletionForLastDay;
use Tuleap\Tracker\Artifact\Artifact;

final readonly class ArtifactDeleteModalPresenterBuilder
{
    public function __construct(
        private ArtifactDeletionCSRFSynchronizerTokenProvider $token_provider,
        private ConfigurationArtifactsDeletion $config,
        private RetrieveUserDeletionForLastDay $user_deletion_retriever,
    ) {
    }

    public function getDeleteArtifactModal(\PFUser $user, Artifact $artifact): ?ArtifactDeleteModalPresenter
    {
        if (! $artifact->getTracker()->userIsAdmin($user)) {
            return null;
        }

        return new ArtifactDeleteModalPresenter(
            $artifact,
            CSRFSynchronizerTokenPresenter::fromToken($this->token_provider->getToken($artifact)),
            $this->config->getArtifactsDeletionLimit(),
            $this->user_deletion_retriever->getNumberOfArtifactsDeletionsForUserInTimePeriod($user),
        );
    }
}
