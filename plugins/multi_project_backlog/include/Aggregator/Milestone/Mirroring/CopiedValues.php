<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

final class CopiedValues
{
    /**
     * @var \Tracker_Artifact_ChangesetValue_String
     * @psalm-readonly
     */
    private $title_value;
    /**
     * @var int
     * @psalm-readonly
     */
    private $submitted_on;
    /**
     * @var int
     * @psalm-readonly
     */
    private $source_artifact_id;

    public function __construct(
        \Tracker_Artifact_ChangesetValue_String $title_value,
        int $submitted_on,
        int $source_artifact_id
    ) {
        $this->title_value        = $title_value;
        $this->submitted_on       = $submitted_on;
        $this->source_artifact_id = $source_artifact_id;
    }

    /**
     * @psalm-mutation-free
     */
    public function getTitleValue(): \Tracker_Artifact_ChangesetValue_String
    {
        return $this->title_value;
    }

    /**
     * @return int UNIX timestamp
     * @psalm-mutation-free
     */
    public function getSubmittedOn(): int
    {
        return $this->submitted_on;
    }

    /**
     * @psalm-mutation-free
     */
    public function getArtifactId(): int
    {
        return $this->source_artifact_id;
    }
}
