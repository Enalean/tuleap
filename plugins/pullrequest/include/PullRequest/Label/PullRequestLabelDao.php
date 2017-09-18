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

namespace Tuleap\PullRequest\Label;

use DataAccessObject;
use Tuleap\Label\LabelableDao;
use Tuleap\Label\UnknownLabelException;

class PullRequestLabelDao extends DataAccessObject implements LabelableDao
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    public function searchLabelByPullRequestId($pull_request_id, $limit, $offset)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $limit           = $this->da->escapeInt($limit);
        $offset          = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS project_label.*
                FROM plugin_pullrequest_label
                    INNER JOIN project_label ON (
                        plugin_pullrequest_label.label_id = project_label.id
                        AND plugin_pullrequest_label.pull_request_id = $pull_request_id
                    )
                ORDER BY project_label.id
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    /**
     * @param int $item_id
     * @param int[] $array_of_label_ids
     * @throws UnknownLabelException
     */
    public function addLabelsInTransaction($item_id, array $array_of_label_ids)
    {
        if (empty($array_of_label_ids)) {
            return;
        }

        $pull_request_id = $this->da->escapeInt($item_id);
        $da = $this->da;
        $values = implode(
            ', ',
            array_map(
                function ($id) use ($pull_request_id, $da) {
                    $id = $da->escapeInt($id);

                    return "($pull_request_id, $id)";
                },
                $array_of_label_ids
            )
        );

        $sql = "INSERT IGNORE INTO plugin_pullrequest_label (pull_request_id, label_id) VALUES $values";

        $this->update($sql);
    }

    /**
     * @param int $item_id
     * @param int[] $array_of_label_ids
     */
    public function removeLabelsInTransaction($item_id, array $array_of_label_ids)
    {
        if (empty($array_of_label_ids)) {
            return;
        }

        $pull_request_id = $this->da->escapeInt($item_id);

        sort($array_of_label_ids);
        $array_of_label_ids = $this->da->escapeIntImplode($array_of_label_ids);

        $sql = "DELETE FROM plugin_pullrequest_label
                WHERE pull_request_id = $pull_request_id
                    AND label_id IN ($array_of_label_ids)";

        $this->update($sql);
    }

    public function searchLabelsUsedInProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT project_label.*
                FROM plugin_pullrequest_label
                    INNER JOIN project_label ON (
                        plugin_pullrequest_label.label_id = project_label.id
                        AND project_label.project_id = $project_id
                    )";

        return $this->retrieve($sql);
    }

    public function deleteInTransaction($project_id, $label_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $label_id   = $this->da->escapeInt($label_id);

        $sql = "DELETE plugin_pullrequest_label.*
                FROM plugin_pullrequest_label
                    INNER JOIN project_label ON (
                        plugin_pullrequest_label.label_id = project_label.id
                        AND project_label.id = $label_id
                        AND project_label.project_id = $project_id
                    )";

        return $this->update($sql);
    }
}
