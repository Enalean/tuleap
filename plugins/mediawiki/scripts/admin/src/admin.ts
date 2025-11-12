/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

import "./admin.scss";

document.addEventListener("DOMContentLoaded", () => {
    function getSelectedIds(selected_elements: NodeListOf<Element>): string[] {
        return [...selected_elements].reduce((ids: string[], element) => {
            if (element instanceof HTMLOptionElement) {
                ids.push(element.value);
            }

            return ids;
        }, []);
    }

    function addSelectedElementToField(
        container: Element,
        selected_elements: NodeListOf<Element>,
    ): void {
        const selected_ids = getSelectedIds(selected_elements);
        const hidden = container.querySelector(".forge_mw_hidden_selected_groups");
        if (!(hidden instanceof HTMLInputElement)) {
            return;
        }

        hidden.value = selected_ids.concat(hidden.value.split(",")).join(",");
    }

    function removeSelectedElementToField(
        container: Element,
        selected_elements: NodeListOf<Element>,
    ): void {
        const selected_ids = getSelectedIds(selected_elements);
        const hidden = container.querySelector(".forge_mw_hidden_selected_groups");
        if (!(hidden instanceof HTMLInputElement)) {
            return;
        }

        hidden.value = hidden.value
            .split(",")
            .filter((id) => !selected_ids.includes(id))
            .join(",");
    }

    document.querySelectorAll(".forge_mw_add_ugroup").forEach((element) =>
        element.addEventListener("click", function () {
            const container = element.closest(".mediawiki-admin-permissions");
            if (!container) {
                return;
            }
            const select = container.querySelector(".forge-mw-selected-groups");
            if (!select) {
                return;
            }

            const selected_elements = container.querySelectorAll(
                ".forge-mw-available-groups option:checked",
            );
            addSelectedElementToField(container, selected_elements);
            select.append(...selected_elements);
        }),
    );

    document.querySelectorAll(".forge_mw_remove_ugroup").forEach((element) =>
        element.addEventListener("click", function () {
            const container = element.closest(".mediawiki-admin-permissions");
            if (!container) {
                return;
            }
            const select = container.querySelector(".forge-mw-available-groups");
            if (!select) {
                return;
            }

            const selected_elements = container.querySelectorAll(
                ".forge-mw-selected-groups option:checked",
            );
            removeSelectedElementToField(container, selected_elements);
            select.append(...selected_elements);
        }),
    );
});
