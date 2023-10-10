/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { listenToPreferenceChange } from "./dates-display";
import { RelativeDateElement } from "@tuleap/tlp-relative-date";

function appendTheDisplayPreferencesSelect(doc: Document): HTMLSelectElement {
    const select = document.createElement("select");
    select.setAttribute("id", "relative-dates-display");

    const relative_first_option = document.createElement("option");
    const absolute_tooltip_option = document.createElement("option");

    relative_first_option.value = "relative_first-absolute_shown";
    relative_first_option.text = "Relative date first";
    relative_first_option.selected = true;

    absolute_tooltip_option.value = "absolute_first-relative_tooltip";
    absolute_tooltip_option.text = "Absolute date only";

    select.add(relative_first_option);
    select.add(absolute_tooltip_option);

    doc.body.appendChild(select);

    return select;
}

function appendTheTlpRelativeDateElement(doc: Document): RelativeDateElement {
    const container = document.createElement("div");

    container.innerHTML = `<tlp-relative-date
        date="2020/08/04"
        absolute-date="2020/08/04"
        placement="right"
        preference="absolute"
        locale="en_US"
    >2020/08/04</tlp-relative-date>`;

    const tlp_local_time = container.querySelector("tlp-relative-date");
    if (!(tlp_local_time instanceof RelativeDateElement)) {
        throw Error("Unable to find just created element");
    }

    doc.body.appendChild(container);

    return tlp_local_time;
}

describe("dates display", (): void => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe("Elements not found during initialization", (): void => {
        it("throws an error when the <select> containing the relative dates display preference can't be found", (): void => {
            expect(() => listenToPreferenceChange(doc)).toThrow(
                "Unable to find the relative dates display preferences <select>",
            );
        });

        it("throws an error when the <tlp-relative-date> element can't be found", (): void => {
            appendTheDisplayPreferencesSelect(doc);

            expect(() => listenToPreferenceChange(doc)).toThrow(
                "Unable to find the <tlp-relative-date> component",
            );
        });
    });

    describe("Event listeners", () => {
        let selectbox: HTMLSelectElement;
        let relative_date: RelativeDateElement;

        beforeEach(() => {
            selectbox = appendTheDisplayPreferencesSelect(doc);
            relative_date = appendTheTlpRelativeDateElement(doc);
        });

        it("listens to the <select> changes", (): void => {
            jest.spyOn(selectbox, "addEventListener");

            listenToPreferenceChange(doc);

            expect(selectbox.addEventListener).toHaveBeenCalledWith("change", expect.any(Function));
        });

        it("inits the <tlp-relative-date> element", () => {
            expect(relative_date.preference).toBe("absolute");
            expect(relative_date.placement).toBe("right");

            listenToPreferenceChange(doc);

            expect(relative_date.preference).toBe("relative");
            expect(relative_date.placement).toBe("right");
        });

        it("updates the <tlp-relative-dates> preference and placement", () => {
            listenToPreferenceChange(doc);

            expect(relative_date.preference).toBe("relative");
            expect(relative_date.placement).toBe("right");

            selectbox.selectedIndex = 1;
            selectbox.dispatchEvent(new Event("change"));

            expect(relative_date.preference).toBe("absolute");
            expect(relative_date.placement).toBe("tooltip");
        });
    });
});
