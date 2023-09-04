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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Card, ColumnDefinition, Swimlane } from "../../../../../type";
import type { RootState } from "../../../../../store/type";
import InvalidMappingCell from "./InvalidMappingCell.vue";
import AddCard from "../Card/Add/AddCard.vue";

function createWrapper(
    swimlane: Swimlane,
    is_collapsed: boolean,
    can_add_in_place = false,
): Wrapper<InvalidMappingCell> {
    const column_done = { id: 3, label: "Done", is_collapsed } as ColumnDefinition;

    return shallowMount(InvalidMappingCell, {
        mocks: {
            $store: createStoreMock({
                state: {
                    card_being_dragged: null,
                    column: {
                        columns: [column_done],
                    },
                    swimlane: {},
                } as RootState,
                getters: { can_add_in_place: (): boolean => can_add_in_place },
            }),
        },
        propsData: { swimlane, column: column_done },
    });
}

describe(`InvalidMappingCell`, () => {
    it(`When the column is collapsed,
        Then the the cell is marked as collapsed`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(true);
    });

    it(`informs the pointerenter when the column is collapsed`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);
        const column = wrapper.vm.$store.state.column.columns[0];

        wrapper.trigger("pointerenter");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/pointerEntersColumn", column);
    });

    it(`does not inform the pointerenter when the column is expanded`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false);

        wrapper.trigger("pointerenter");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`informs the pointerleave when the column is collapsed`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);
        const column = wrapper.vm.$store.state.column.columns[0];

        wrapper.trigger("pointerleave");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/pointerLeavesColumn", column);
    });

    it(`does not inform the pointerleave when the column is expanded`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false);

        wrapper.trigger("pointerleave");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`when the column is collapsed and a card is being dragged,
        it won't inform the pointerleave
        because too many events are triggered and we want to keep the collapsed column styling`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false);
        wrapper.vm.$store.state.card_being_dragged = {
            tracker_id: 12,
            card_id: 15,
        };

        wrapper.trigger("pointerleave");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`expands the column when user clicks on the collapsed column cell`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);
        const column = wrapper.vm.$store.state.column.columns[0];

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("column/expandColumn", column);
    });

    it(`does not expand the column when user clicks on the expanded column cell`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false);

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
    });

    it(`Allows to add cards`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false, true);

        expect(wrapper.findComponent(AddCard).exists()).toBe(true);
    });
});
