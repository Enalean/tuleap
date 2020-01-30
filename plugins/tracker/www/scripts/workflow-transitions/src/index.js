/**
 * Copyright Enalean (c) 2020-present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
import Vuex from "vuex";
import store_options from "./store";
import GettextPlugin from "vue-gettext";
import french_translations from "../po/fr.po";
import VueDOMPurifyHTML from "vue-dompurify-html";
import BaseTrackerWorkflowTransitions from "./components/BaseTrackerWorkflowTransitions.vue";
import { fetchCrossPluginComponents } from "./helpers/cross-plugin-component-importer.js";
import { setExternalComponents } from "./helpers/external-component-state.js";

/* Define Vue on window so that components find it
It will also avoid to the external component to embed a whole vue instance
 */
window.Vue = Vue;

async function addExternalComponents(jsonp_callbacks) {
    const modules = await fetchCrossPluginComponents(jsonp_callbacks);

    let all_translations = french_translations.messages;
    for (const module of modules) {
        setExternalComponents(module.components);
        all_translations = Object.assign(all_translations, module.french_translations.messages);
    }
    return all_translations;
}

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("tracker-workflow-transitions");
    if (!vue_mount_point) {
        return;
    }

    const data_callbacks = vue_mount_point.dataset.jsonpCallbacks;
    if (!data_callbacks) {
        return;
    }
    const jsonp_callbacks = JSON.parse(data_callbacks);
    const all_translations = await addExternalComponents(jsonp_callbacks);

    Vue.use(Vuex);
    Vue.use(GettextPlugin, {
        translations: {
            fr: all_translations
        },
        silent: true
    });
    Vue.use(VueDOMPurifyHTML);

    const locale = document.body.dataset.userLocale;
    Vue.config.language = locale;

    const RootComponent = Vue.extend(BaseTrackerWorkflowTransitions);
    const trackerId = Number.parseInt(vue_mount_point.dataset.trackerId, 10);
    const store = new Vuex.Store(store_options);

    new RootComponent({
        store,
        propsData: { trackerId }
    }).$mount(vue_mount_point);
});
