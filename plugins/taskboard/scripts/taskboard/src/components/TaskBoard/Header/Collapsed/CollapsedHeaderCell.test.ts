/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import { ColumnDefinition } from "../../../../type";
import CollapsedHeaderCell from "./CollapsedHeaderCell.vue";
import ExpandButton from "./ExpandButton.vue";

describe("CollapsedHeaderCell", () => {
    it("displays a cell with expand button", () => {
        const wrapper = shallowMount(CollapsedHeaderCell, {
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "",
                    has_hover: false,
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("taskboard-header-collapsed")).toBe(true);
        expect(wrapper.classes("tlp-swatch-fiesta-red")).toBe(false);
        expect(wrapper.contains(ExpandButton)).toBe(true);
    });

    it("displays a cell with color", () => {
        const wrapper = shallowMount(CollapsedHeaderCell, {
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "fiesta-red",
                    has_hover: false,
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("tlp-swatch-fiesta-red")).toBe(true);
    });

    it(`Given the column has hover, then it shows its label`, () => {
        const wrapper = shallowMount(CollapsedHeaderCell, {
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "fiesta-red",
                    has_hover: true,
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("taskboard-header-collapsed-show-label")).toBe(true);
    });

    it(`Given the column does not have hover, then it does not shows its label`, () => {
        const wrapper = shallowMount(CollapsedHeaderCell, {
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "fiesta-red",
                    has_hover: false,
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("taskboard-header-collapsed-show-label")).toBe(false);
    });
});
