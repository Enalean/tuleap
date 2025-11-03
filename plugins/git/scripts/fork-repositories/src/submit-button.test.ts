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
import { initSubmitButton } from "./submit-button";

describe("submit-button", () => {
    let doc: Document,
        submit_button: HTMLButtonElement,
        forkable_repositories: HTMLSelectElement,
        form: HTMLFormElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        doc.body.insertAdjacentHTML(
            "beforeend",
            `
            <form>
                <select multiple>
                    <option selected>repo 1</option>
                    <option>repo 2</option>
                </select>
                <button type="submit">Submit</button>
            </form>
        `,
        );

        submit_button = selectOrThrow(doc, "button[type=submit]", HTMLButtonElement);
        forkable_repositories = selectOrThrow(doc, "select", HTMLSelectElement);
        form = selectOrThrow(doc, "form", HTMLFormElement);
    });

    it("When no repository is selected, then the submit button should be disabled", () => {
        initSubmitButton(form, submit_button, forkable_repositories);
        expect(submit_button.disabled).toBe(false);

        for (const option of forkable_repositories.options) {
            option.selected = false;
        }

        forkable_repositories.dispatchEvent(new Event("change"));
        expect(submit_button.disabled).toBe(true);
    });

    it("When the form has been submitted, then the submit button should be disabled", () => {
        initSubmitButton(form, submit_button, forkable_repositories);
        expect(submit_button.disabled).toBe(false);

        form.dispatchEvent(new Event("submit"));
        expect(submit_button.disabled).toBe(true);
    });
});
