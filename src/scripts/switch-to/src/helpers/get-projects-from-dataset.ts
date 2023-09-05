/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { Project, ProjectBaseDefinition } from "../type";
import { sprintf } from "sprintf-js";

export function getProjectsFromDataset(
    projects: string | undefined,
    $gettext: (msgid: string) => string,
): Project[] {
    if (projects === undefined) {
        return [];
    }

    const projects_definitions: ProjectBaseDefinition[] = JSON.parse(projects);

    return projects_definitions.map((project): Project => {
        const quick_links = project.is_current_user_admin
            ? [
                  {
                      name: sprintf(
                          $gettext("Go to project administration of %s"),
                          project.project_name,
                      ),
                      html_url: project.project_config_uri,
                      icon_name: "fa-solid fa-gear",
                  },
              ]
            : [];
        return { ...project, quick_links };
    });
}
