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

namespace Tuleap\Project\Label;

use DataAccessObject;
use Tuleap\Label\UnknownLabelException;

class LabelDao extends DataAccessObject
{
    public function searchLabelsLikeKeywordByProjectId($project_id, $keyword, $limit, $offset)
    {
        $project_id = $this->da->escapeInt($project_id);
        $keyword    = $this->da->quoteLikeValueSurround($keyword);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM project_label
                WHERE project_id = $project_id
                  AND name LIKE $keyword
                ORDER BY id
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function createIfNeededInTransaction($project_id, $name)
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);

        $sql = "SELECT id
                FROM project_label
                WHERE project_id = $project_id AND name = $name";
        $result = $this->retrieveFirstRow($sql);
        if ($result) {
            return $result['id'];
        }

        $sql = "INSERT INTO project_label (project_id, name) VALUES ($project_id, $name)";

        return $this->updateAndGetLastId($sql);
    }

    public function checkThatAllLabelIdsExistInProjectInTransaction($project_id, array $array_of_label_ids)
    {
        if (empty($array_of_label_ids)) {
            return;
        }

        $array_of_label_ids = array_unique($array_of_label_ids);

        $project_id = $this->da->escapeInt($project_id);

        $nb_of_given_labels = count($array_of_label_ids);
        sort($array_of_label_ids);
        $array_of_label_ids = $this->da->escapeIntImplode($array_of_label_ids);

        $sql = "SELECT COUNT(*) AS nb_of_project_labels
                FROM project_label
                WHERE id IN ($array_of_label_ids)
                  AND project_id = $project_id";

        $result = $this->retrieveFirstRow($sql);
        if ($result['nb_of_project_labels'] != $nb_of_given_labels) {
            throw new UnknownLabelException();
        }
    }

    public function searchLabelsUsedByProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT * FROM project_label WHERE project_id = $project_id ORDER BY id";

        return $this->retrieve($sql);
    }
}
