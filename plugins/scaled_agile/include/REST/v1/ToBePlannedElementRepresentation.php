<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\REST\v1;

/**
 * @psalm-immutable
 */
final class ToBePlannedElementRepresentation
{
    /**
     * @var string
     */
    public $tracker_name;
    /**
     * @var int
     */
    public $artifact_id;
    /**
     * @var string
     */
    public $artifact_title;

    public function __construct(string $tracker_name, int $artifact_id, string $artifact_title)
    {
        $this->tracker_name = $tracker_name;
        $this->artifact_id = $artifact_id;
        $this->artifact_title = $artifact_title;
    }

    public function getTrackerName(): string
    {
        return $this->tracker_name;
    }

    public function getArtifactId(): int
    {
        return $this->artifact_id;
    }

    public function getArtifactTitle(): string
    {
        return $this->artifact_title;
    }
}
