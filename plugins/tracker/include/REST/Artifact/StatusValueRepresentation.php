<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
final class StatusValueRepresentation
{
    /**
     * @var string the semantic status value
     */
    public string $value = '';
    /**
     * @var string | null the color of status value
     */
    public ?string $color = null;

    private function __construct(string $value, ?string $color)
    {
        $this->value = $value;
        $this->color = $color;
    }

    public static function buildFromArtifact(Artifact $artifact, \PFUser $user): self
    {
        $semantic_status = \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::load($artifact->getTracker());
        return new self($artifact->getStatus(), $semantic_status->getColor($artifact->getLastChangeset(), $user));
    }

    public static function buildFromValues(string $value, ?string $color): self
    {
        return new self($value, $color);
    }
}
