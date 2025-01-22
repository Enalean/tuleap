<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Artifact\View;

use Codendi_Request;
use PFUser;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * Represents an artifact or a specific information of it
 */
abstract readonly class TrackerArtifactView
{
    public function __construct(protected Artifact $artifact, protected Codendi_Request $request, protected PFUser $user)
    {
    }

    public function getURL(): string
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'aid'  => $this->artifact->getId(),
                'view' => $this->getIdentifier(),
            ]
        );
    }

    abstract public function getTitle(): string;

    abstract public function getIdentifier(): string;

    abstract public function fetch(): string;
}
