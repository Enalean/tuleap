/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { getProjectList } from "../api/rest-querier.js";

export async function loadProjectList(context) {
    try {
        const projectList = await getProjectList();

        getAsyncProjectList(context.commit, projectList.json());
    } catch (e) {
        const { error } = await e.response.json();
        context.commit("setErrorMessageType", error.message);
    } finally {
        context.commit("setIsLoadingInitial", false);
    }
}

export function resetState(context) {
    context.commit("setIsLoadingInitial", true);
    context.commit("setErrorMessageType", "");
    context.commit("pushProjects", {});
}

export async function getAsyncProjectList(commit, projectList) {
    commit("pushProjects", await projectList);
}
