/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

import { createListPicker } from "@tuleap/list-picker";
import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";

export async function initStatusListPicker(mount_point: Document): Promise<void> {
    const action_button = mount_point.getElementById("done_value");
    if (!(action_button instanceof HTMLSelectElement)) {
        return;
    }
    const language = mount_point.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    const gettext_provider = await initGettext(
        language,
        "status-done-picker",
        (locale) => import(`../../../po/${getPOFileFromLocale(locale)}`),
    );

    createListPicker(action_button, {
        locale: language,
        placeholder: gettext_provider.gettext("Choose values"),
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initStatusListPicker(document);
});
