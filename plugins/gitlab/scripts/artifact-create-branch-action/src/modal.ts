/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import type { App } from "vue";
import MainComponent from "./components/MainComponent.vue";
import { getGitlabRepositoriesWithDefaultBranches } from "./fetch-gitlab-repositories-information";

let app: App<Element> | null = null;

export async function init(create_branch_link: HTMLElement, mount_point: Element): Promise<void> {
    if (!create_branch_link.dataset.integrations) {
        throw new Error("Missing integrations representations dataset");
    }
    if (!create_branch_link.dataset.artifactId) {
        throw new Error("Missing artifact id dataset");
    }
    if (!create_branch_link.dataset.branchName) {
        throw new Error("Missing branch name dataset");
    }

    if (app !== null) {
        app.unmount();
    }

    const integrations_representations = JSON.parse(create_branch_link.dataset.integrations);
    const artifact_id = Number(create_branch_link.dataset.artifactId);

    app = createApp(MainComponent, {
        integrations: await getGitlabRepositoriesWithDefaultBranches(integrations_representations),
        branch_name: create_branch_link.dataset.branchName,
        artifact_id,
    });

    app.use(
        await initVueGettext(createGettext, (locale: string) => {
            return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
        }),
    );
    app.mount(mount_point);
}
