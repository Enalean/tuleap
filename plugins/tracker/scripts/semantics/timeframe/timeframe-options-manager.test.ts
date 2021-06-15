/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { init } from "./timeframe-options-manager";

function populateSelectBox(
    select: HTMLSelectElement,
    options: { name: string; value: string }[]
): void {
    const empty_option = document.createElement("option");
    empty_option.setAttribute("name", "");
    empty_option.setAttribute("value", "");
    select.appendChild(empty_option);

    options.forEach((option) => {
        const new_option = document.createElement("option");
        new_option.setAttribute("name", option.name);
        new_option.setAttribute("value", option.value);

        select.appendChild(new_option);
    });
}

function getNamedOption(select: HTMLSelectElement, option_name: string): HTMLOptionElement {
    const option = select.options.namedItem(option_name);
    if (!option) {
        throw new Error(`Option named ${option_name} not found in the provided <select>`);
    }

    return option;
}

describe("timeframe-options-manager", () => {
    let doc: HTMLDocument,
        options_container: HTMLElement,
        start_date_select: HTMLSelectElement,
        end_date_select: HTMLSelectElement,
        duration_select: HTMLSelectElement,
        option_end_date_radio_button: HTMLInputElement,
        option_duration_radio_button: HTMLInputElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        options_container = document.createElement("div");
        start_date_select = document.createElement("select");
        end_date_select = document.createElement("select");
        duration_select = document.createElement("select");
        option_end_date_radio_button = document.createElement("input");
        option_duration_radio_button = document.createElement("input");

        end_date_select.id = "end-date-field";
        duration_select.id = "duration-field";

        option_end_date_radio_button.id = "radio-button-end-date";
        option_end_date_radio_button.type = "radio";
        option_end_date_radio_button.setAttribute("data-target-selector", "end-date-field");
        option_end_date_radio_button.classList.add("semantic-timeframe-option-radio");

        option_duration_radio_button.id = "radio-button-duration";
        option_duration_radio_button.type = "radio";
        option_duration_radio_button.setAttribute("data-target-selector", "duration-field");
        option_duration_radio_button.classList.add("semantic-timeframe-option-radio");

        options_container.appendChild(option_end_date_radio_button);
        options_container.appendChild(option_duration_radio_button);

        doc.body.appendChild(options_container);
        doc.body.appendChild(start_date_select);
        doc.body.appendChild(end_date_select);
        doc.body.appendChild(duration_select);

        populateSelectBox(start_date_select, [
            { value: "1001", name: "field-1001" },
            { value: "1002", name: "field-1002" },
            { value: "1003", name: "field-1003" },
        ]);
        populateSelectBox(end_date_select, [
            { value: "1001", name: "field-1001" },
            { value: "1002", name: "field-1002" },
            { value: "1003", name: "field-1003" },
        ]);
    });

    describe("init", () => {
        beforeEach(() => {
            jest.spyOn(start_date_select, "addEventListener");
            jest.spyOn(end_date_select, "addEventListener");
            jest.spyOn(options_container, "addEventListener");
            jest.spyOn(option_end_date_radio_button, "addEventListener");
            jest.spyOn(option_duration_radio_button, "addEventListener");
        });

        it("should make selectors and radio buttons listen for events", () => {
            init(
                doc,
                options_container,
                start_date_select,
                end_date_select,
                option_end_date_radio_button,
                option_duration_radio_button
            );

            expect(start_date_select.addEventListener).toHaveBeenCalledWith(
                "change",
                expect.any(Function)
            );
            expect(end_date_select.addEventListener).toHaveBeenCalledWith(
                "change",
                expect.any(Function)
            );
            expect(options_container.addEventListener).toHaveBeenCalledWith(
                "click",
                expect.any(Function)
            );
            expect(option_end_date_radio_button.addEventListener).toHaveBeenCalledWith(
                "click",
                expect.any(Function)
            );
            expect(option_duration_radio_button.addEventListener).toHaveBeenCalledWith(
                "click",
                expect.any(Function)
            );
        });

        it("should disable already selected options in start date and end date selectors", () => {
            getNamedOption(start_date_select, "field-1001").selected = true;
            getNamedOption(end_date_select, "field-1002").selected = true;
            option_end_date_radio_button.checked = true;

            init(
                doc,
                options_container,
                start_date_select,
                end_date_select,
                option_end_date_radio_button,
                option_duration_radio_button
            );

            expect(getNamedOption(start_date_select, "field-1002").disabled).toBe(true);
            expect(getNamedOption(end_date_select, "field-1001").disabled).toBe(true);

            expect(duration_select.disabled).toBe(true);
            expect(duration_select.required).toBe(false);
        });

        it("should disable the duration select when the end date mode is selected", () => {
            option_end_date_radio_button.checked = true;

            init(
                doc,
                options_container,
                start_date_select,
                end_date_select,
                option_end_date_radio_button,
                option_duration_radio_button
            );

            expect(duration_select.disabled).toBe(true);
            expect(duration_select.required).toBe(false);

            expect(end_date_select.disabled).toBe(false);
            expect(end_date_select.required).toBe(true);
        });
    });

    describe("modes management", () => {
        beforeEach(() => {
            init(
                doc,
                options_container,
                start_date_select,
                end_date_select,
                option_end_date_radio_button,
                option_duration_radio_button
            );
        });

        describe("toggleSelectBoxes", () => {
            it("should disable the config of the end date mode when the duration mode is active", () => {
                option_duration_radio_button.checked = true;
                options_container.dispatchEvent(new KeyboardEvent("click"));

                expect(duration_select.disabled).toBe(false);
                expect(duration_select.required).toBe(true);

                expect(end_date_select.disabled).toBe(true);
                expect(end_date_select.required).toBe(false);
            });

            it("should disable the config of the duration mode when the end date mode is active", () => {
                option_end_date_radio_button.checked = true;
                options_container.dispatchEvent(new KeyboardEvent("click"));

                expect(duration_select.disabled).toBe(true);
                expect(duration_select.required).toBe(false);

                expect(end_date_select.disabled).toBe(false);
                expect(end_date_select.required).toBe(true);
            });
        });

        describe("date fields selectors management", () => {
            beforeEach(() => {
                option_end_date_radio_button.checked = true;
                options_container.dispatchEvent(new KeyboardEvent("click"));
            });

            it("When a value is selected in the start date select, then it should disable it in the end date select and conversely", () => {
                getNamedOption(start_date_select, "field-1001").selected = true;
                start_date_select.dispatchEvent(new Event("change"));

                expect(getNamedOption(end_date_select, "field-1001").disabled).toBe(true);
                expect(getNamedOption(end_date_select, "field-1002").disabled).toBe(false);
                expect(getNamedOption(end_date_select, "field-1003").disabled).toBe(false);

                getNamedOption(end_date_select, "field-1003").selected = true;
                end_date_select.dispatchEvent(new Event("change"));

                expect(getNamedOption(start_date_select, "field-1001").disabled).toBe(false);
                expect(getNamedOption(start_date_select, "field-1002").disabled).toBe(false);
                expect(getNamedOption(start_date_select, "field-1003").disabled).toBe(true);

                getNamedOption(start_date_select, "field-1001").selected = false;
                getNamedOption(start_date_select, "field-1002").selected = true;
                start_date_select.dispatchEvent(new Event("change"));

                expect(getNamedOption(end_date_select, "field-1001").disabled).toBe(false);
                expect(getNamedOption(end_date_select, "field-1002").disabled).toBe(true);
                expect(getNamedOption(end_date_select, "field-1003").disabled).toBe(false);
            });
        });
    });
});
