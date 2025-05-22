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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import CellCustomProperty from "./CellCustomProperty.vue";
import type { ItemSearchResult } from "../../../../type";

describe("CellCustomProperty", () => {
    const item: ItemSearchResult = {
        custom_properties: {
            field_123: {
                type: "string",
                value: "Lorem ipsum",
            },
            field_124: {
                type: "list",
                values: ["Am", "Stram"],
            },
            field_125: {
                type: "date",
                value: "2022-01-30",
            },
            field_225: {
                type: "date",
                value: null,
            },
        },
    } as unknown as ItemSearchResult;

    it("should display a string property", () => {
        const wrapper = shallowMount(CellCustomProperty, {
            props: {
                column_name: "field_123",
                item,
            },
        });

        expect(wrapper.vm.value_string).toBe("Lorem ipsum");
        expect(wrapper.html()).toBe(`<cell-string-stub></cell-string-stub>`);
    });

    it("should display a list property", () => {
        const wrapper = shallowMount(CellCustomProperty, {
            props: {
                column_name: "field_124",
                item,
            },
        });

        expect(wrapper.vm.value_list).toBe("Am, Stram");
        expect(wrapper.html()).toBe(`<cell-string-stub></cell-string-stub>`);
    });

    it("should display a date property", () => {
        const wrapper = shallowMount(CellCustomProperty, {
            props: {
                column_name: "field_125",
                item,
            },
        });

        expect(wrapper.vm.value_date).toBe("2022-01-30");
        expect(wrapper.html()).toBe(`<cell-date-stub date="2022-01-30"></cell-date-stub>`);
    });

    it("should display an empty cell when the item does not have the column in its custom properties", () => {
        const wrapper = shallowMount(CellCustomProperty, {
            props: {
                column_name: "field_126",
                item,
            },
        });

        expect(wrapper.html()).toBe(`<td></td>`);
    });

    it("should display an empty cell when the date is null", () => {
        const wrapper = shallowMount(CellCustomProperty, {
            props: {
                column_name: "field_225",
                item,
            },
        });

        expect(wrapper.html()).toBe(`<td></td>`);
    });
});
