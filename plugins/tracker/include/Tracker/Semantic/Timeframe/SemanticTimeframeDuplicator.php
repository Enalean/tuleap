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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Tuleap\Tracker\Semantic\IDuplicateSemantic;

class SemanticTimeframeDuplicator implements IDuplicateSemantic
{
    /**
     * @var SemanticTimeframeDao
     */
    private $dao;

    public function __construct(SemanticTimeframeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Duplicate the semantic from tracker source to tracker target
     *
     * @param int   $from_tracker_id The Id of the tracker source
     * @param int   $to_tracker_id   The Id of the tracker target
     * @param array $field_mapping   The mapping of the fields of the tracker
     *
     * @return void
     */
    public function duplicate($from_tracker_id, $to_tracker_id, array $field_mapping)
    {
        $row = $this->dao->searchByTrackerId((int) $from_tracker_id);
        if ($row === null) {
            return;
        }

        $from_start_date_field_id    = $row['start_date_field_id'];
        $from_duration_date_field_id = $row['duration_field_id'];
        $from_end_date_field_id      = $row['end_date_field_id'];

        $to_start_date_field_id = null;
        $to_duration_field_id   = null;
        $to_end_date_field_id   = null;
        foreach ($field_mapping as $mapping) {
            if ((int) $mapping['from'] === $from_start_date_field_id) {
                $to_start_date_field_id = (int) $mapping['to'];
            }
            if ((int) $mapping['from'] === $from_duration_date_field_id) {
                $to_duration_field_id = (int) $mapping['to'];
            }

            if ((int) $mapping['from'] === $from_end_date_field_id) {
                $to_end_date_field_id = (int) $mapping['to'];
            }
        }

        if ($to_start_date_field_id === null || ($to_duration_field_id === null && $to_end_date_field_id === null)) {
            return;
        }

        $this->dao->save((int) $to_tracker_id, $to_start_date_field_id, $to_duration_field_id, $to_end_date_field_id);
    }
}
