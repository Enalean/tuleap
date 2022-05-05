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

import type { HostElement } from "./CheckboxField";
import { buildCheckbox } from "./CheckboxField";
import type { CheckboxFieldControllerType } from "./CheckboxFieldController";
import { CheckboxFieldController } from "./CheckboxFieldController";

describe("CheckboxField", () => {
    let doc: Document,
        target: ShadowRoot,
        controller: CheckboxFieldControllerType,
        bind_value_ids: Array<number | null>;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
        bind_value_ids = [1, null, 3, 4];
        controller = CheckboxFieldController(
            {
                field_id: 1060,
                label: "Numbers",
                values: [
                    {
                        id: 1,
                        label: "One",
                    },
                    {
                        id: 2,
                        label: "Two",
                    },
                    {
                        id: 3,
                        label: "Three",
                    },
                    {
                        id: 4,
                        label: "Four",
                    },
                ],
                required: true,
            },
            bind_value_ids,
            false
        );
    });

    it("should ask the controller to update the checkbox state when it has been changed", () => {
        jest.spyOn(controller, "setCheckboxValue");

        const host: HostElement = {
            controller,
            field_presenter: controller.buildPresenter(),
        } as unknown as HostElement;

        const value_index = 0;
        const value = {
            id: 1,
            label: "One",
            is_checked: true,
        };

        const update = buildCheckbox(host, value, value_index);

        update(host, target);

        const checkbox = target.querySelector("[data-test=checkbox-field-input]");
        if (!(checkbox instanceof HTMLInputElement)) {
            throw new Error("Checkbox not found in target");
        }

        checkbox.checked = false;
        checkbox.dispatchEvent(new Event("change"));

        expect(controller.setCheckboxValue).toHaveBeenCalledWith(value.id, value_index, false);
        expect(host.field_presenter.checkbox_values[value_index].is_checked).toBe(false);
        expect(bind_value_ids[value_index]).toBeNull();
    });
});
