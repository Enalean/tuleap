<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
class MinimalArtifactRepresentation
{
    public const ROUTE = 'artifacts';

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
    public $xref;
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
    public $badge_color;
    /**
     * @var string
     */
    public $submission_date;

    private function __construct(
        int $id,
        string $xref,
        string $html_url,
        string $title,
        string $badge_color,
        string $submission_date
    ) {
        $this->id              = $id;
        $this->uri             = self::ROUTE . '/' . $this->id;
        $this->xref            = $xref;
        $this->html_url        = $html_url;
        $this->title           = $title;
        $this->badge_color     = $badge_color;
        $this->submission_date = $submission_date;
    }

    public static function build(Artifact $artifact): self
    {
        return new self(
            JsonCast::toInt($artifact->getId()),
            $artifact->getXRef(),
            $artifact->getUri(),
            $artifact->getTitle() ?? '',
            $artifact->getTracker()->getColor()->getName(),
            JsonCast::toDate($artifact->getSubmittedOn())
        );
    }
}
