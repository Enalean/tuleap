/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
import GetTextPlugin from "vue-gettext";
import french_translations from "../po/fr.po";

export function init(mount_point, button) {
    Vue.use(GetTextPlugin, {
        translations: {
            fr: french_translations.messages,
        },
        silent: true,
    });

    Vue.config.language = document.body.dataset.userLocale;

    const repository_id = Number(button.dataset.repositoryId);
    const repository_url = button.dataset.repositoryUrl;
    const is_tag = Boolean(button.dataset.isTag);
    const current_ref_name = button.dataset.currentRefName;
    const url_parameters = JSON.parse(button.dataset.urlParameters);

    const RootComponent = Vue.extend(App);

    new RootComponent({
        propsData: {
            button,
            repository_id,
            repository_url,
            is_tag,
            current_ref_name,
            url_parameters,
        },
    }).$mount(mount_point);
}
