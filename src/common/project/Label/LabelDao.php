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
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

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

    public function createIfNeededInTransaction($project_id, $new_name, array &$new_labels)
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($new_name);

        $sql = "SELECT id
                FROM project_label
                WHERE project_id = $project_id AND name = $name";
        $result = $this->retrieveFirstRow($sql);
        if ($result) {
            return $result['id'];
        }

        $sql = "INSERT INTO project_label (project_id, name, is_outline, color)
                VALUES ($project_id, $name, 1, 'chrome-silver')";
        $new_labels[] = $new_name;

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

        $sql = "SELECT * FROM project_label WHERE project_id = $project_id ORDER BY name";

        return $this->retrieve($sql);
    }

    public function deleteInTransaction($project_id, $label_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $label_id   = $this->da->escapeInt($label_id);

        $sql = "DELETE FROM project_label WHERE project_id = $project_id AND id = $label_id";

        $this->update($sql);
    }

    /**
     * @return int The number of affected label (0 if there is no change)
     */
    public function editInTransaction($project_id, $label_id, $new_name, $new_color, $new_is_outline)
    {
        $project_id     = $this->da->escapeInt($project_id);
        $label_id       = $this->da->escapeInt($label_id);
        $new_name       = $this->da->quoteSmart($new_name);
        $new_color      = $this->da->quoteSmart($new_color);
        $new_is_outline = $new_is_outline ? 1 : 0;

        $sql = "UPDATE project_label
                SET name = $new_name,
                    color = $new_color,
                    is_outline = $new_is_outline
                WHERE project_id = $project_id
                  AND id = $label_id";

        $this->update($sql);

        return $this->da->affectedRows();
    }

    public function searchProjectLabelsThatHaveSameName($project_id, $label_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $label_id   = $this->da->escapeInt($label_id);

        $sql = "SELECT other.id
                FROM project_label AS reference
                    INNER JOIN project_label AS other
                    ON (
                        reference.project_id = other.project_id
                        AND reference.name = other.name
                        AND reference.id != other.id
                        AND reference.id = $label_id
                        AND reference.project_id = $project_id
                    )
                ORDER BY id";

        return $this->retrieve($sql);
    }

    public function deleteAllLabelsInTransaction($project_id, array $label_ids)
    {
        if (count($label_ids) === 0) {
            return;
        }

        $project_id = $this->da->escapeInt($project_id);
        $label_ids  = $this->da->escapeIntImplode($label_ids);

        $sql = "DELETE FROM project_label WHERE project_id = $project_id AND id IN ($label_ids)";

        $this->update($sql);
    }

    public function addUniqueLabel($project_id, $name, $color, $is_outline)
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);
        $color      = $this->da->quoteSmart($color);
        $is_outline = $is_outline ? 1 : 0;

        $this->startTransaction();
        try {
            $sql = "SELECT NULL
                    FROM project_label
                    WHERE project_id = $project_id
                      AND name = $name
                    LIMIT 1";
            if (count($this->retrieve($sql)) > 0) {
                throw new LabelWithSameNameAlreadyExistException();
            }

            $sql = "INSERT INTO project_label (project_id, name, is_outline, color)
                    VALUES ($project_id, $name, $is_outline, $color)";
            $this->update($sql);

            $this->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    public function getLabelById($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM project_label WHERE id = $id";

        return $this->retrieveFirstRow($sql);
    }
}
