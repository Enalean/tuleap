<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Taskboard\REST\v1;

use Tracker_Artifact;
use Tuleap\Cardwall\BackgroundColor\BackgroundColor;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

class CardRepresentation
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $tracker_id;
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $xref;
    /**
     * @var int
     */
    public $rank;
    /**
     * @var string
     */
    public $color;
    /**
     * @var string
     */
    public $background_color;
    /**
     * @var string
     */
    public $artifact_html_uri;
    /**
     * @var bool
     */
    public $has_children;
    /**
     * @var array {@type MinimalUserRepresentation}
     * @psalm-var list<MinimalUserRepresentation>
     */
    public $assignees;
    /**
     * @var MappedListValueRepresentation | null
     */
    public $mapped_list_value;
    /**
     * @var float | null
     */
    public $initial_effort;
    /**
     * @var RemainingEffortRepresentation | null
     */
    public $remaining_effort;
    /**
     * @var bool
     */
    public $is_open;
    /**
     * @var bool
     */
    public $is_collapsed;

    /**
     * @param MinimalUserRepresentation[] $assignees
     * @psalm-param list<MinimalUserRepresentation> $assignees
     */
    public function build(
        Tracker_Artifact $artifact,
        BackgroundColor $background_color,
        int $rank,
        array $assignees,
        ?MappedListValueRepresentation $mapped_list_value,
        $initial_effort,
        ?RemainingEffortRepresentation $remaining_effort,
        bool $is_collapsed
    ): void {
        $this->id                = JsonCast::toInt($artifact->getId());
        $this->tracker_id        = JsonCast::toInt($artifact->getTrackerId());
        $this->label             = $artifact->getTitle() ?? '';
        $this->xref              = $artifact->getXRef();
        $this->rank              = $rank;
        $this->color             = $artifact->getTracker()->getColor()->getName();
        $this->artifact_html_uri = $artifact->getUri();
        $this->background_color  = (string) $background_color->getBackgroundColorName();
        $this->assignees         = $assignees;
        $this->has_children      = JsonCast::toBoolean($artifact->hasChildren());
        $this->mapped_list_value = $mapped_list_value;
        $this->initial_effort    = $this->formatNumeric($initial_effort);
        $this->remaining_effort  = $remaining_effort;
        $this->is_open           = $artifact->isOpen();
        $this->is_collapsed      = $is_collapsed;
    }

    /**
     * @param $potentially_a_string_number
     *
     * @return float | null
     */
    private function formatNumeric($potentially_a_string_number)
    {
        if (! is_numeric($potentially_a_string_number)) {
            return null;
        }

        return JsonCast::toFloat($potentially_a_string_number);
    }
}
