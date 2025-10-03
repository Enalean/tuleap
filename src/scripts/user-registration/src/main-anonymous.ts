/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import { createPopover } from "@tuleap/tlp-popovers";
import { getLocaleWithDefault } from "@tuleap/locale";

document.addEventListener("DOMContentLoaded", () => {
    const form_loginname = document.getElementById("form_loginname");
    if (form_loginname instanceof HTMLInputElement) {
        form_loginname.addEventListener("keyup", () => {
            form_loginname.value = form_loginname.value.toLowerCase();
        });
    }

    const timezone = document.getElementById("timezone");
    if (timezone instanceof HTMLSelectElement) {
        if (!timezone.value) {
            timezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
        }
        createListPicker(timezone, {
            locale: getLocaleWithDefault(document),
            is_filterable: true,
        });
    }

    const form_pw = document.getElementById("form_pw");
    const form_pw_popover = document.getElementById("form_pw_popover");
    if (form_pw instanceof HTMLElement && form_pw_popover instanceof HTMLElement) {
        createPopover(form_pw, form_pw_popover, {
            trigger: "focus",
            placement: "right-start",
            anchor: document.getElementById("form_pw_anchor") || form_pw,
        });
    }
});
