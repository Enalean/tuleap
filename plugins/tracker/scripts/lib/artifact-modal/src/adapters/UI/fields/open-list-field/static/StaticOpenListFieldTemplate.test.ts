/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "@jest/globals";
import { selectOrThrow } from "@tuleap/dom";
import { setCatalog } from "../../../../../gettext-catalog";
import type { HostElement } from "./StaticOpenListField";
import { renderStaticOpenListField } from "./StaticOpenListFieldTemplate";
import type { StaticOpenListFieldPresenter } from "./StaticOpenListFieldPresenter";

describe("StaticOpenListFieldTemplate", () => {
    let disabled: boolean;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        disabled = false;
    });

    const getRenderedStaticOpenListField = (
        presenter: StaticOpenListFieldPresenter,
    ): ShadowRoot => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        const host = {
            disabled,
            presenter,
        } as unknown as HostElement;
        const render = renderStaticOpenListField(host);
        render(host, target);

        return target;
    };

    it("should render a static open list field with its values", () => {
        const presenter = {
            field_id: "tracker_field_1001",
            required: true,
            label: "Static open list field",
            hint: "Please select a value in the list",
            name: "static_open_list_field",
            is_required_and_empty: false,
            values: [
                {
                    id: "1",
                    label: "Value 1",
                    selected: false,
                    is_hidden: false,
                },
                {
                    id: "2",
                    label: "Value 2",
                    selected: true,
                    is_hidden: false,
                },
            ],
        };

        const field_element = getRenderedStaticOpenListField(presenter);
        const form_element = selectOrThrow(field_element, "[data-test=openlist-field]");
        const select_element = selectOrThrow(
            field_element,
            "[data-test=static-open-list-field-select]",
            HTMLSelectElement,
        );
        const label_element = selectOrThrow(
            field_element,
            "[data-test=static-open-list-field-label]",
        );
        const required_flag_element = selectOrThrow(
            field_element,
            "[data-test=static-open-list-field-required-flag]",
        );

        expect(Array.from(form_element.classList)).toStrictEqual(["tlp-form-element"]);
        expect(required_flag_element).toBeDefined();
        expect(label_element.textContent).toContain(presenter.label);
        expect(select_element.id).toBe(presenter.field_id);
        expect(select_element.disabled).toBe(false);
        expect(select_element.required).toBe(true);
        expect(select_element.title).toBe(presenter.hint);

        expect(select_element.options).toHaveLength(2);

        const [first_option, second_option] = select_element.options;
        if (!first_option || !second_option) {
            throw new Error("Expected to have two Options in the select element.");
        }

        expect(first_option.value).toStrictEqual(String(presenter.values[0].id));
        expect(first_option.textContent?.trim()).toStrictEqual(presenter.values[0].label);
        expect(first_option.selected).toBe(presenter.values[0].selected);

        expect(second_option.value).toStrictEqual(String(presenter.values[1].id));
        expect(second_option.textContent?.trim()).toStrictEqual(presenter.values[1].label);
        expect(second_option.selected).toBe(presenter.values[1].selected);
    });

    it(`When the field is disabled, then:
        - it should have the tlp-form-element-disabled class
        - its select element should be disabled`, () => {
        disabled = true;

        const field_element = getRenderedStaticOpenListField({
            values: [],
        } as unknown as StaticOpenListFieldPresenter);
        const form_element = selectOrThrow(field_element, "[data-test=openlist-field]");
        const select_element = selectOrThrow(
            field_element,
            "[data-test=static-open-list-field-select]",
            HTMLSelectElement,
        );

        expect(Array.from(form_element.classList)).toStrictEqual([
            "tlp-form-element",
            "tlp-form-element-disabled",
        ]);
        expect(select_element.disabled).toBe(true);
    });

    it(`When the field is required, and no value has been selected, then
        - it should have the tlp-form-element-error class
        - an error message should be displayed`, () => {
        const field_element = getRenderedStaticOpenListField({
            values: [],
            is_required_and_empty: true,
        } as unknown as StaticOpenListFieldPresenter);
        const form_element = selectOrThrow(field_element, "[data-test=openlist-field]");
        const error_element = selectOrThrow(
            field_element,
            "[data-test=static-open-list-field-error]",
        );

        expect(Array.from(form_element.classList)).toStrictEqual([
            "tlp-form-element",
            "tlp-form-element-error",
        ]);
        expect(error_element).toBeDefined();
    });
});
