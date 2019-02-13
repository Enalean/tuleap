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
 */

namespace Tuleap\Baseline\REST;

class SimplifiedBaselineRepresentation
{

    /**
     * @var string|null
     */
    public $artifact_title;

    /**
     * @var string|null
     */
    public $artifact_description;

    /**
     * @var string|null
     */
    public $artifact_status;

    /**
     * @var int
     */
    public $last_modification_date_before_baseline_date;

    public function __construct(
        ?string $artifact_title,
        ?string $artifact_description,
        ?string $artifact_status,
        int $last_modification_date_before_baseline_date
    )
    {
        $this->artifact_title                              = $artifact_title;
        $this->artifact_description                        = $artifact_description;
        $this->artifact_status                             = $artifact_status;
        $this->last_modification_date_before_baseline_date = $last_modification_date_before_baseline_date;
    }
}
