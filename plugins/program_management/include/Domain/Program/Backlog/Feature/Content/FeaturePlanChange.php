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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchArtifactsLinks;

/**
 * @psalm-immutable
 */
final class FeaturePlanChange
{
    /**
     * @var FeatureChange[]
     */
    public $user_stories;

    /**
     * @param FeatureChange[] $user_stories
     */
    private function __construct(array $user_stories)
    {
        $this->user_stories = $user_stories;
    }

    /**
     * @param int[] $feature_to_links
     */
    public static function fromRaw(SearchArtifactsLinks $searcher, array $feature_to_links, int $program_increment_tracker_id): self
    {
        $feature_change = [];
        foreach ($feature_to_links as $feature_id_to_link) {
            $links = $searcher->getArtifactsLinkedToId($feature_id_to_link, $program_increment_tracker_id);

            foreach ($links as $link) {
                $feature_change[] = FeatureChange::fromRaw($link);
            }
        }
        return new self($feature_change);
    }
}
