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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;

class FeatureToLinkBuilder
{
    /**
     * @var ArtifactsLinkedToParentDao
     */
    private $dao;

    public function __construct(ArtifactsLinkedToParentDao $dao)
    {
        $this->dao = $dao;
    }

    public function buildFeatureChange(array $feature_to_links, int $program_increment_tracker_id): FeaturePlanChange
    {
        $feature_change = [];
        foreach ($feature_to_links as $feature_to_link) {
            $links = $this->dao->getArtifactsLinkedToId((int) $feature_to_link['artifact_id'], $program_increment_tracker_id);

            foreach ($links as $link) {
                $feature_change[] = $link['id'];
            }
        }

        return new FeaturePlanChange($feature_change);
    }
}
