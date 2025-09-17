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

import "../themes/label.scss";
import { createApp } from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import LabeledItemsList from "./LabeledItemsList.vue";
import { createGettext } from "vue3-gettext";
import { getAttributeOrThrow } from "@tuleap/dom";

document.addEventListener("DOMContentLoaded", async () => {
    /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
    const gettext = await initVueGettext(createGettext, (locale) => {
        return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
    });

    const widgets = document.getElementsByClassName("labeled-items-widget");
    for (const widget of widgets) {
        if (!widget || !(widget instanceof HTMLElement)) {
            return;
        }
        createApp(LabeledItemsList, {
            labels_id: getAttributeOrThrow(widget, "data-labels-id"),
            project_id: getAttributeOrThrow(widget, "data-project-id"),
        })
            /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
            .use(gettext)
            .use(VueDOMPurifyHTML, {
                namedConfigurations: {
                    svg: {
                        USE_PROFILES: { svg: true },
                    },
                },
            })
            .mount(widget);
    }
});
