/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import type { RootState } from "./type";
import type { ProjectProperties, TemplateData } from "../type";
import { getProjectUserIsAdminOf, postProject } from "../api/rest-querier";
import type { ActionContext } from "vuex";

export function setSelectedTemplate(
    context: ActionContext<RootState, RootState>,
    selected_template: TemplateData,
): void {
    return context.commit("setSelectedTemplate", selected_template);
}

export async function createProject(
    context: ActionContext<RootState, RootState>,
    project_properties: ProjectProperties,
): Promise<string> {
    let response;

    try {
        context.commit("setIsCreatingProject", true);
        response = await postProject(project_properties);
    } catch (error) {
        await context.commit("handleError", error);
        throw error;
    } finally {
        context.commit("setIsCreatingProject", false);
    }

    return response;
}

export async function loadUserProjects(
    context: ActionContext<RootState, RootState>,
): Promise<void> {
    const projects_user_is_admin_of = await getProjectUserIsAdminOf();
    context.commit("setAvailableProjectsUserIsAdminOf", projects_user_is_admin_of);
}
