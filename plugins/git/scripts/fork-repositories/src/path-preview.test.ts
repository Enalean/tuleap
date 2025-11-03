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

import { describe, it, expect, beforeEach } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { initForkPathPreview } from "./path-preview";

const username = "joe-lasticot";

describe("path-preview", () => {
    let doc: Document,
        project_select_box: HTMLSelectElement,
        forkable_repositories: HTMLSelectElement,
        fork_path_input: HTMLInputElement,
        project_fork_radio_button: HTMLInputElement,
        personal_fork_radio_button: HTMLInputElement,
        preview_element: HTMLElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        doc.body.insertAdjacentHTML(
            "beforeend",
            `
            <form>
                <select multiple data-test="forkable-repositories">
                    <option>repo-1</option>
                    <option>repo-2</option>
                </select>
                <label>
                    <input type="radio" name="fork_type" value="personal-fork" data-test="personal-fork"/>
                    Personal
                </label>
                <label>
                    <input type="radio" name="fork_type" value="project-fork" data-test="project-fork"/>
                    Project
                </label>
                <select data-test="project-select-box">
                    <option data-unix-name="project-1">Project 1</option>
                    <option data-unix-name="project-2">Project 2</option>
                </select>
                <input type="text" data-test="fork-path"/>
                <div data-test="preview-element" data-user-name="${username}"></div>
            </form>
        `,
        );

        project_select_box = selectOrThrow(
            doc,
            "[data-test=project-select-box]",
            HTMLSelectElement,
        );
        fork_path_input = selectOrThrow(doc, "[data-test=fork-path]", HTMLInputElement);
        project_fork_radio_button = selectOrThrow(
            doc,
            "[data-test=project-fork]",
            HTMLInputElement,
        );
        personal_fork_radio_button = selectOrThrow(
            doc,
            "[data-test=personal-fork]",
            HTMLInputElement,
        );
        forkable_repositories = selectOrThrow(
            doc,
            "[data-test=forkable-repositories]",
            HTMLSelectElement,
        );
        preview_element = selectOrThrow(doc, "[data-test=preview-element]", HTMLElement);

        initForkPathPreview(
            preview_element,
            forkable_repositories,
            personal_fork_radio_button,
            fork_path_input,
            project_select_box,
            project_fork_radio_button,
        );
    });

    describe("Given that 'personal fork' is checked,", () => {
        beforeEach(() => {
            personal_fork_radio_button.checked = true;
            personal_fork_radio_button.dispatchEvent(new Event("click"));
        });

        it("When no repository is selected, Then it should display 'u/<username>/...'", () => {
            expect(doc.querySelector("[data-test=previewed-fork-path]")?.textContent).toBe(
                `u/${username}/...`,
            );
        });

        it("When some repositories are selected and there is no custom path, Then it should display 'u/<username>/<repository_name>'", () => {
            for (const option of forkable_repositories.options) {
                option.selected = true;
            }
            forkable_repositories.dispatchEvent(new Event("change"));

            const previews = doc.querySelectorAll("[data-test=previewed-fork-path]");

            expect(previews).toHaveLength(2);
            expect(previews[0].textContent).toBe(`u/${username}/repo-1`);
            expect(previews[1].textContent).toBe(`u/${username}/repo-2`);
        });

        it("When some repositories are selected and there is a custom path, Then it should display 'u/<username>/<custom_path>/<repository_name>'", () => {
            for (const option of forkable_repositories.options) {
                option.selected = true;
            }
            fork_path_input.value = "custom-path";
            forkable_repositories.dispatchEvent(new Event("change"));

            const previews = doc.querySelectorAll("[data-test=previewed-fork-path]");

            expect(previews).toHaveLength(2);
            expect(previews[0].textContent).toBe(`u/${username}/custom-path/repo-1`);
            expect(previews[1].textContent).toBe(`u/${username}/custom-path/repo-2`);
        });
    });

    describe("Given that 'project fork' is checked AND a project is selected", () => {
        beforeEach(() => {
            project_fork_radio_button.checked = true;
            project_select_box.selectedIndex = 0;
            project_select_box.dispatchEvent(new Event("change"));
        });

        it("When no repository is selected, Then it should display '<project-unix-name>/...'", () => {
            expect(doc.querySelector("[data-test=previewed-fork-path]")?.textContent).toBe(
                `project-1/...`,
            );
        });

        it("When some repositories are selected, Then it should display '<project-unix-name>/<repository_name>'", () => {
            for (const option of forkable_repositories.options) {
                option.selected = true;
            }
            forkable_repositories.dispatchEvent(new Event("change"));

            const previews = doc.querySelectorAll("[data-test=previewed-fork-path]");

            expect(previews).toHaveLength(2);
            expect(previews[0].textContent).toBe(`project-1/repo-1`);
            expect(previews[1].textContent).toBe(`project-1/repo-2`);
        });
    });
});
