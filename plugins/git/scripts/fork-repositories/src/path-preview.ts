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

import { getAttributeOrThrow } from "@tuleap/dom";

export function initForkPathPreview(
    preview_element: HTMLElement,
    forkable_repositories: HTMLSelectElement,
    personal_fork_radio_button: HTMLInputElement,
    fork_path_input: HTMLInputElement,
    project_select_box: HTMLElement | null,
    project_fork_radio_button: HTMLElement | null,
): void {
    const user_name = getAttributeOrThrow(preview_element, "data-user-name");

    const appendPathPreview = (path: string): void => {
        const preview = document.createElement("span");
        preview.setAttribute("data-test", "previewed-fork-path");
        preview.textContent = path;
        preview_element.appendChild(preview);
    };

    const generateUserForkPath = (): void => {
        const custom_user_path =
            fork_path_input.value.trim() !== "" ? `/${fork_path_input.value.trim()}` : "";
        if (forkable_repositories.selectedOptions.length === 0) {
            appendPathPreview(`u/${user_name}${custom_user_path}/...`);
            return;
        }

        for (const { textContent: repository_name } of forkable_repositories.selectedOptions) {
            appendPathPreview(`u/${user_name}${custom_user_path}/${repository_name}`);
        }
    };

    const generateProjectForkPath = (project_select_box: HTMLSelectElement): void => {
        const selected_project = project_select_box.selectedOptions.item(0);
        if (!selected_project) {
            return;
        }

        const project_unix_name = getAttributeOrThrow(selected_project, "data-unix-name");
        if (forkable_repositories.selectedOptions.length === 0) {
            appendPathPreview(`${project_unix_name}/...`);
            return;
        }

        for (const { textContent: repository_name } of forkable_repositories.selectedOptions) {
            appendPathPreview(`${project_unix_name}/${repository_name}`);
        }
    };

    const generatePreview = (): void => {
        preview_element.innerHTML = "";

        if (personal_fork_radio_button.checked) {
            generateUserForkPath();
            return;
        }

        if (
            project_fork_radio_button instanceof HTMLInputElement &&
            project_fork_radio_button.checked &&
            project_select_box instanceof HTMLSelectElement
        ) {
            generateProjectForkPath(project_select_box);
        }
    };

    forkable_repositories.addEventListener("change", generatePreview);
    fork_path_input.addEventListener("input", generatePreview);
    personal_fork_radio_button.addEventListener("click", generatePreview);
    project_fork_radio_button?.addEventListener("click", generatePreview);
    project_select_box?.addEventListener("change", generatePreview);

    generatePreview();
}
