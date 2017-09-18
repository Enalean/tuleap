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

use Tuleap\Label\Label;
use Tuleap\Label\PaginatedCollectionsOfLabels;
use Tuleap\PullRequest\PullRequest;

class LabelsCurlyCoatedRetriever
{
    /**
     * @var PullRequestLabelDao
     */
    private $dao;

    public function __construct(PullRequestLabelDao $dao)
    {
        $this->dao = $dao;
    }

    public function getPaginatedLabelsForPullRequest(PullRequest $pull_request, $limit, $offset)
    {
        $result     = $this->dao->searchLabelByPullRequestId($pull_request->getId(), $limit, $offset);
        $total_size = $this->dao->foundRows();

        $labels = array();
        foreach ($result as $row) {
            $labels[] = new Label($row['id'], $row['name']);
        }

        return new PaginatedCollectionsOfLabels($labels, $total_size);
    }
}
