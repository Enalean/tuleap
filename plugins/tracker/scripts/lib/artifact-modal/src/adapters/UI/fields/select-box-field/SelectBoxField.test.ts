/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import type { HostElement } from "./SelectBoxField";
import { getFormElementClasses, SelectBoxField } from "./SelectBoxField";
import { SelectBoxFieldPresenter } from "./SelectBoxFieldPresenter";
import type { BindValueId } from "../../../../domain/fields/select-box-field/BindValueId";

describe("SelectBoxField", () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    const getHost = (): HostElement => {
        const bind_value_ids: ReadonlyArray<BindValueId> = [205];
        const element = doc.createElement("div");
        return Object.assign(element, {
            field_presenter: SelectBoxFieldPresenter.fromField(
                {
                    field_id: 170,
                    label: "Proceed",
                    name: "proceed",
                    type: "sb",
                    bindings: { type: "static" },
                    required: false,
                    default_value: [],
                    values: [
                        { id: 205, label: "submeter", value_color: "" },
                        { id: 462, label: "severalize", value_color: "" },
                    ],
                },
                [205, 462],
                false
            ),
            bind_value_ids,
        } as HostElement);
    };

    const render = (host: HostElement): ShadowRoot => {
        const update = SelectBoxField.content(host);
        update(host, host);
        return host as unknown as ShadowRoot;
    };

    describe("field error state", () => {
        const getFieldClasses = (
            is_field_required: boolean,
            bind_value_ids: ReadonlyArray<BindValueId>
        ): Record<string, boolean> => {
            return getFormElementClasses({
                bind_value_ids,
                field_presenter: {
                    is_field_required,
                } as SelectBoxFieldPresenter,
            } as SelectBoxField);
        };

        it(`Given that the field is required to have a value
            When there is no selected value
            Then it should have the tlp-form-element-error class`, () => {
            expect(getFieldClasses(true, [])).toStrictEqual({
                "tlp-form-element": true,
                "tlp-form-element-error": true,
            });
        });

        it(`Given that the field is required to have a value
            When only the value 100 is selected
            Then it should have the tlp-form-element-error class`, () => {
            expect(getFieldClasses(true, [100])).toStrictEqual({
                "tlp-form-element": true,
                "tlp-form-element-error": true,
            });
        });

        it(`Given that the field is not required to have a value
            When there is no selected value
            Then it should NOT have the tlp-form-element-error class`, () => {
            expect(getFieldClasses(false, [])).toStrictEqual({
                "tlp-form-element": true,
                "tlp-form-element-error": false,
            });
        });

        it(`Given that the field is not required to have a value
            When the only selected value is 100 (none)
            Then it should NOT have the tlp-form-element-error class`, () => {
            expect(getFieldClasses(false, [100])).toStrictEqual({
                "tlp-form-element": true,
                "tlp-form-element-error": false,
            });
        });
    });

    it(`dispatches a bubbling "change" event when its inner select is changed
        so that the modal shows a warning when closed`, () => {
        const host = getHost();
        const target = render(host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });
        const select = selectOrThrow(
            target,
            "[data-test=selectbox-field-select]",
            HTMLSelectElement
        );
        const [first_option] = select.options;
        first_option.selected = true;
        select.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });
});
