/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { createApp } from "vue";
import type { App } from "vue";
import MainComponent from "./components/MainComponent.vue";
import { getProjectRepositories } from "../api/rest_querier";
import { addFeedback } from "@tuleap/fp-feedback";
import type { createGettext } from "vue3-gettext";

let app: App<Element> | null = null;

export async function init(
    git_create_branch_link: HTMLElement,
    mount_point: Element,
    gettext_provider: ReturnType<typeof createGettext>,
): Promise<void> {
    if (!git_create_branch_link.dataset.projectId) {
        throw new Error("Missing project id in dataset");
    }
    if (!git_create_branch_link.dataset.gitBranchNamePreview) {
        throw new Error("Missing branch name preview in dataset");
    }

    const project_id = Number(git_create_branch_link.dataset.projectId);
    const branch_name_preview = git_create_branch_link.dataset.gitBranchNamePreview;
    const are_pullrequest_endpoints_available = Boolean(
        git_create_branch_link.dataset.arePullrequestEndpointsAvailable,
    );

    if (app !== null) {
        app.unmount();
    }

    await getProjectRepositories(project_id, branch_name_preview).match(
        (project_repositories) => {
            app = createApp(MainComponent, {
                repositories: project_repositories,
                branch_name_preview: branch_name_preview,
                are_pullrequest_endpoints_available,
            });
            app.use(gettext_provider);
            app.mount(mount_point);
        },
        (fault) => {
            addFeedback(
                "error",
                gettext_provider.interpolate(
                    gettext_provider.$gettext(
                        "Error while retrieving the Git project repositories: %{ error }",
                    ),
                    { error: String(fault) },
                ),
            );
        },
    );
}
