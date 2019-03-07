<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline;

use DateTime;
use Tracker_Artifact;

class SimplifiedBaseline
{
    /** @var Tracker_Artifact */
    private $milestone;

    /** @var string|null */
    private $title;

    /** @var string|null */
    private $description;

    /** @var string|null */
    private $status;

    /** @var DateTime */
    private $last_modification_date_before_baseline_date;

    public function __construct(
        Tracker_Artifact $milestone,
        ?string $title,
        ?string $description,
        ?string $status,
        DateTime $last_modification_date_before_baseline_date
    ) {
        $this->milestone                                   = $milestone;
        $this->title                                       = $title;
        $this->description                                 = $description;
        $this->status                                      = $status;
        $this->last_modification_date_before_baseline_date = $last_modification_date_before_baseline_date;
    }

    public function getMilestone(): Tracker_Artifact
    {
        return $this->milestone;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getLastModificationDateBeforeBaselineDate(): DateTime
    {
        return $this->last_modification_date_before_baseline_date;
    }
}
