/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { getBranches, createPullrequest } from "../api/rest-querier.js";
import { redirectTo } from "../helpers/window-helper.js";

export async function init(
    context,
    {
        repository_id,
        project_id,
        parent_repository_id,
        parent_repository_name,
        parent_project_id,
        user_can_see_parent_repository,
    },
) {
    try {
        const branches = (await getBranches(repository_id)).map(extendBranch);

        context.commit("setSourceBranches", branches);

        if (parent_repository_id && user_can_see_parent_repository) {
            const parent_repository_branches = (await getBranches(parent_repository_id)).map(
                extendBranchForParent,
            );
            context.commit("setDestinationBranches", branches.concat(parent_repository_branches));
        } else {
            context.commit("setDestinationBranches", branches);
        }
    } catch (e) {
        context.commit("setHasErrorWhileLoadingBranchesToTrue");
    }

    function extendBranch(branch) {
        return {
            display_name: branch.name,
            repository_id,
            project_id,
            ...branch,
        };
    }

    function extendBranchForParent(branch) {
        return {
            display_name: `${parent_repository_name} : ${branch.name}`,
            repository_id: parent_repository_id,
            project_id: parent_project_id,
            ...branch,
        };
    }
}

export async function create(context, { source_branch, destination_branch }) {
    try {
        context.commit("setIsCreatinPullRequest", true);
        const pullrequest = await createPullrequest(
            source_branch.repository_id,
            source_branch.name,
            destination_branch.repository_id,
            destination_branch.name,
        );
        redirectTo(
            `/plugins/git/?action=pull-requests&tab=overview&repo_id=${destination_branch.repository_id}&group_id=${destination_branch.project_id}#/pull-requests/${pullrequest.id}/overview`,
        );
    } catch (e) {
        const { error } = await e.response.json();
        context.commit("setCreateErrorMessage", error.message);
        context.commit("setIsCreatinPullRequest", false);
    }
}
