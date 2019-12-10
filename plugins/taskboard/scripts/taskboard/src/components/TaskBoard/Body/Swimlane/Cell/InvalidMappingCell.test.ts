/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { Card, ColumnDefinition, Swimlane } from "../../../../../type";
import { RootState } from "../../../../../store/type";
import InvalidMappingCell from "./InvalidMappingCell.vue";
import AddCard from "../Card/Add/AddCard.vue";

function createWrapper(
    swimlane: Swimlane,
    is_collapsed: boolean,
    can_add_in_place = false
): Wrapper<InvalidMappingCell> {
    const column_done = { id: 3, label: "Done", is_collapsed } as ColumnDefinition;

    return shallowMount(InvalidMappingCell, {
        mocks: {
            $store: createStoreMock({
                state: {
                    column: {
                        columns: [column_done]
                    },
                    swimlane: {}
                } as RootState,
                getters: { can_add_in_place: (): boolean => can_add_in_place }
            })
        },
        propsData: { swimlane, column: column_done }
    });
}

describe(`InvalidMappingCell`, () => {
    it(`When the column is collapsed,
        Then the the cell is marked as collapsed`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(true);
    });

    it(`It informs the mouseenter when the column is collapsed`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);
        const column = wrapper.vm.$store.state.column.columns[0];

        wrapper.trigger("mouseenter");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/mouseEntersColumn", column);
    });

    it(`It does not inform the mouseenter when the column is expanded`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false);

        wrapper.trigger("mouseenter");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`It informs the mouseout when the column is collapsed`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);
        const column = wrapper.vm.$store.state.column.columns[0];

        wrapper.trigger("mouseout");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/mouseLeavesColumn", column);
    });

    it(`It does not inform the mouseout when the column is expanded`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false);

        wrapper.trigger("mouseout");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`it expands the column when user clicks on the collapsed column cell`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);
        const column = wrapper.vm.$store.state.column.columns[0];

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("column/expandColumn", column);
    });

    it(`it does not expand the column when user clicks on the expanded column cell`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false);

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
    });

    it(`Allows to add cards`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false, true);

        expect(wrapper.contains(AddCard)).toBe(true);
    });
});
