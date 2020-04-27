/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { autoSubmitSwitches } from "./switch-autosubmitter";

describe(`description-fields`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = createLocalDocument();
    });

    describe(`when a switch changes`, () => {
        let switch_element: HTMLInputElement;
        beforeEach(() => {
            switch_element = doc.createElement("input");
            switch_element.classList.add("switches");
            doc.body.append(switch_element);
            simulateChange(switch_element);
        });

        it(`will throw if the switch has no data-form-id`, () => {
            expect(() => autoSubmitSwitches(doc, ".switches")).toThrow(
                "Missing data-form-id on switch element"
            );
        });

        it(`will throw if the form id cannot be found`, () => {
            switch_element.dataset.formId = "unknown-form";
            expect(() => autoSubmitSwitches(doc, ".switches")).toThrow(
                "Could not find form id #unknown-form"
            );
        });

        it(`will throw if the form id points to something other than a form`, () => {
            switch_element.dataset.formId = "switch-form";
            const not_a_form = doc.createElement("div");
            not_a_form.id = "switch-form";
            doc.body.appendChild(not_a_form);
            expect(() => autoSubmitSwitches(doc, ".switches")).toThrow(
                "Could not find form id #switch-form"
            );
        });

        it(`will submit the form indicated by data-form-id`, () => {
            switch_element.dataset.formId = "switch-form";
            const form = doc.createElement("form");
            form.id = "switch-form";
            doc.body.appendChild(form);
            const submit = jest.spyOn(form, "submit");

            autoSubmitSwitches(doc, ".switches");
            expect(submit).toHaveBeenCalled();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}

function simulateChange(switch_element: HTMLInputElement): void {
    jest.spyOn(switch_element, "addEventListener").mockImplementation(
        (event: string, handler: EventListenerOrEventListenerObject) => {
            if (handler instanceof Function) {
                handler(new Event("change"));
            }
        }
    );
}
