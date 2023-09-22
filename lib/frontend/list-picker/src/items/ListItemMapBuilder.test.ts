/**
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import {
    appendGroupedOptionsToSourceSelectBox,
    appendSimpleOptionsToSourceSelectBox,
} from "../test-helpers/select-box-options-generator";
import { ListItemMapBuilder } from "./ListItemMapBuilder";
import type { ListPickerOptions } from "../type";
import type { TemplateResult } from "lit-html";
import { html } from "lit-html";

describe("ListItemBuilder", () => {
    let select: HTMLSelectElement, builder: ListItemMapBuilder, doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select = doc.createElement("select");
        builder = new ListItemMapBuilder(select);
    });

    it("builds the map of the available options inside the source <select>", () => {
        appendSimpleOptionsToSourceSelectBox(select);

        const map = builder.buildListPickerItemsMap();

        expect(map.size).toBe(8);

        const iterator = map.entries();

        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-100",
            {
                id: "list-picker-item-100",
                template: buildTemplateResult("None"),
                label: "None",
                value: "100",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-value_0",
            {
                id: "list-picker-item-value_0",
                template: buildTemplateResult("Value 0"),
                label: "Value 0",
                value: "value_0",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-value_1",
            {
                id: "list-picker-item-value_1",
                template: buildTemplateResult("Value 1"),
                label: "Value 1",
                value: "value_1",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-value_2",
            {
                id: "list-picker-item-value_2",
                template: buildTemplateResult("Value 2"),
                label: "Value 2",
                value: "value_2",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-value_3",
            {
                id: "list-picker-item-value_3",
                template: buildTemplateResult("Value 3"),
                label: "Value 3",
                value: "value_3",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
    });

    it("builds the map of the available grouped options inside the source <select>", () => {
        appendGroupedOptionsToSourceSelectBox(select);

        const map = builder.buildListPickerItemsMap();

        expect(map.size).toBe(6);

        const iterator = map.entries();
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-group1-value_0",
            {
                id: "list-picker-item-group1-value_0",
                template: buildTemplateResult("Value 0"),
                label: "Value 0",
                value: "value_0",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-group1-value_1",
            {
                id: "list-picker-item-group1-value_1",
                template: buildTemplateResult("Value 1"),
                label: "Value 1",
                value: "value_1",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-group1-value_2",
            {
                id: "list-picker-item-group1-value_2",
                template: buildTemplateResult("Value 2"),
                label: "Value 2",
                value: "value_2",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-group2-value_3",
            {
                id: "list-picker-item-group2-value_3",
                template: buildTemplateResult("Value 3"),
                label: "Value 3",
                value: "value_3",
                is_disabled: false,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-group2-value_4",
            {
                id: "list-picker-item-group2-value_4",
                template: buildTemplateResult("Value 4"),
                label: "Value 4",
                value: "value_4",
                is_disabled: false,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toStrictEqual([
            "list-picker-item-group2-value_5",
            {
                id: "list-picker-item-group2-value_5",
                template: buildTemplateResult("Value 5"),
                label: "Value 5",
                value: "value_5",
                is_disabled: true,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
    });

    it("should ignore options with empty value attribute and no content BUT does not remove them from the source <select> options", () => {
        const empty_option = doc.createElement("option");
        empty_option.setAttribute("id", "empty-option");
        empty_option.setAttribute("value", "");
        select.appendChild(empty_option);

        appendSimpleOptionsToSourceSelectBox(select);
        const map = builder.buildListPickerItemsMap();
        const item_with_empty_value = Array.from(map.values()).find((item) => {
            return item.value === "";
        });
        expect(item_with_empty_value).toBeUndefined();
        expect(select.querySelector("option[value='']")).not.toBeNull();
    });

    it("should NOT ignore options with empty value attribute with content", () => {
        const empty_option = doc.createElement("option");
        empty_option.setAttribute("id", "empty-option");
        empty_option.setAttribute("value", "");
        empty_option.textContent = "Some label";
        select.appendChild(empty_option);

        appendSimpleOptionsToSourceSelectBox(select);
        const map = builder.buildListPickerItemsMap();
        const item_with_empty_value = Array.from(map.values()).find((item) => {
            return item.value === "";
        });
        expect(item_with_empty_value).toBeDefined();
        expect(select.querySelector("option[value='']")).not.toBeNull();
    });

    it("should ignore empty options in the angular-modal remove it from the source <select> options", () => {
        appendSimpleOptionsToSourceSelectBox(select);
        select.options[0].value = "?";

        const map = builder.buildListPickerItemsMap();
        const item_with_empty_value = Array.from(map.values()).find((item) => {
            return item.value === "?";
        });
        expect(item_with_empty_value).toBeUndefined();
        expect(select.querySelector("option[value='?']")).toBeNull();
    });

    describe("When a items_template_formatter is passed through the options", () => {
        let options: ListPickerOptions;
        beforeEach(() => {
            options = {
                items_template_formatter: vi
                    .fn()
                    .mockReturnValue(Promise.resolve("A beautiful template")),
            };
            builder = new ListItemMapBuilder(select, options);
        });

        it("should call it for each item once and cache the templates", () => {
            const itemsTemplateFormatter = vi.spyOn(options, "items_template_formatter");
            appendSimpleOptionsToSourceSelectBox(select);
            builder.buildListPickerItemsMap();

            expect(itemsTemplateFormatter).toHaveBeenCalledTimes(5);
            expect(itemsTemplateFormatter.mock.calls[0]).toStrictEqual([html, "100", "None"]);
            expect(itemsTemplateFormatter.mock.calls[1]).toStrictEqual([
                html,
                "value_0",
                "Value 0",
            ]);
            expect(itemsTemplateFormatter.mock.calls[2]).toStrictEqual([
                html,
                "value_1",
                "Value 1",
            ]);
            expect(itemsTemplateFormatter.mock.calls[3]).toStrictEqual([
                html,
                "value_2",
                "Value 2",
            ]);
            expect(itemsTemplateFormatter.mock.calls[4]).toStrictEqual([
                html,
                "value_3",
                "Value 3",
            ]);

            builder.buildListPickerItemsMap();
            expect(itemsTemplateFormatter).toHaveBeenCalledTimes(5);
        });
    });

    function buildTemplateResult(value: string): TemplateResult {
        return html`${value}`;
    }
});
