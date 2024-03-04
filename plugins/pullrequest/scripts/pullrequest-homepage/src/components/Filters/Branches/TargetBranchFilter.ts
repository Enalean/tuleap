/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { Branch } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { BasePullRequestsListFilter } from "../PullRequestsListFilter";

export type TargetBranchFilter = "target_branch";
export const TYPE_FILTER_TARGET_BRANCH: TargetBranchFilter = "target_branch";

export type PullRequestTargetBranchFilter = BasePullRequestsListFilter<Branch> & {
    type: TargetBranchFilter;
    is_unique: true;
};

export type BuildTargetBranchFilter = {
    fromBranch(branch: Branch): PullRequestTargetBranchFilter;
};

export const TargetBranchFilterBuilder = (
    $gettext: (string: string) => string,
): BuildTargetBranchFilter => ({
    fromBranch: (branch: Branch): PullRequestTargetBranchFilter => ({
        id: 1,
        type: TYPE_FILTER_TARGET_BRANCH,
        label: `${$gettext("Branch")}: ${branch.name}`,
        value: branch,
        is_unique: true,
    }),
});
