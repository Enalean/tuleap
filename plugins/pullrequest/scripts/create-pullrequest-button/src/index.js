/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import App from "./components/App.vue";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";

document.addEventListener("DOMContentLoaded", async () => {
    const is_anonymous = document.body.dataset.userId === "0";
    if (is_anonymous) {
        return;
    }

    const container = document.getElementById("git-repository-actions-main-buttons");
    if (!container || container.dataset.isMigratedToGerrit === "1") {
        return;
    }

    const repository_id = parseInt(container.dataset.repositoryId, 10);
    const project_id = parseInt(container.dataset.projectId, 10);
    const parent_repository_id = parseInt(container.dataset.parentRepositoryId, 10);
    const parent_repository_name = container.dataset.parentRepositoryName;
    const parent_project_id = parseInt(container.dataset.parentProjectId, 10);
    const user_can_see_parent_repository = container.dataset.userCanSeeParentRepository === "1";

    const mount_point = document.createElement("div");
    container.appendChild(mount_point);

    const gettext = await initVueGettext(createGettext, (locale) =>
        import(`./${getPOFileFromLocale(locale)}`),
    );

    const app = createApp(App, {
        repository_id,
        project_id,
        parent_repository_id,
        parent_repository_name,
        parent_project_id,
        user_can_see_parent_repository,
    });

    app.use(gettext);
    app.mount(mount_point);
});
