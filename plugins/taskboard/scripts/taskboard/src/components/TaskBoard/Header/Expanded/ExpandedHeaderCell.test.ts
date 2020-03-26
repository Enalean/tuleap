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
import ExpandedHeaderCell from "./ExpandedHeaderCell.vue";
import { createStoreMock } from "../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { UserState } from "../../../../store/user/type";
import { ColumnDefinition } from "../../../../type";
import WrongColorPopover from "./WrongColorPopover.vue";

describe("ExpandedHeaderCell", () => {
    it("displays a cell with the column label", () => {
        const wrapper = shallowMount(ExpandedHeaderCell, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_is_admin: false } as UserState } }),
            },
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "",
                } as ColumnDefinition,
            },
        });

        const label = wrapper.get("[data-test=label]");
        expect(label.classes("taskboard-header-label")).toBe(true);
        expect(label.text()).toBe("To do");
    });

    it("displays a cell without color", () => {
        const wrapper = shallowMount(ExpandedHeaderCell, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_is_admin: false } as UserState } }),
            },
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "",
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("tlp-swatch-fiesta-red")).toBe(false);
    });

    it("displays a cell with color", () => {
        const wrapper = shallowMount(ExpandedHeaderCell, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_is_admin: false } as UserState } }),
            },
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "fiesta-red",
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("tlp-swatch-fiesta-red")).toBe(true);
    });

    it("displays a cell with default color", () => {
        const wrapper = shallowMount(ExpandedHeaderCell, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_is_admin: false } as UserState } }),
            },
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "#F8F8F8",
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("tlp-swatch-fiesta-red")).toBe(false);
    });

    it("displays a cell with legacy color to regular users", () => {
        const wrapper = shallowMount(ExpandedHeaderCell, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_is_admin: false } as UserState } }),
            },
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "#87DBEF",
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("tlp-swatch-fiesta-red")).toBe(false);
        expect(wrapper.contains(WrongColorPopover)).toBe(false);
    });

    it("displays a cell with legacy color to admin users", () => {
        const wrapper = shallowMount(ExpandedHeaderCell, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_is_admin: true } as UserState } }),
            },
            propsData: {
                column: {
                    id: 2,
                    label: "To do",
                    color: "#87DBEF",
                } as ColumnDefinition,
            },
        });

        expect(wrapper.classes("tlp-swatch-fiesta-red")).toBe(false);
        expect(wrapper.contains(WrongColorPopover)).toBe(true);
    });
});
