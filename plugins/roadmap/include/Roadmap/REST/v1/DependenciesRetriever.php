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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

final class DependenciesRetriever implements IRetrieveDependencies
{
    /**
     * @var NatureDao
     */
    private $nature_dao;

    public function __construct(NatureDao $nature_dao)
    {
        $this->nature_dao = $nature_dao;
    }

    /**
     * @return DependenciesByNature[]
     */
    public function getDependencies(Artifact $artifact): array
    {
        $dependencies = [];
        $rows         = $this->nature_dao->searchForwardNatureShortNamesForGivenArtifact($artifact->getID());
        foreach ($rows as $row) {
            $shortname    = $row['shortname'];
            $artifact_ids = $this->nature_dao->getForwardLinkedArtifactIds(
                $artifact->getID(),
                $shortname,
                PHP_INT_MAX,
                0
            );

            $dependencies[] = new DependenciesByNature($shortname, $artifact_ids);
        }

        return $dependencies;
    }
}
