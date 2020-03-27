/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import french_translations from "../po/fr.po";
import LabeledItemsList from "./LabeledItemsList.vue";

document.addEventListener("DOMContentLoaded", () => {
    Vue.use(VueDOMPurifyHTML, {
        namedConfigurations: {
            svg: {
                USE_PROFILES: { svg: true },
            },
        },
    });
    Vue.use(GetTextPlugin, {
        translations: {
            fr: french_translations.messages,
        },
        silent: true,
    });

    const locale = document.body.dataset.userLocale;
    Vue.config.language = locale;
    const widgets = document.getElementsByClassName("labeled-items-widget");
    const RootComponent = Vue.extend(LabeledItemsList);

    const widgets_array = [...widgets];
    for (const widget of widgets_array) {
        new RootComponent({
            propsData: { ...widget.dataset },
        }).$mount(widget);
    }
});
