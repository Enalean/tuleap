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

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

/**
 * @psalm-immutable
 */
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
     * @var string | null
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

    private function __construct(
        int $id,
        string $uri,
        string $html_url,
        ?string $title,
        string $xref,
        MinimalTrackerRepresentation $tracker
    ) {
        $this->id       = $id;
        $this->uri      = $uri;
        $this->html_url = $html_url;
        $this->title    = $title;
        $this->xref     = $xref;
        $this->tracker  = $tracker;
    }

    public static function build(Artifact $artifact, MinimalTrackerRepresentation $tracker_representation): self
    {
        return new self(
            JsonCast::toInt($artifact->getId()),
            ArtifactRepresentation::ROUTE . '/' . $artifact->getId(),
            $artifact->getUri(),
            $artifact->getTitle(),
            $artifact->getXRef(),
            $tracker_representation
        );
    }
}
