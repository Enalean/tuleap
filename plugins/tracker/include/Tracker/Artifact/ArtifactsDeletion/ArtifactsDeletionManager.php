<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use DateTimeImmutable;
use PFUser;
use SystemEventDao;
use Tracker_Artifact;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfig;

class ArtifactsDeletionManager
{
    /**
     * @var ArtifactsDeletionConfig
     */
    private $config;
    /**
     * @var SystemEventDao
     */
    private $dao;
    /**
     * @var ArtifactDeletor
     */
    private $artifact_deletor;

    public function __construct(
        ArtifactsDeletionConfig $config,
        ArtifactsDeletionDAO $dao,
        ArtifactDeletor $artifact_deletor
    ) {
        $this->config           = $config;
        $this->dao              = $dao;
        $this->artifact_deletor = $artifact_deletor;
    }

    /**
     * @return int The remaining number of artifacts allowed to delete
     * @throws ArtifactsDeletionLimitReachedException
     */
    public function deleteArtifact(
        Tracker_artifact $artifact,
        PFUser $user
    ) {
        $window_start         = new DateTimeImmutable('-1day');
        $limit                = $this->config->getArtifactsDeletionLimit();
        $nb_artifacts_deleted = (int) $this->dao->searchNumberOfArtifactsDeletionsForUserInTimePeriod(
            $user->getId(),
            $window_start->getTimestamp()
        );

        if ($nb_artifacts_deleted >= (int) $limit) {
            throw new ArtifactsDeletionLimitReachedException();
        }

        $this->artifact_deletor->delete($artifact, $user);
        $this->dao->recordDeletionForUser($user->getId(), time());

        return $limit - ($nb_artifacts_deleted + 1);
    }
}
