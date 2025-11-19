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

import { selectOrThrow, getAttributeOrThrow } from "@tuleap/dom";
import { getGettextProvider } from "./gettext-provider";

export const initAddFineGrainedPermissions = async (): Promise<void> => {
    const gettext_provider = await getGettextProvider();
    const getNewIndex = (type: string): number => {
        return document.querySelectorAll(`input[name=add-${type}-name]`).length;
    };

    const buttons = document.querySelectorAll<HTMLButtonElement>(
        ".add-fine-grained-permission-button",
    );
    const permission_row_template = selectOrThrow(
        document,
        "#add-fine-grained-permission-template",
    );

    for (const button of buttons) {
        button.addEventListener("click", () => {
            const type = getAttributeOrThrow(button, "data-type");
            const are_regexps_enabled = getAttributeOrThrow(button, "data-regexp-enabled");
            const target_table_id = getAttributeOrThrow(button, `data-target-table`);

            const new_row = document.createElement("tr");
            const regexp_column = document.createElement("td");
            const write_permissions_column = document.createElement("td");
            const rewind_permissions_column = document.createElement("td");
            const row_index = getNewIndex(type);

            const regexp_input = document.createElement("input");
            regexp_input.type = "text";
            regexp_input.className = "tlp-input";
            regexp_input.name = `add-${type}-name[${row_index}]`;
            regexp_input.placeholder =
                type === "branch"
                    ? gettext_provider.gettext("Branch name (master, my_feature…)")
                    : gettext_provider.gettext("Tag name (v1, alpha)…");

            regexp_column.appendChild(regexp_input);
            if (are_regexps_enabled) {
                const regexp_input_helper_icon = document.createElement("i");
                regexp_input_helper_icon.classList.add("fa-solid", "fa-circle-info");
                regexp_input_helper_icon.setAttribute("aria-hidden", "true");

                const regexp_input_helper = document.createElement("p");
                regexp_input_helper.className = "tlp-text-info";

                const regexp_input_helper_text = document.createTextNode(
                    gettext_provider.gettext(
                        "Regular expressions are available, only \\n can\\'t be used",
                    ),
                );
                regexp_input_helper.append(regexp_input_helper_icon, regexp_input_helper_text);
                regexp_column.appendChild(regexp_input_helper);
            }

            new_row.appendChild(regexp_column);

            const write_permissions_select = permission_row_template.cloneNode(true);
            if (!(write_permissions_select instanceof HTMLSelectElement)) {
                return;
            }
            write_permissions_select.removeAttribute("id");
            write_permissions_select.name = `add-${type}-write[${row_index}][]`;
            write_permissions_column.appendChild(write_permissions_select);

            const rewind_permissions_select = permission_row_template.cloneNode(true);
            if (!(rewind_permissions_select instanceof HTMLSelectElement)) {
                return;
            }
            rewind_permissions_select.removeAttribute("id");
            rewind_permissions_select.name = `add-${type}-rewind[${row_index}][]`;
            rewind_permissions_column.appendChild(rewind_permissions_select);

            new_row.append(
                regexp_column,
                write_permissions_column,
                rewind_permissions_column,
                document.createElement("td"),
            );
            selectOrThrow(document, `#${target_table_id} > tbody`).appendChild(new_row);
        });
    }
};
