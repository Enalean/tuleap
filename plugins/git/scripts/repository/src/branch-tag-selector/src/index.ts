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

import Vue from "vue";
import App from "./components/App.vue";
import { initVueGettext, getPOFileFromLocaleWithoutExtension } from "@tuleap/vue2-gettext-init";

export async function init(mount_point: HTMLDivElement, button: HTMLElement): Promise<void> {
    await initVueGettext(
        Vue,
        (locale: string) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const repository_id = Number(button.dataset.repositoryId);
    const repository_url = button.dataset.repositoryUrl;
    if (!repository_url) {
        throw new Error("No URL Repository");
    }
    const repository_default_branch = button.dataset.repositoryDefaultBranch;
    if (!repository_default_branch) {
        throw new Error("Repository default branch was not provided");
    }
    const is_tag = Boolean(button.dataset.isTag);
    const current_ref_name = button.dataset.currentRefName;

    if (!button.dataset.urlParameters) {
        throw new Error("No URL parameters");
    }

    const url_parameters = JSON.parse(button.dataset.urlParameters);

    const RootComponent = Vue.extend(App);

    new RootComponent({
        propsData: {
            button,
            repository_id,
            repository_url,
            repository_default_branch,
            is_tag,
            current_ref_name,
            url_parameters,
        },
    }).$mount(mount_point);
}
