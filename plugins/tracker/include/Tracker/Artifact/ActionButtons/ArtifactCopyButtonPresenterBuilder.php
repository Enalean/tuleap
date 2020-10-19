<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactCopyButtonPresenterBuilder
{
    public function getCopyArtifactButton(PFUser $user, Artifact $artifact)
    {
        if ($user->isLoggedIn() && ! $this->isAlreadyCopyingArtifact()) {
            return new ArtifactCopyButtonPresenter(
                dgettext('tuleap-tracker', 'Duplicate this artifact'),
                dgettext('tuleap-tracker', 'Duplicate this artifact'),
                TRACKER_BASE_URL . '/?func=copy-artifact&aid=' . $artifact->getId()
            );
        }
    }

    private function isAlreadyCopyingArtifact()
    {
        return strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL . '/?func=copy-artifact') === 0;
    }
}
