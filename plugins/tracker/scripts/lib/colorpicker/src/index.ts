/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import "./style.scss";
import { createApp } from "vue";
import { createGettext } from "vue3-gettext";
import ColorPicker from "./ColorPicker.vue";
import { initVueGettext, getPOFileFromLocaleWithoutExtension } from "@tuleap/vue3-gettext-init";

export async function createColorPicker(mount_point: HTMLElement): Promise<void> {
    const gettext = await initVueGettext(createGettext, (locale: string) => {
        return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
    });

    const app = createApp(ColorPicker, {
        input_name: mount_point.dataset.inputName,
        input_id: mount_point.dataset.inputId,
        current_color: mount_point.dataset.currentColor,
        is_switch_disabled: Boolean(mount_point.dataset.isSwitchDisabled),
        is_old_palette_enabled: Boolean(mount_point.dataset.isOldPaletteEnabled),
    });
    app.use(gettext);
    app.mount(mount_point);
}
