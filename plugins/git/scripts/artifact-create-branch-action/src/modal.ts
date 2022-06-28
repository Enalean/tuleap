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
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { getProjectRepositories } from "../api/rest_querier";

let app: App<Element> | null = null;

export async function init(
    git_create_branch_link: HTMLElement,
    mount_point: Element
): Promise<void> {
    const user_locale = document.body.dataset.userLocale;
    if (!user_locale) {
        return;
    }
    if (!git_create_branch_link.dataset.projectId) {
        throw new Error("Missing project id in dataset");
    }

    const project_id = Number(git_create_branch_link.dataset.projectId);

    if (app !== null) {
        app.unmount();
    }

    app = createApp(MainComponent, {
        repositories: await getProjectRepositories(project_id),
    });
    app.use(
        await initVueGettext(createGettext, (locale: string) => {
            return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
        })
    );
    app.mount(mount_point);
}
