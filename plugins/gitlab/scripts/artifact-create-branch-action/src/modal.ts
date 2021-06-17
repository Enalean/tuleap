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
import {
    getPOFileFromLocale,
    initVueGettext,
} from "../../../../../src/scripts/tuleap/gettext/vue-gettext-init";
import App from "./components/App.vue";
import { createStore } from "./store";

export async function init(create_branch_link: HTMLElement): Promise<void> {
    const user_locale = document.body.dataset.userLocale;
    if (!user_locale) {
        return;
    }

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
    if (!create_branch_link.dataset.slugifiedArtifactTitle) {
        throw new Error("Missing artifact title dataset");
    }

    const integrations_representations = JSON.parse(create_branch_link.dataset.integrations);
    const artifact_id = Number(create_branch_link.dataset.artifactId);
    const slugified_artifact_title = create_branch_link.dataset.slugifiedArtifactTitle;

    let branch_name = "tuleap-" + artifact_id;
    if (slugified_artifact_title.length > 0) {
        branch_name += "-" + slugified_artifact_title;
    }

    const store = createStore(integrations_representations, artifact_id, branch_name);
    const AppComponent = Vue.extend(App);
    const app = new AppComponent({
        store,
    }).$mount();
    document.body.appendChild(app.$el);
}
