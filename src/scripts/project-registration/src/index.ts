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

import { createApp } from "vue";
import "../themes/project-registration/project-registration.scss";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import App from "./components/App.vue";
import type { TemplateData, TroveCatData } from "./type";
import { useStore } from "./stores/root";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { router } from "./router";
import { createPinia } from "pinia";
import type { RootState } from "./stores/type";
import { createGettext } from "vue3-gettext";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("project-registration");
    if (!vue_mount_point) {
        return;
    }

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

    const is_project_approval_required = Boolean(vue_mount_point.dataset.projectsMustBeApproved);
    const is_description_required = Boolean(vue_mount_point.dataset.isDescriptionMandatory);
    const can_user_choose_project_visibility = Boolean(
        vue_mount_point.dataset.canUserChoosePrivacy,
    );

    const company_templates_json = vue_mount_point.dataset.companyTemplates;
    if (!company_templates_json) {
        return;
    }
    const company_templates = JSON.parse(company_templates_json);

    const company_name = String(vue_mount_point.dataset.companyName);

    let external_templates = [];
    const external_templates_json = vue_mount_point.dataset.externalTemplates;
    if (external_templates_json) {
        external_templates = JSON.parse(external_templates_json);
    }

    const can_create_from_project_file = Boolean(vue_mount_point.dataset.canCreateFromProjectFile);

    const pinia = createPinia();

    const root_state: RootState = {
        tuleap_templates,
        external_templates,
        are_restricted_users_allowed,
        project_default_visibility,
        is_project_approval_required,
        trove_categories,
        is_description_required,
        project_fields,
        company_templates,
        company_name,
        can_user_choose_project_visibility,
        can_create_from_project_file,
        selected_tuleap_template: null,
        selected_company_template: null,
        selected_template_category: null,
        selected_advanced_option: null,
        projects_user_is_admin_of: [],
        error: null,
        is_creating_project: false,
    };

    const store = useStore(pinia);

    store.$patch(root_state);
    createApp(App)
        .use(VueDOMPurifyHTML)
        .use(pinia)
        .use(
            await initVueGettext(createGettext, (locale: string) => {
                return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
            }),
        )
        .use(router)
        .mount(vue_mount_point);
});
