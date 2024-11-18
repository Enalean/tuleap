<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders\Fields;

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactLinkFieldBuilder
{
    private int $parent_id        = 0;
    private string $shortname     = 'overjoy';
    private string $label         = 'Overjoy';
    private string $description   = 'unremunerated simility';
    private bool $is_used         = true;
    private string $scope         = 'P';
    private bool $is_required     = false;
    private string $notifications = '';
    private int $rank             = 1;
    private \Tracker $tracker;

    private function __construct(private int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(86)->build();
    }

    public static function anArtifactLinkField(int $id): self
    {
        return new self($id);
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->shortname = $name;
        return $this;
    }

    public function thatIsRequired(): self
    {
        $this->is_required = true;
        return $this;
    }

    public function withTrackerId(int $tracker_id): self
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId($tracker_id)->build();
        return $this;
    }

    public function inTracker(\Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function build(): \Tracker_FormElement_Field_ArtifactLink
    {
        $field = new \Tracker_FormElement_Field_ArtifactLink(
            $this->id,
            $this->tracker->getId(),
            $this->parent_id,
            $this->shortname,
            $this->label,
            $this->description,
            $this->is_used,
            $this->scope,
            $this->is_required,
            $this->notifications,
            $this->rank
        );
        $field->setTracker($this->tracker);
        return $field;
    }
}
