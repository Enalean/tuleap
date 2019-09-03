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

namespace Tuleap\Taskboard\Column;

use Cardwall_OnTop_ColumnDao;
use Tracker;

class ColumnPresenterCollectionRetriever
{
    /**
     * @var Cardwall_OnTop_ColumnDao
     */
    private $dao;

    public function __construct(Cardwall_OnTop_ColumnDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return ColumnPresenter[]
     */
    public function getColumns(Tracker $tracker): array
    {
        $collection = [];
        foreach ($this->dao->searchColumnsByTrackerId($tracker->getId()) as $row) {
            $collection[] = new ColumnPresenter(
                (int) $row['id'],
                $row['label']
            );
        }

        return $collection;
    }
}
