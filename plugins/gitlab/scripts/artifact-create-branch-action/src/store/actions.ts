/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import type { State } from "./type";
import type { ActionContext } from "vuex";
import type { createBranchPayload } from "./type";
import { postGitlabBranch } from "../api/rest-querier";

export async function createBranch(
    context: ActionContext<State, State>,
    payload: createBranchPayload
): Promise<void> {
    await postGitlabBranch(
        payload.gitlab_integration_id,
        payload.artifact_id,
        payload.branch_name,
        payload.reference
    );
}
