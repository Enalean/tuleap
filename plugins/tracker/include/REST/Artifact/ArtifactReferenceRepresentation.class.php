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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\REST\JsonCast;
use Exception;

class ArtifactReferenceRepresentation
{
    /**
     * @var int ID of the artifact
     */
    public $id;

    /**
     * @var string URI to the artifact
     */
    public $uri;

    public function build($reference)
    {
        if ($reference instanceof Tracker_Artifact) {
            $this->id = JsonCast::toInt($reference->getId());
        } elseif (is_int($reference)) {
            $this->id = JsonCast::toInt($reference);
        } else {
            throw new Exception('Unknown artifact reference');
        }
        $this->uri = ArtifactRepresentation::ROUTE . '/' . $this->id;
    }
}
