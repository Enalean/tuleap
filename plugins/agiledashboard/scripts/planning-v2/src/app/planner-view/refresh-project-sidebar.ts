/*
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

import { getJSON, uri } from "@tuleap/fetch-result";

type IntegrationData = {
    readonly project_sidebar: {
        readonly config: string;
    };
};

export function refreshProjectSidebar(project_id: number): void {
    getJSON<IntegrationData>(
        uri`/api/projects/${project_id}/3rd_party_integration_data?currently_active_service=plugin_agiledashboard`,
    )
        .map((integration_data: IntegrationData): string => {
            return integration_data.project_sidebar.config;
        })
        .match(
            (config: string): void => {
                for (const sidebar of document.body.querySelectorAll("tuleap-project-sidebar")) {
                    sidebar.setAttribute("config", config);
                }
            },
            () => {},
        );
}
