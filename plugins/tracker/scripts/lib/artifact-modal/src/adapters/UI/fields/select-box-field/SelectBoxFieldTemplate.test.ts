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

import type { SelectBoxFieldPresenter } from "./SelectBoxFieldPresenter";
import type { HostElement } from "./SelectBoxField";
import { buildSelectBox, onSelectChange } from "./SelectBoxFieldTemplate";
import { selectOrThrow } from "@tuleap/dom";
import type { BindValueId } from "../../../../domain/fields/select-box-field/BindValueId";

function getSelectBox(
    presenter: SelectBoxFieldPresenter,
    target: ShadowRoot,
    bind_value_ids: number[] = [],
): HTMLSelectElement {
    const host = {
        field_presenter: presenter,
        bind_value_ids,
    } as unknown as HostElement;

    const update = buildSelectBox(host);
    update(host, target);

    return selectOrThrow(target, "[data-test=selectbox-field-select]", HTMLSelectElement);
}

describe("SelectBoxFieldTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    it("Renders a required select when the field is required to have a value", () => {
        const presenter = {
            is_field_required: true,
            select_box_options: [],
        } as unknown as SelectBoxFieldPresenter;

        const select = getSelectBox(presenter, target);

        expect(select.required).toBe(true);
    });

    it("renders a disabled select when the field is disabled", () => {
        const presenter = {
            is_field_disabled: true,
            select_box_options: [],
        } as unknown as SelectBoxFieldPresenter;

        const select = getSelectBox(presenter, target);

        expect(select.disabled).toBe(true);
    });

    it("renders a select containing with its options", () => {
        const option_1 = {
            id: 101,
            label: "Value 101",
        };
        const option_2 = {
            id: 102,
            label: "Value 101",
        };
        const presenter = {
            select_box_options: [option_1, option_2],
        } as unknown as SelectBoxFieldPresenter;

        const select = getSelectBox(presenter, target, [option_1.id]);

        expect(select.options).toHaveLength(2);

        expect(select.options[0].value).toStrictEqual(String(option_1.id));
        expect(select.options[0].textContent?.trim()).toStrictEqual(option_1.label);
        expect(select.options[0].selected).toBe(true);

        expect(select.options[1].value).toStrictEqual(String(option_2.id));
        expect(select.options[1].textContent?.trim()).toStrictEqual(option_2.label);
        expect(select.options[1].selected).toBe(false);
    });

    it("should bind colors to options when have some", () => {
        const option_1 = {
            id: 101,
            label: "Value 101",
            value_color: "red-wine",
        };
        const option_2 = {
            id: 102,
            label: "Value 101",
            value_color: "blue-waffle",
        };
        const presenter = {
            select_box_options: [option_1, option_2],
        } as unknown as SelectBoxFieldPresenter;

        const select = getSelectBox(presenter, target);
        expect(select.options[0].dataset.colorValue).toBe("red-wine");
        expect(select.options[1].dataset.colorValue).toBe("blue-waffle");
    });

    it("should bind avatar urls to options when they have some", () => {
        const option_1 = {
            id: 101,
            label: "User 102",
            avatar_url: "url/to/user-102/avatar.png",
        };
        const option_2 = {
            id: 102,
            label: "User 103",
            avatar_url: "url/to/user-103/avatar.png",
        };
        const presenter = {
            select_box_options: [option_1, option_2],
        } as unknown as SelectBoxFieldPresenter;

        const select = getSelectBox(presenter, target);
        expect(select.options[0].dataset.avatarUrl).toBe("url/to/user-102/avatar.png");
        expect(select.options[1].dataset.avatarUrl).toBe("url/to/user-103/avatar.png");
    });

    describe("onSelectChange()", () => {
        let doc: Document, host: HostElement, select: HTMLSelectElement;

        beforeEach(() => {
            doc = document.implementation.createHTMLDocument();
            select = doc.createElement("select");
            select.multiple = true;
            host = {
                controller: {
                    setSelectedValue: jest.fn(),
                },
                bind_value_ids: [],
            } as unknown as HostElement;
        });

        const injectSelectedOptionsFromValues = (
            bind_value_ids: ReadonlyArray<BindValueId>,
        ): void => {
            select.append(
                ...bind_value_ids.map((value) => {
                    const option = doc.createElement("option");
                    option.value = String(value);
                    option.selected = true;
                    return option;
                }),
            );
        };

        it("should push all the selected values to the bind_value_ids array", () => {
            const selected_values = [101, 102, 103];

            injectSelectedOptionsFromValues(selected_values);
            onSelectChange(host, {
                target: select,
            } as unknown as Event);

            expect(host.bind_value_ids).toStrictEqual(selected_values);
            expect(host.controller.setSelectedValue).toHaveBeenCalledWith(selected_values);
        });

        it('When some selected values contains the character "_", Then it should not try to parse them as a Number', () => {
            const selected_values = ["101_2", "101_3", 1234];

            injectSelectedOptionsFromValues(selected_values);
            onSelectChange(host, {
                target: select,
            } as unknown as Event);

            expect(host.bind_value_ids).toStrictEqual(selected_values);
            expect(host.controller.setSelectedValue).toHaveBeenCalledWith(selected_values);
        });
    });
});
