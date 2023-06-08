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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use PFUser;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfig;
use Tuleap\Tracker\Admin\ArtifactsDeletion\UserDeletionRetriever;

class ArtifactDeletionLimitRetriever implements RetrieveActionDeletionLimit
{
    public function __construct(private ArtifactsDeletionConfig $config, private UserDeletionRetriever $user_deletion_retriever)
    {
    }

    /**
     * @throws ArtifactsDeletionLimitReachedException
     * @throws DeletionOfArtifactsIsNotAllowedException
     */
    public function getNumberOfArtifactsAllowedToDelete(PFUser $user): int
    {
        $limit = $this->config->getArtifactsDeletionLimit();
        if (! $limit) {
            throw new DeletionOfArtifactsIsNotAllowedException();
        }

        $nb_artifacts_deleted = $this->user_deletion_retriever->getNumberOfArtifactsDeletionsForUserInTimePeriod($user);

        if ($nb_artifacts_deleted >= $limit) {
            throw new ArtifactsDeletionLimitReachedException();
        }

        return $limit - $nb_artifacts_deleted;
    }
}
