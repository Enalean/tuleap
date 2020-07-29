<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tooltip;

/**
 * @psalm-immutable
 */
class TrackerStats
{
    /**
     * @var int
     */
    private $nb_total_artifacts;

    /**
     * @var int
     */
    private $nb_open_artifacts;

    /**
     * @var ?int
     */
    private $last_artifact_creation_date;

    /**
     * @var ?int
     */
    private $last_artifact_update_date;

    public function __construct(
        int $nb_total_artifacts,
        int $nb_open_artifacts,
        ?int $last_artifact_creation_date,
        ?int $last_artifact_update_date
    ) {
        $this->nb_total_artifacts          = $nb_total_artifacts;
        $this->nb_open_artifacts           = $nb_open_artifacts;
        $this->last_artifact_creation_date = $last_artifact_creation_date;
        $this->last_artifact_update_date   = $last_artifact_update_date;
    }

    public function getNbTotalArtifacts(): int
    {
        return $this->nb_total_artifacts;
    }

    public function getNbOpenArtifacts(): int
    {
        return $this->nb_open_artifacts;
    }

    public function getLastArtifactCreationDate(): ?int
    {
        return $this->last_artifact_creation_date;
    }

    public function getLastArtifactUpdateDate(): ?int
    {
        return $this->last_artifact_update_date;
    }
}
