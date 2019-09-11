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
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\FormElement\Burnup;

use Tracker_Artifact;

class ElementsCount
{
    /**
     * @var int
     */
    private $closed_elements;
    /**
     * @var int
     */
    private $total_elements;
    /**
     * @var int[]
     */
    private $already_seen_artifacts;

    public function __construct(int $total_elements, int $closed_elements, array $already_seen_artifacts)
    {
        $this->total_elements         = $total_elements;
        $this->closed_elements        = $closed_elements;
        $this->already_seen_artifacts = $already_seen_artifacts;
    }

    public function getTotalElements(): int
    {
        return $this->total_elements;
    }

    public function getClosedElements(): int
    {
        return $this->closed_elements;
    }

    public function getAlreadySeenArtifacts(): array
    {
        return $this->already_seen_artifacts;
    }

    public function isArtifactAlreadyParsed(Tracker_Artifact $artifact): bool
    {
        return in_array((int) $artifact->getId(), $this->already_seen_artifacts, true);
    }
}
