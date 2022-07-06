<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Feature\Links;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchFeaturesInChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;

final class LinkedFeaturesDiff
{
    private function __construct(private array $removed_features)
    {
    }

    public static function build(
        SearchFeaturesInChangeset $search_features_in_changeset,
        ProgramIncrementChanged $program_increment_changed,
    ): self {
        $new_features     = $search_features_in_changeset->getArtifactsLinkedInChangeset($program_increment_changed->changeset);
        $old_features     = $search_features_in_changeset->getArtifactsLinkedInChangeset($program_increment_changed->old_changeset);
        $removed_features = array_values(array_diff($old_features, $new_features));

        return new self($removed_features);
    }

    /**
     * @return int[]
     */
    public function getRemovedFeaturesIds(): array
    {
        return $this->removed_features;
    }
}
