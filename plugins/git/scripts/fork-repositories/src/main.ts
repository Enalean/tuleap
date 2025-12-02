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

import "../styles/styles.scss";
import { selectOrThrow } from "@tuleap/dom";
import { initProjectDestinationSelection } from "./destination-selection";
import { initForkPathPreview } from "./path-preview";
import { initSubmitButton } from "./submit-button";

document.addEventListener("DOMContentLoaded", () => {
    const project_select_box = document.getElementById("fork-destination");
    const project_fork_radio_button = document.getElementById("project-fork");
    const personal_fork_radio_button = selectOrThrow(document, "#personal-fork", HTMLInputElement);
    const fork_path_form_element = selectOrThrow(document, "#fork-repository-path-form-element");
    const fork_path_input = selectOrThrow(document, "#fork-repositories-path", HTMLInputElement);
    const forkable_repositories = selectOrThrow(
        document,
        "#forkable-repositories",
        HTMLSelectElement,
    );
    const preview_element = selectOrThrow(document, "#fork-path-preview");
    const form = selectOrThrow(document, "#fork-repositories-form", HTMLFormElement);
    const submit_button = selectOrThrow(
        document,
        "#submit-fork-repositories-form",
        HTMLButtonElement,
    );

    if (
        project_select_box instanceof HTMLSelectElement &&
        project_fork_radio_button instanceof HTMLInputElement
    ) {
        initProjectDestinationSelection(
            project_select_box,
            fork_path_input,
            project_fork_radio_button,
            personal_fork_radio_button,
            fork_path_form_element,
        );
    }

    initForkPathPreview(
        preview_element,
        forkable_repositories,
        personal_fork_radio_button,
        fork_path_input,
        project_select_box,
        project_fork_radio_button,
    );
    initSubmitButton(form, submit_button, forkable_repositories);
});
