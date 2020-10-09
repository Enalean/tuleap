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

use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_Artifact_ChangesetValue_Numeric;

final class CopiedValues
{
    /**
     * @var \Tracker_Artifact_ChangesetValue_String
     * @psalm-readonly
     */
    private $title_value;
    /**
     * @var \Tracker_Artifact_ChangesetValue_Text
     * @psalm-readonly
     */
    private $description_value;
    /**
     * @var \Tracker_Artifact_ChangesetValue_List
     * @psalm-readonly
     */
    private $status_value;
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
    /**
     * @var Tracker_Artifact_ChangesetValue_Date
     */
    private $start_date_value;
    /**
     * @var Tracker_Artifact_ChangesetValue_Numeric| Tracker_Artifact_ChangesetValue_Date
     */
    private $end_period_value;

    /**
     * @param \Tracker_Artifact_ChangesetValue_Numeric| \Tracker_Artifact_ChangesetValue_Date $end_period_value
     */
    public function __construct(
        \Tracker_Artifact_ChangesetValue_String $title_value,
        \Tracker_Artifact_ChangesetValue_Text $description_value,
        \Tracker_Artifact_ChangesetValue_List $status_value,
        int $submitted_on,
        int $source_artifact_id,
        Tracker_Artifact_ChangesetValue_Date $start_date_value,
        Tracker_Artifact_ChangesetValue $end_period_value
    ) {
        $this->title_value        = $title_value;
        $this->description_value  = $description_value;
        $this->status_value       = $status_value;
        $this->submitted_on       = $submitted_on;
        $this->source_artifact_id = $source_artifact_id;
        $this->start_date_value   = $start_date_value;
        $this->end_period_value   = $end_period_value;
    }

    /**
     * @psalm-mutation-free
     */
    public function getTitleValue(): \Tracker_Artifact_ChangesetValue_String
    {
        return $this->title_value;
    }

    /**
     * @psalm-mutation-free
     */
    public function getDescriptionValue(): \Tracker_Artifact_ChangesetValue_Text
    {
        return $this->description_value;
    }

    /**
     * @psalm-mutation-free
     */
    public function getStatusValue(): \Tracker_Artifact_ChangesetValue_List
    {
        return $this->status_value;
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

    /**
     * @psalm-mutation-free
     */
    public function getStartDateValue(): Tracker_Artifact_ChangesetValue_Date
    {
        return $this->start_date_value;
    }

    /**
     * @psalm-mutation-free
     * @return Tracker_Artifact_ChangesetValue_Numeric| Tracker_Artifact_ChangesetValue_Date
     */
    public function getEndPeriodValue(): Tracker_Artifact_ChangesetValue
    {
        return $this->end_period_value;
    }
}
