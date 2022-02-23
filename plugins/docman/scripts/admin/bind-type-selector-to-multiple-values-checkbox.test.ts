/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { bindTypeSelectorToMultipleValuesCheckbox } from "./bind-type-selector-to-multiple-values-checkbox";

describe("bindTypeSelectorToMultipleValuesCheckbox", () => {
    const TYPE_TEXT = "1";
    const TYPE_STRING = "6";
    const TYPE_DATE = "4";
    const TYPE_LIST = "5";

    it.each<[string, boolean]>([
        [TYPE_TEXT, true],
        [TYPE_STRING, true],
        [TYPE_DATE, true],
        [TYPE_LIST, false],
    ])("when selected type is %s, then checkbox.disabled = %s", (type, is_disabled) => {
        const doc = document.implementation.createHTMLDocument();

        const select = doc.createElement("select");
        select.id = "docman-admin-properties-create-type";
        doc.body.appendChild(select);
        for (const value of [TYPE_TEXT, TYPE_STRING, TYPE_DATE, TYPE_LIST]) {
            const option = doc.createElement("option");
            option.value = value;
            option.selected = value === type;
            select.appendChild(option);
        }

        const checkbox = doc.createElement("input");
        checkbox.type = "checkbox";
        checkbox.id = "docman-admin-properties-create-multiplevalues-allowed";
        doc.body.appendChild(checkbox);

        expect(checkbox.disabled).toBe(false);

        bindTypeSelectorToMultipleValuesCheckbox(doc);

        expect(checkbox.disabled).toBe(is_disabled);
    });

    it.each<[string, boolean]>([
        [TYPE_TEXT, true],
        [TYPE_STRING, true],
        [TYPE_DATE, true],
        [TYPE_LIST, false],
    ])("when select box changes to %s, then checkbox.disabled = %s", (type, is_disabled) => {
        const doc = document.implementation.createHTMLDocument();

        const select = doc.createElement("select");
        select.id = "docman-admin-properties-create-type";
        doc.body.appendChild(select);
        for (const value of [TYPE_TEXT, TYPE_STRING, TYPE_DATE, TYPE_LIST]) {
            const option = doc.createElement("option");
            option.value = value;
            option.selected = false;
            select.appendChild(option);
        }

        const checkbox = doc.createElement("input");
        checkbox.type = "checkbox";
        checkbox.id = "docman-admin-properties-create-multiplevalues-allowed";
        doc.body.appendChild(checkbox);

        expect(checkbox.disabled).toBe(false);

        bindTypeSelectorToMultipleValuesCheckbox(doc);

        select.value = type;
        select.dispatchEvent(new Event("change"));

        expect(checkbox.disabled).toBe(is_disabled);
    });
});
