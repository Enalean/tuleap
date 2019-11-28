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

import { post } from "tlp";
import { ProjectProperties } from "../type";

export { postProject };

async function postProject(project_properties: ProjectProperties): Promise<string> {
    const headers = {
        "content-type": "application/json"
    };

    const json_body = {
        ...project_properties
    };
    const body = JSON.stringify(json_body);

    const response = await post("/api/projects", { headers, body });

    return response.json();
}
