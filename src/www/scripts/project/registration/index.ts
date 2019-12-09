/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import Vue from "vue";
import { initVueGettext } from "../../tuleap/gettext/vue-gettext-init";
import App from "./src/components/App.vue";
import { TemplateData, TroveCatData } from "./src/type";
import { createStore } from "./src/store";
import { State } from "./src/store/type";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { createRouter } from "./src/router";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("project-registration");
    if (!vue_mount_point) {
        return;
    }

    await initVueGettext(Vue, (locale: string) =>
        import(/* webpackChunkName: "project-registration-po-" */ `./po/${locale}.po`)
    );

    const tuleap_templates_json = vue_mount_point.dataset.availableTuleapTemplates;
    if (!tuleap_templates_json) {
        return;
    }

    const trove_categories_json = vue_mount_point.dataset.troveCat;
    let trove_categories: TroveCatData[] = [];
    if (trove_categories_json) {
        trove_categories = JSON.parse(trove_categories_json);
    }

    const field_list_json = vue_mount_point.dataset.fieldList;
    if (!field_list_json) {
        return;
    }
    const project_fields = JSON.parse(field_list_json);

    const are_restricted_users_allowed = Boolean(vue_mount_point.dataset.areRestrictedUsersAllowed);
    const project_default_visibility = String(vue_mount_point.dataset.projectDefaultVisibility);
    const tuleap_templates: TemplateData[] = JSON.parse(tuleap_templates_json);

    const selected_template = null;
    const error = null;
    const is_creating_project = false;
    const is_project_approval_required = Boolean(vue_mount_point.dataset.projectsMustBeApproved);
    const is_description_required = Boolean(vue_mount_point.dataset.isDescriptionMandatory);

    const root_state: State = {
        tuleap_templates,
        selected_template,
        are_restricted_users_allowed,
        project_default_visibility,
        error,
        is_creating_project,
        is_project_approval_required,
        trove_categories,
        is_description_required,
        project_fields
    };

    Vue.use(VueDOMPurifyHTML);

    const AppComponent = Vue.extend(App);
    const store = createStore(root_state);
    const router = createRouter();

    new AppComponent({
        store,
        router
    }).$mount(vue_mount_point);
});
