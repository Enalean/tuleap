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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { post, recursiveGet, get } from "@tuleap/tlp-fetch";
import type { ProjectProperties, MinimalProjectRepresentation, TemplateData } from "../type";

export async function postProject(project_properties: ProjectProperties): Promise<string> {
    const headers = {
        "content-type": "application/json",
    };

    const json_body = {
        ...project_properties,
    };
    const body = JSON.stringify(json_body);

    const response = await post("/api/projects", { headers, body });

    return response.json();
}

export async function getProjectUserIsAdminOf(): Promise<TemplateData[]> {
    const minimal_project_representations: Array<MinimalProjectRepresentation> = await recursiveGet(
        "/api/projects/",
        {
            params: {
                limit: 50,
                offset: 0,
                query: JSON.stringify({ is_admin_of: true }),
            },
        },
    );

    return minimal_project_representations
        .filter((project) => !project.is_template)
        .map((project) => {
            return {
                title: project.label,
                description: "",
                id: project.id,
                glyph: "",
                is_built_in: false,
            };
        })
        .sort((a, b) => a.title.localeCompare(b.title, undefined, { numeric: true }));
}

export async function getTermOfService(): Promise<string> {
    const response = await get("/tos/tos_text.php");

    return response.text();
}
