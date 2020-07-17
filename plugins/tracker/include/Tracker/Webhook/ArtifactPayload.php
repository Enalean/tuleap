<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use Tracker_Artifact_Changeset;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\Webhook\Payload;

class ArtifactPayload implements Payload
{
    /**
     * @var Tracker_Artifact_Changeset
     */
    private $last_changeset;
    /**
     * @var array
     */
    private $payload;

    public function __construct(Tracker_Artifact_Changeset $last_changeset)
    {
        $this->last_changeset = $last_changeset;
    }

    /**
     * @return array
     */
    private function buildPayload(Tracker_Artifact_Changeset $last_changeset)
    {
        $user               = $last_changeset->getSubmitter();
        $previous_changeset = $last_changeset->getArtifact()->getPreviousChangeset($last_changeset->getId());

        $last_changeset_content     = $last_changeset->getFullRESTValue($user);
        $previous_changeset_content = null;
        if ($previous_changeset !== null) {
            $previous_changeset_content = $previous_changeset->getFullRESTValue($user);
        }

        $user_representation = MinimalUserRepresentation::build($user);

        return [
            'action'   => $previous_changeset === null ? 'create' : 'update',
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
        if ($this->payload === null) {
            $this->payload = $this->buildPayload($this->last_changeset);
        }
        return $this->payload;
    }
}
