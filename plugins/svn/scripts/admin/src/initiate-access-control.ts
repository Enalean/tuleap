/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import "./access-control.scss";

import { getJSON, uri } from "@tuleap/fetch-result";

interface ArchivedVersionResult {
    content: string;
}

export function initiateAccessControl(): void {
    const version_selected = document.getElementById("version-selected");
    const version_displayer = document.getElementById("other-version-content");
    const old_access_file_form = document.getElementById("old-access-file-form");

    if (
        !(version_selected instanceof HTMLSelectElement) ||
        version_displayer === null ||
        old_access_file_form === null
    ) {
        return;
    }

    const project_id = String(
        document.querySelector<HTMLInputElement>("input[name=project_id]")?.value,
    );
    const repo_id = String(document.querySelector<HTMLInputElement>("input[name=repo_id]")?.value);

    const updateVersionDisplayer = (response: ArchivedVersionResult): void => {
        let text_to_display = String(version_displayer.dataset.emptyVersion);
        old_access_file_form.hidden = false;

        if (response.content === "") {
            version_displayer.classList.add("empty-version");
            version_displayer.setAttribute("disabled", "");
        } else {
            text_to_display = response.content;
            version_displayer.classList.remove("empty-version");
            version_displayer.removeAttribute("disabled");
        }

        version_displayer.textContent = text_to_display;
    };

    version_selected.addEventListener("change", () => {
        if (version_selected.value === "0") {
            version_displayer.innerText = "";
            version_displayer.setAttribute("disabled", "");
            old_access_file_form.hidden = true;
        } else {
            getJSON<ArchivedVersionResult>(
                uri`/plugins/svn/?action=display-archived-version&accessfile_history_id=${version_selected.value}&group_id=${project_id}&repo_id=${repo_id}`,
            ).map(updateVersionDisplayer);
        }
    });
}
