/*
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

import { createApp } from "vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import GitlabGroupLinkAppComponent from "./components/GitlabGroupLinkAppComponent.vue";

export async function init(mount_point: HTMLElement): Promise<void> {
    const current_project_name = mount_point.dataset.currentProjectName;
    if (!current_project_name) {
        throw new Error("Missing current project name in dataset");
    }

    const app = createApp(GitlabGroupLinkAppComponent, {
        current_project_name,
    });
    app.use(
        await initVueGettext(createGettext, (locale: string) => {
            return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
        })
    );
    app.mount(mount_point);
}
