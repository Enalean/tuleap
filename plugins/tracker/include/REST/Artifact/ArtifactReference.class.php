<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
 */
class ArtifactReference
{

    /**
     * @var int ID of the milestone {@type int} {@required true}
     */
    public $id;

    /**
     * @var string URI of the milestone {@type string} {@required false}
     */
    public $uri;

    /**
     * @var \Tuleap\Tracker\REST\TrackerReference {@type \Tuleap\Tracker\REST\TrackerReference} {@required false}
     */
    public $tracker;

    /**
     * @var Artifact
     */
    private $artifact;

    protected function __construct(Artifact $artifact, \Tracker $tracker, string $format = '')
    {
        $this->id  = JsonCast::toInt($artifact->getId());
        $this->uri = ArtifactRepresentation::ROUTE . '/' . $this->id;

        if ($format) {
            $this->uri = $this->uri . "?values_format=$format";
        }

        $this->tracker = TrackerReference::build($tracker);

        $this->artifact = clone $artifact;
    }

    public static function build(Artifact $artifact, string $format = ''): ArtifactReference
    {
        return new self($artifact, $artifact->getTracker(), $format);
    }

    public function getArtifact()
    {
        return $this->artifact;
    }
}
