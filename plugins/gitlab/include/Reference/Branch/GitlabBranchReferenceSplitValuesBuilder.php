<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference\Branch;

use Tuleap\Gitlab\Reference\GitlabReferenceSplittedValues;
use Tuleap\Gitlab\Reference\GitlabReferenceSplitValuesBuilder;

final class GitlabBranchReferenceSplitValuesBuilder implements GitlabReferenceSplitValuesBuilder
{
    public function __construct(private BranchReferenceSplitValuesDao $branch_reference_splitted_values_dao)
    {
    }

    #[\Override]
    public function splitRepositoryNameAndReferencedItemId(string $value, int $project_id): GitlabReferenceSplittedValues
    {
        $referenced_branch_in_project = $this->branch_reference_splitted_values_dao->getAllBranchesSplitValuesInProject(
            $project_id,
            $value
        );

        if ($referenced_branch_in_project === null) {
            return GitlabReferenceSplittedValues::buildNotFoundReference();
        }

        $repository_name = $referenced_branch_in_project['repository_name'];
        $branch_name     = $referenced_branch_in_project['branch_name'];

        return GitlabReferenceSplittedValues::buildFromReference(
            $repository_name,
            $branch_name
        );
    }
}
