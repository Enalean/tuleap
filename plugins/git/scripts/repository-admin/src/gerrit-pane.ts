/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import "@tuleap/tlp-relative-date";
import "../styles/gerrit-pane.scss";

const GERRIT_SELECT_ID = "gerrit_url";
const ACTION_HIDDEN_INPUT_SELECTOR = "#action";
const HIDE_CLASS = "gerrit-pane-hidden";

document.addEventListener("DOMContentLoaded", () => {
    document
        .getElementById(GERRIT_SELECT_ID)
        ?.addEventListener("change", toggleMigrateDeleteRemote);
});

function toggleMigrateDeleteRemote(): void {
    const gerrit_select = document.getElementById(GERRIT_SELECT_ID);
    if (!(gerrit_select instanceof HTMLSelectElement)) {
        return;
    }
    const selected_option = gerrit_select.selectedOptions.item(0);
    const should_delete = selected_option?.getAttribute("data-repo-delete") === "1";
    const plugin_enabled = selected_option?.getAttribute("data-repo-delete-plugin-enabled") === "1";

    const delete_plugin_disabled = selectOrThrow(
        document,
        "#gerrit_past_project_delete_plugin_disabled",
    );
    const past_project_delete = selectOrThrow(document, "#gerrit_past_project_delete");
    const migrate_access_rights = selectOrThrow(document, "#migrate_access_right");
    const action_input = selectOrThrow(document, ACTION_HIDDEN_INPUT_SELECTOR, HTMLInputElement);

    if (!should_delete) {
        delete_plugin_disabled.classList.add(HIDE_CLASS);
        past_project_delete.classList.add(HIDE_CLASS);
        migrate_access_rights.classList.remove(HIDE_CLASS);
        action_input.value = "migrate_to_gerrit";
    } else if (should_delete && plugin_enabled) {
        delete_plugin_disabled.classList.add(HIDE_CLASS);
        past_project_delete.classList.remove(HIDE_CLASS);
        migrate_access_rights.classList.add(HIDE_CLASS);
        action_input.value = "delete_gerrit_project";
    } else if (should_delete && !plugin_enabled) {
        delete_plugin_disabled.classList.remove(HIDE_CLASS);
        past_project_delete.classList.add(HIDE_CLASS);
        migrate_access_rights.classList.add(HIDE_CLASS);
        action_input.value = "migrate_to_gerrit";
    }
}
