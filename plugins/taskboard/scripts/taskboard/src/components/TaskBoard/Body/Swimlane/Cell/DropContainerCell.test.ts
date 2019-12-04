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

import { shallowMount, Slots, Wrapper } from "@vue/test-utils";
import DropContainerCell from "./DropContainerCell.vue";
import { ColumnDefinition, Swimlane } from "../../../../../type";
import { createStoreMock } from "../../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../../../../store/type";

function getWrapper(column: ColumnDefinition, slots: Slots = {}): Wrapper<DropContainerCell> {
    const swimlane = { card: { id: 1 } } as Swimlane;

    return shallowMount(DropContainerCell, {
        propsData: {
            column,
            swimlane
        },
        mocks: {
            $store: createStoreMock({
                state: { column: {}, swimlane: {} } as RootState,
                getters: {
                    "column/accepted_trackers_ids": (): number[] => []
                }
            })
        },
        slots
    });
}

describe("DropContainerCell", () => {
    it(`Given the column is expanded, it displays the content of the cell`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column, {
            default: '<div class="my-slot-content"></div>'
        });

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(false);
        expect(wrapper.contains(".my-slot-content")).toBe(true);
    });

    it(`Given the column is collapsed, it does not display the content of the cell`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, {
            default: '<div class="my-slot-content"></div>'
        });

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(true);
        expect(wrapper.contains(".my-slot-content")).toBe(false);
    });

    it(`It informs the mouseenter when the column is collapsed`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("mouseenter");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/mouseEntersColumn", column);
    });

    it(`It does not inform the mouseenter when the column is expanded`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("mouseenter");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`It informs the mouseout when the column is collapsed`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("mouseout");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/mouseLeavesColumn", column);
    });

    it(`It does not inform the mouseout when the column is expanded`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("mouseout");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`it expands the column when user clicks on the collapsed column cell`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("column/expandColumn", column);
    });

    it(`it does not expand the column when user clicks on the expanded column cell`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
    });
});
