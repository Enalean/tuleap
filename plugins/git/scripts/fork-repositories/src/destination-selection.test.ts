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
import { initProjectDestinationSelection } from "./destination-selection";

describe("destination-selection", () => {
    let doc: Document,
        project_select_box: HTMLSelectElement,
        fork_path_input: HTMLInputElement,
        project_fork_radio_button: HTMLInputElement,
        personal_fork_radio_button: HTMLInputElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        doc.body.insertAdjacentHTML(
            "beforeend",
            `
            <form>
                <label>
                    <input type="radio" name="fork_type" value="personal-fork" data-test="personal-fork"/>
                    Personal
                </label>
                <label>
                    <input type="radio" name="fork_type" value="project-fork" data-test="project-fork"/>
                    Project
                </label>
                <select data-test="project-select-box">
                    <option>Project 1</option>
                    <option>Project 2</option>
                </select>
                <input type="text" data-test="fork-path"/>
                <button type="submit" data-test="submit-button">Submit</button>
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

        initProjectDestinationSelection(
            project_select_box,
            fork_path_input,
            project_fork_radio_button,
            personal_fork_radio_button,
        );
    });

    it("When the 'personal fork' radio button is clicked, then it should disable the project select box and enable the fork path input", () => {
        personal_fork_radio_button.dispatchEvent(new Event("click"));

        expect(project_select_box.disabled).toBe(true);
        expect(fork_path_input.disabled).toBe(false);
    });

    it("When the 'project fork' radio button is clicked, then it should enable the project select box and disable the fork path input", () => {
        project_fork_radio_button.dispatchEvent(new Event("click"));

        expect(project_select_box.disabled).toBe(false);
        expect(fork_path_input.disabled).toBe(true);
    });
});
