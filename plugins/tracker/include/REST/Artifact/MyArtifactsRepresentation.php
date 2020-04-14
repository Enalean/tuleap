<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Artifact;

use Tracker_Artifact;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

class MyArtifactsRepresentation
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $uri;
    /**
     * @var string
     */
    public $html_url;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $xref;
    /**
     * @var MinimalTrackerRepresentation
     */
    public $tracker;

    public function build(Tracker_Artifact $artifact, MinimalTrackerRepresentation $tracker_representation): self
    {
        $this->id       = JsonCast::toInt($artifact->getId());
        $this->uri      = ArtifactRepresentation::ROUTE . '/' . $artifact->getId();
        $this->html_url = $artifact->getUri();
        $this->title    = $artifact->getTitle() ?? '';
        $this->xref     = $artifact->getXRef();
        $this->tracker  = $tracker_representation;
        return $this;
    }
}
