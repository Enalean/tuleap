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

namespace Tuleap\Tracker\Webhook;

use PFUser;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\Webhook\Payload;

class ArtifactPayload implements Payload
{

    /**
     * @var array
     */
    private $payload;

    public function __construct(Tracker_Artifact $artifact, PFUser $user, $action)
    {
        $this->payload = $this->buildPayload($artifact, $user, $action);
    }

    /**
     * @return array
     */
    private function buildPayload(Tracker_Artifact $artifact, PFUser $user, $action)
    {
        $last_changeset     = $artifact->getLastChangeset();
        $previous_changeset = $artifact->getPreviousChangeset($last_changeset->getId());

        $last_changeset_content     = $last_changeset->getFullRESTValue($user);
        $previous_changeset_content = null;
        if ($previous_changeset !== null) {
            $previous_changeset_content = $previous_changeset->getFullRESTValue($user);
        }

        $user_representation = new MinimalUserRepresentation();
        $user_representation->build($user);

        return [
            'action'   => $action,
            'user'     => $user_representation,
            'current'  => $last_changeset_content,
            'previous' => $previous_changeset_content
        ];
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
