<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label;

interface LabelableDao
{
    /**
     * @param int $item_id
     * @param int[] $array_of_label_ids
     * @throws UnknownLabelException
     */
    public function addLabelsInTransaction($item_id, array $array_of_label_ids);

    /**
     * @param int $item_id
     * @param int[] $array_of_label_ids
     */
    public function removeLabelsInTransaction($item_id, array $array_of_label_ids);

    /**
     * @param int $project_id
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function searchLabelsUsedInProject($project_id);

    /**
     * @param int $project_id
     * @param int $label_id
     */
    public function deleteInTransaction($project_id, $label_id);

    /**
     * @param int array $project_id
     * @param int $label_id
     * @param int[] $label_ids_to_merge
     */
    public function mergeLabelsInTransaction($project_id, $label_id, array $label_ids_to_merge);
}
