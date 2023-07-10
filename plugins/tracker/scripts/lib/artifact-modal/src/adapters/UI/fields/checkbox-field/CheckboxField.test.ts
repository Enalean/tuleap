/*
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

import { selectOrThrow } from "@tuleap/dom";
import type { HostElement } from "./CheckboxField";
import { buildCheckbox } from "./CheckboxField";
import type { CheckboxFieldControllerType } from "./CheckboxFieldController";
import { CheckboxFieldController } from "./CheckboxFieldController";
import type { CheckboxFieldValuePresenter } from "./CheckboxFieldPresenter";

describe("CheckboxField", () => {
    let doc: Document,
        controller: CheckboxFieldControllerType,
        bind_value_ids: Array<number | null>,
        value_index: number,
        value: CheckboxFieldValuePresenter;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        bind_value_ids = [1, null, 3, 4];
        controller = CheckboxFieldController(
            {
                field_id: 1060,
                label: "Numbers",
                values: [
                    { id: 1, label: "One" },
                    { id: 2, label: "Two" },
                    { id: 3, label: "Three" },
                    { id: 4, label: "Four" },
                ],
                required: true,
            },
            bind_value_ids,
            false
        );
        value_index = 0;
        value = { id: 1, label: "One", is_checked: true };
    });

    const getHost = (): HostElement => {
        const element = doc.createElement("div");
        return Object.assign(element, {
            controller,
            field_presenter: controller.buildPresenter(),
        } as HostElement);
    };

    const renderCheckbox = (host: HostElement): HTMLInputElement => {
        const update = buildCheckbox(host, value, value_index);
        update(host, host);
        return selectOrThrow(host, "[data-test=checkbox-field-input]", HTMLInputElement);
    };

    it("should ask the controller to update the checkbox state when it has been changed", () => {
        jest.spyOn(controller, "setCheckboxValue");

        const host = getHost();
        const checkbox = renderCheckbox(host);
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event("change"));

        expect(controller.setCheckboxValue).toHaveBeenCalledWith(value.id, value_index, false);
        expect(host.field_presenter.checkbox_values[value_index].is_checked).toBe(false);
        expect(bind_value_ids[value_index]).toBeNull();
    });

    it(`dispatches a bubbling "change" event when its inner checkbox is changed
        so that the modal shows a warning when closed`, () => {
        value = { id: 1, label: "One", is_checked: false };
        const host = getHost();
        const checkbox = renderCheckbox(host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });

        checkbox.checked = true;
        checkbox.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });
});
