/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import type { ComputedRef, Ref } from "vue";
import { computed, ref } from "vue";
import type { ExtendedBranch, Branch } from "./pullrequest-helper";
import {
    canCreatePullrequest,
    extendBranch,
    extendBranchForParent,
    getPullrequestUrl,
} from "./pullrequest-helper";
import { getBranches, createPullrequest } from "../api/rest-querier";
import { redirectTo } from "./window-helper";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

export interface PullrequestState {
    source_branches: Ref<ExtendedBranch[]>;
    destination_branches: Ref<ExtendedBranch[]>;
    selected_source_branch: Ref<ExtendedBranch | "">;
    selected_destination_branch: Ref<ExtendedBranch | "">;
    create_error_message: Ref<string>;
    has_error_while_loading_branches: Ref<boolean>;
    is_creating_pullrequest: Ref<boolean>;
    can_create_pullrequest: ComputedRef<boolean>;
    init: (params: {
        repository_id: number;
        project_id: number;
        parent_repository_id: number;
        parent_repository_name: string;
        parent_project_id: number;
        user_can_see_parent_repository: boolean;
    }) => Promise<void>;
    create: () => Promise<void>;
    resetSelection: () => void;
}

export function buildPullrequestState(): PullrequestState {
    const source_branches = ref<ExtendedBranch[]>([]);
    const destination_branches = ref<ExtendedBranch[]>([]);
    const selected_source_branch = ref<ExtendedBranch | "">("");
    const selected_destination_branch = ref<ExtendedBranch | "">("");
    const create_error_message = ref("");
    const has_error_while_loading_branches = ref(false);
    const is_creating_pullrequest = ref(false);

    const can_create_pullrequest = computed(() =>
        canCreatePullrequest(source_branches.value, destination_branches.value),
    );

    async function init({
        repository_id,
        project_id,
        parent_repository_id,
        parent_repository_name,
        parent_project_id,
        user_can_see_parent_repository,
    }: {
        repository_id: number;
        project_id: number;
        parent_repository_id: number;
        parent_repository_name: string;
        parent_project_id: number;
        user_can_see_parent_repository: boolean;
    }): Promise<void> {
        try {
            const branches = (await getBranches(repository_id)).map((branch: Branch) =>
                extendBranch(branch, repository_id, project_id),
            );

            source_branches.value = branches;

            if (parent_repository_id && user_can_see_parent_repository) {
                const parent_repository_branches = (await getBranches(parent_repository_id)).map(
                    (branch: Branch) =>
                        extendBranchForParent(
                            branch,
                            parent_repository_id,
                            parent_repository_name,
                            parent_project_id,
                        ),
                );
                destination_branches.value = parent_repository_branches.concat(branches);
            } else {
                destination_branches.value = branches;
            }
        } catch (rest_error) {
            has_error_while_loading_branches.value = true;
            throw rest_error;
        }
    }

    async function create(): Promise<void> {
        if (!selected_source_branch.value || !selected_destination_branch.value) {
            return;
        }
        try {
            is_creating_pullrequest.value = true;
            const pullrequest = await createPullrequest(
                selected_source_branch.value.repository_id,
                selected_source_branch.value.name,
                selected_destination_branch.value.repository_id,
                selected_destination_branch.value.name,
            );
            redirectTo(
                getPullrequestUrl(
                    pullrequest.id,
                    selected_destination_branch.value.repository_id,
                    selected_destination_branch.value.project_id,
                ),
            );
        } catch (rest_error) {
            if (rest_error instanceof FetchWrapperError) {
                const { error } = await rest_error.response.json();
                create_error_message.value = error.message;
            }
            is_creating_pullrequest.value = false;
            throw rest_error;
        }
    }

    function resetSelection(): void {
        create_error_message.value = "";
        selected_source_branch.value = "";
        selected_destination_branch.value = "";
    }

    return {
        source_branches,
        destination_branches,
        selected_source_branch,
        selected_destination_branch,
        create_error_message,
        has_error_while_loading_branches,
        is_creating_pullrequest,
        can_create_pullrequest,
        init,
        create,
        resetSelection,
    };
}
