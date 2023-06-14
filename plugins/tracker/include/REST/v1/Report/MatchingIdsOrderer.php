<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Report;

class MatchingIdsOrderer
{
    public function __construct(private readonly \Tracker_Artifact_PriorityDao $artifact_priority_dao)
    {
    }

    public function orderMatchingIdsByGlobalRank(array $matching_ids): array
    {
        if (! array_key_exists('id', $matching_ids) || ! array_key_exists('last_changeset_id', $matching_ids)) {
            throw new \LogicException();
        }

        if ($matching_ids['id'] === '' && $matching_ids['last_changeset_id'] === '') {
            return $matching_ids;
        }

        $all_matching_artifact_ids       = explode(',', $matching_ids['id']);
        $all_matching_last_changeset_ids = explode(',', $matching_ids['last_changeset_id']);
        if (! is_array($all_matching_artifact_ids) || ! is_array($all_matching_last_changeset_ids)) {
            throw new \LogicException();
        }

        $rank = [];
        foreach ($this->artifact_priority_dao->getGlobalRanks($all_matching_artifact_ids) as $row) {
            $rank[$row['rank']] = $row['artifact_id'];
        }
        ksort($rank);

        $ranked_artifact_ids       = [];
        $ranked_last_changeset_ids = [];
        foreach ($rank as $artifact_id) {
            $key = array_search($artifact_id, $all_matching_artifact_ids);
            if ($key !== false) {
                $ranked_artifact_ids[]       = $all_matching_artifact_ids[$key];
                $ranked_last_changeset_ids[] = $all_matching_last_changeset_ids[$key];
            }
        }

        return [
            'id' => implode(',', $ranked_artifact_ids),
            'last_changeset_id' => implode(',', $ranked_last_changeset_ids),
        ];
    }
}
