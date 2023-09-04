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

import { init } from "./options-manager";

function populateSelectBox(
    select: HTMLSelectElement,
    options: { name: string; value: string }[],
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

describe("options-manager", () => {
    let method_selector: HTMLSelectElement,
        total_effort_selector: HTMLSelectElement,
        remaining_effort_selector: HTMLSelectElement,
        effort_based_section: HTMLElement,
        links_count_based_section: HTMLElement,
        update_semantic_progress_button: HTMLElement;

    beforeEach(() => {
        update_semantic_progress_button = document.createElement("button");
        effort_based_section = document.createElement("div");
        links_count_based_section = document.createElement("div");
        method_selector = document.createElement("select");
        total_effort_selector = document.createElement("select");
        remaining_effort_selector = document.createElement("select");

        populateSelectBox(method_selector, [
            { value: "effort-based", name: "effort-based" },
            { value: "artifacts-links-count-based", name: "artifacts-links-count-based" },
        ]);
        populateSelectBox(total_effort_selector, [
            { value: "field_1001", name: "field-1001" },
            { value: "field_1002", name: "field-1002" },
        ]);
        populateSelectBox(remaining_effort_selector, [
            { value: "field_1001", name: "field-1001" },
            { value: "field_1002", name: "field-1002" },
        ]);
    });

    describe("init", () => {
        beforeEach(() => {
            jest.spyOn(total_effort_selector, "addEventListener");
            jest.spyOn(remaining_effort_selector, "addEventListener");
            jest.spyOn(method_selector, "addEventListener");
        });

        it("should make selectors listen for value change events", () => {
            init(
                update_semantic_progress_button,
                method_selector,
                effort_based_section,
                total_effort_selector,
                remaining_effort_selector,
                links_count_based_section,
            );

            expect(method_selector.addEventListener).toHaveBeenCalledWith(
                "change",
                expect.any(Function),
            );
            expect(total_effort_selector.addEventListener).toHaveBeenCalledWith(
                "change",
                expect.any(Function),
            );
            expect(remaining_effort_selector.addEventListener).toHaveBeenCalledWith(
                "change",
                expect.any(Function),
            );
        });

        it("should disable already selected options in total effort and remaining effort selectors", () => {
            getNamedOption(total_effort_selector, "field-1001").selected = true;
            getNamedOption(remaining_effort_selector, "field-1002").selected = true;

            init(
                update_semantic_progress_button,
                method_selector,
                effort_based_section,
                total_effort_selector,
                remaining_effort_selector,
                links_count_based_section,
            );

            expect(getNamedOption(total_effort_selector, "field-1002").disabled).toBe(true);
            expect(getNamedOption(remaining_effort_selector, "field-1001").disabled).toBe(true);
        });
    });

    describe("<select> management", () => {
        beforeEach(() => {
            init(
                update_semantic_progress_button,
                method_selector,
                effort_based_section,
                total_effort_selector,
                remaining_effort_selector,
                links_count_based_section,
            );
        });

        describe("toggleComputationMethodConfigSection", () => {
            it("should toggle the right configuration section and disable/enable required inputs if needed", () => {
                const effort_based_option = getNamedOption(method_selector, "effort-based");
                const links_based_option = getNamedOption(
                    method_selector,
                    "artifacts-links-count-based",
                );

                effort_based_option.selected = true;
                method_selector.dispatchEvent(new Event("change"));

                expect(
                    effort_based_section.classList.contains("selected-computation-method-config"),
                ).toBe(true);
                expect(
                    links_count_based_section.classList.contains(
                        "selected-computation-method-config",
                    ),
                ).toBe(false);

                expect(total_effort_selector.getAttribute("disabled")).toBeNull();
                expect(remaining_effort_selector.getAttribute("disabled")).toBeNull();

                links_based_option.selected = true;
                method_selector.dispatchEvent(new Event("change"));

                expect(
                    effort_based_section.classList.contains("selected-computation-method-config"),
                ).toBe(false);
                expect(
                    links_count_based_section.classList.contains(
                        "selected-computation-method-config",
                    ),
                ).toBe(true);

                expect(total_effort_selector.getAttribute("disabled")).toBe("disabled");
                expect(remaining_effort_selector.getAttribute("disabled")).toBe("disabled");
            });

            it("should disable the submit button when the links count section is active and config cannot be defined", () => {
                links_count_based_section.classList.add("links-count-based-config-impossible");

                const effort_based_option = getNamedOption(method_selector, "effort-based");
                const links_based_option = getNamedOption(
                    method_selector,
                    "artifacts-links-count-based",
                );

                links_based_option.selected = true;
                method_selector.dispatchEvent(new Event("change"));

                expect(update_semantic_progress_button.getAttribute("disabled")).toBe("disabled");

                effort_based_option.selected = true;
                method_selector.dispatchEvent(new Event("change"));

                expect(update_semantic_progress_button.getAttribute("disabled")).toBeNull();
            });
        });

        describe("disableAlreadySelectedOptions", () => {
            it("When a value is selected in the total effort field selector, Then it should disable it in the remaining effort one", () => {
                getNamedOption(total_effort_selector, "field-1001").selected = true;
                total_effort_selector.dispatchEvent(new Event("change"));

                expect(remaining_effort_selector.namedItem("field-1001")?.disabled).toBe(true);

                getNamedOption(total_effort_selector, "field-1001").selected = false;
                total_effort_selector.dispatchEvent(new Event("change"));

                expect(remaining_effort_selector.namedItem("field-1001")?.disabled).toBe(false);
            });

            it("When a value is selected in the remaining effort field selector, Then it should disable it in the total effort one", () => {
                getNamedOption(remaining_effort_selector, "field-1001").selected = true;
                remaining_effort_selector.dispatchEvent(new Event("change"));

                expect(total_effort_selector.namedItem("field-1001")?.disabled).toBe(true);

                getNamedOption(remaining_effort_selector, "field-1001").selected = false;
                remaining_effort_selector.dispatchEvent(new Event("change"));

                expect(total_effort_selector.namedItem("field-1001")?.disabled).toBe(false);
            });
        });
    });
});
