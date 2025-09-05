<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Tuleap\Roadmap\NatureForRoadmapDao;
use Tuleap\Tracker\Artifact\Artifact;

final class DependenciesRetriever implements IRetrieveDependencies
{
    /**
     * @var NatureForRoadmapDao
     */
    private $nature_dao;

    public function __construct(NatureForRoadmapDao $nature_dao)
    {
        $this->nature_dao = $nature_dao;
    }

    /**
     * @return DependenciesByNature[]
     */
    #[\Override]
    public function getDependencies(Artifact $artifact): array
    {
        $links_by_nature = [];

        $rows = $this->nature_dao->searchForwardLinksHavingSemantics($artifact->getID());
        if (! $rows) {
            return [];
        }

        foreach ($rows as $row) {
            if (! isset($links_by_nature[$row['nature']])) {
                $links_by_nature[$row['nature']] = [];
            }

            $links_by_nature[$row['nature']][] = $row['id'];
        }

        $dependencies = [];
        foreach ($links_by_nature as $nature => $artifact_ids) {
            $dependencies[] = new DependenciesByNature($nature, array_unique($artifact_ids));
        }

        return $dependencies;
    }
}
