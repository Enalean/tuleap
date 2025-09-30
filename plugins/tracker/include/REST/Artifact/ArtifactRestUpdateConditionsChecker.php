<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Tuleap\Tracker\Artifact\Artifact;

final class ArtifactRestUpdateConditionsChecker implements CheckArtifactRestUpdateConditions
{
    /**
     * @throws RestException
     */
    #[\Override]
    public function checkIfArtifactUpdateCanBePerformedThroughREST(\PFUser $user, Artifact $artifact): void
    {
        if (! $artifact->userCanUpdate($user)) {
            throw new RestException(403, 'You have not the permission to update this artifact');
        }

        if ($this->clientWantsToUpdateLatestVersion() && ! $this->isUpdatingLatestVersion($artifact)) {
            throw new RestException(
                412,
                'Artifact has been modified since you last requested it. Please edit the latest version'
            );
        }
    }

    private function clientWantsToUpdateLatestVersion(): bool
    {
        return (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_MATCH']));
    }

    private function isUpdatingLatestVersion(Artifact $artifact): bool
    {
        $valid_unmodified = true;
        $valid_match      = true;

        if (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {
            $client_version = strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);
            $last_version   = $artifact->getLastUpdateDate();

            $valid_unmodified = ($last_version == $client_version);
        }

        if (isset($_SERVER['HTTP_IF_MATCH'])) {
            $client_version = $_SERVER['HTTP_IF_MATCH'];
            $last_version   = $artifact->getVersionIdentifier();

            $valid_match = ($last_version == $client_version);
        }

        return ($valid_unmodified && $valid_match);
    }
}
