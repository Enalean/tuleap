/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { selectOrThrow } from "@tuleap/dom";

const isViewingDefaultAccessControlAdmin = (): boolean => {
    return /action=admin-default-/.test(window.location.search);
};

export const initFineGrainedPermissionsToggle = (): void => {
    const toggle_fine_grained_permissions_checkbox = selectOrThrow(
        document,
        "#use-fine-grained-permissions",
        HTMLInputElement,
    );
    const write_permissions_select_box = selectOrThrow(
        document,
        ".write-permission-select",
        HTMLSelectElement,
    );
    const rewind_permissions_select_box = selectOrThrow(
        document,
        ".rewind-permission-select",
        HTMLSelectElement,
    );

    const toggleUseRegexpsFormElement = (): void => {
        const use_regexp_form_element = document.getElementById("access-control-use-regexps");
        use_regexp_form_element?.classList.toggle(
            "hidden",
            !toggle_fine_grained_permissions_checkbox.checked,
        );
    };

    const toggleWriteAndRewindPermissionsSelectBoxes = (): void => {
        [write_permissions_select_box, rewind_permissions_select_box].forEach(
            (select_box: HTMLSelectElement): void => {
                select_box.toggleAttribute("disabled");

                if (isViewingDefaultAccessControlAdmin()) {
                    if (select_box === rewind_permissions_select_box) {
                        select_box.toggleAttribute("required");
                    }

                    return;
                }

                select_box.toggleAttribute("required");
            },
        );
    };

    toggle_fine_grained_permissions_checkbox.addEventListener("change", () => {
        toggleWriteAndRewindPermissionsSelectBoxes();
        toggleUseRegexpsFormElement();
    });
};
