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

import { disableSubmitAfterArtifactEdition } from "./disable-submit-buttons";

describe("disableSubmitAfterArtifactEdition", () => {
    let doc: Document;
    let form: HTMLFormElement;
    let button_submit_and_stay: HTMLButtonElement;
    let button_submit_and_continue: HTMLButtonElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument("Test Document");
        form = doc.createElement("form");
        form.className = "artifact-form";

        button_submit_and_stay = doc.createElement("button");
        button_submit_and_stay.className = "submit-artifact-button";
        button_submit_and_stay.name = "submit_and_stay";

        button_submit_and_continue = doc.createElement("button");
        button_submit_and_continue.className = "submit-artifact-button";
        button_submit_and_continue.name = "submit_and_continue";

        form.appendChild(button_submit_and_stay);
        form.appendChild(button_submit_and_continue);
        doc.body.appendChild(form);
    });

    it("should disable the button and call form.submit() when a button is clicked", () => {
        const submit_spy = jest.spyOn(form, "submit");
        disableSubmitAfterArtifactEdition(doc);

        button_submit_and_stay.click();

        expect(button_submit_and_stay.disabled).toBe(true);
        expect(submit_spy).toHaveBeenCalled();
    });

    it("should disable button and fill a hidden input so the backend can get the parameter in request when submit_and_stay is clicked", () => {
        disableSubmitAfterArtifactEdition(doc);

        button_submit_and_stay.click();

        expect(button_submit_and_stay.disabled).toBe(true);
        expect(doc.querySelector('input[type="hidden"][name="submit_and_stay"]')).not.toBeNull();
        expect(doc.querySelector('input[type="hidden"][name="submit_and_continue"]')).toBeNull();
    });

    it("should disable button and fill a hidden input so the backend can get the parameter in request when submit_and_continue is clicked", () => {
        disableSubmitAfterArtifactEdition(doc);

        button_submit_and_continue.click();

        expect(button_submit_and_continue.disabled).toBe(true);
        expect(doc.querySelector('input[type="hidden"][name="submit_and_stay"]')).toBeNull();
        expect(
            doc.querySelector('input[type="hidden"][name="submit_and_continue"]'),
        ).not.toBeNull();
    });

    it("should do nothing if there is no form", () => {
        doc.body.removeChild(form);

        expect(() => disableSubmitAfterArtifactEdition(doc)).not.toThrow();
    });
});
