/*
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

import type { ProjectArchiveTemplateData, ProjectProperties, TemplateData } from "../type";
import {
    ACCESS_PRIVATE,
    ACCESS_PRIVATE_WO_RESTRICTED,
    ACCESS_PUBLIC,
    ACCESS_PUBLIC_UNRESTRICTED,
} from "../constant";

const DEFAULT_PROJECT_ID = "100";

export function buildProjectPrivacy(
    selected_tuleap_template: TemplateData | null,
    selected_company_template: TemplateData | ProjectArchiveTemplateData | null,
    visibility: string,
    project_properties: ProjectProperties,
): ProjectProperties {
    if (selected_tuleap_template && selected_tuleap_template.id !== DEFAULT_PROJECT_ID) {
        project_properties.xml_template_name = selected_tuleap_template.id;
    }

    if (selected_tuleap_template && selected_tuleap_template.id === DEFAULT_PROJECT_ID) {
        project_properties.template_id = parseInt(selected_tuleap_template.id, 10);
    }
    if (selected_company_template) {
        if (
            selected_company_template.id === "from_project_archive" &&
            "archive" in selected_company_template
        ) {
            project_properties.from_archive = {
                file_name: selected_company_template.archive.name,
                file_size: selected_company_template.archive.size,
            };
        } else {
            project_properties.template_id = parseInt(selected_company_template.id, 10);
        }
    }

    let is_public_project = null;
    let is_restricted_allowed_for_the_project = null;
    switch (visibility) {
        case ACCESS_PUBLIC:
            is_public_project = true;
            is_restricted_allowed_for_the_project = false;
            break;
        case ACCESS_PRIVATE:
            is_public_project = false;
            is_restricted_allowed_for_the_project = true;
            break;
        case ACCESS_PUBLIC_UNRESTRICTED:
            is_public_project = true;
            is_restricted_allowed_for_the_project = true;
            break;
        case ACCESS_PRIVATE_WO_RESTRICTED:
            is_public_project = false;
            is_restricted_allowed_for_the_project = false;
            break;
        default:
            throw new Error("Unable to build the project privacy properties");
    }
    project_properties.is_public = is_public_project;
    project_properties.allow_restricted = is_restricted_allowed_for_the_project;
    return project_properties;
}
