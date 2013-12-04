<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;

class Tracker_REST_Artifact_ArtifactReferenceRepresentation {
    /** @var int ID of the artifact */
    public $id;

    /** @var string URI to the artifact */
    public $uri;

    public function __construct($reference) {
        if ($reference instanceof Tracker_Artifact) {
            $this->id  = $reference->getId();
        } elseif (is_int($reference)) {
            $this->id = $reference;
        } else {
            throw new Exception('Unknown artifact reference');
        }
        $this->uri = ArtifactRepresentation::ROUTE . '/' . $this->id;
    }
}
