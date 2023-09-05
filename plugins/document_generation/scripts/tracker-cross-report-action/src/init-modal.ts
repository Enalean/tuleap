/**
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

import type { App } from "vue";
import { createApp } from "vue";
import Main from "./Components/MainComponent.vue";
import type { GlobalExportProperties } from "./type";
import { createGettext } from "vue3-gettext";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";

let app: App<Element> | null = null;

export async function initModal(
    mount_point: Element,
    properties: GlobalExportProperties,
): Promise<void> {
    if (app !== null) {
        app.unmount();
    }
    app = createApp(Main, { properties });
    app.use(
        await initVueGettext(createGettext, (locale: string) => {
            return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
        }),
    );
    app.mount(mount_point);
}
