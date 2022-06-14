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

import Vue from "vue";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue2-gettext-init";
import App from "./components/App.vue";
import { getGitlabRepositoriesWithDefaultBranches } from "./fetch-gitlab-repositories-information";
import VueCompositionAPI from "@vue/composition-api";

export async function init(create_branch_link: HTMLElement): Promise<void> {
    const user_locale = document.body.dataset.userLocale;
    if (!user_locale) {
        return;
    }

    Vue.use(VueCompositionAPI);
    Vue.config.language = user_locale;

    await initVueGettext(
        Vue,
        (locale: string) =>
            import(/* webpackChunkName: "gitlab-po-" */ "../po/" + getPOFileFromLocale(locale))
    );

    if (!create_branch_link.dataset.integrations) {
        throw new Error("Missing integrations representations dataset");
    }
    if (!create_branch_link.dataset.artifactId) {
        throw new Error("Missing artifact id dataset");
    }
    if (!create_branch_link.dataset.branchName) {
        throw new Error("Missing branch name dataset");
    }

    const integrations_representations = JSON.parse(create_branch_link.dataset.integrations);
    const artifact_id = Number(create_branch_link.dataset.artifactId);

    const AppComponent = Vue.extend(App);
    const app = new AppComponent({
        propsData: {
            integrations: await getGitlabRepositoriesWithDefaultBranches(
                integrations_representations
            ),
            branch_name: create_branch_link.dataset.branchName,
            artifact_id,
        },
    }).$mount();

    document.body.appendChild(app.$el);
}
