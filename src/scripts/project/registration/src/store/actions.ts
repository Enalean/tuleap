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

import { Context } from "./type";
import { ProjectProperties, TemplateData } from "../type";
import { postProject } from "../api/rest-querier";

export function setSelectedTemplate(
    context: Context,
    selected_template: TemplateData
): Promise<void> {
    return context.commit("setSelectedTemplate", selected_template);
}

export async function createProject(
    context: Context,
    project_properties: ProjectProperties
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
