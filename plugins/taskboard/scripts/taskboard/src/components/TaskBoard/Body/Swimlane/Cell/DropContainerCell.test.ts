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

import type { Slots, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DropContainerCell from "./DropContainerCell.vue";
import type { ColumnDefinition, Swimlane } from "../../../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { RootState } from "../../../../../store/type";
import AddCard from "../Card/Add/AddCard.vue";

function getWrapper(
    column: ColumnDefinition,
    can_add_in_place: boolean,
    slots: Slots = {},
): Wrapper<DropContainerCell> {
    const swimlane = { card: { id: 1 } } as Swimlane;

    return shallowMount(DropContainerCell, {
        propsData: {
            column,
            swimlane,
        },
        mocks: {
            $store: createStoreMock({
                state: {
                    card_being_dragged: null,
                    column: {},
                    swimlane: {},
                } as RootState,
                getters: {
                    "column/accepted_trackers_ids": (): number[] => [],
                    can_add_in_place: (): boolean => can_add_in_place,
                    "swimlane/is_there_at_least_one_children_to_display": (): boolean => true,
                },
            }),
        },
        slots,
    });
}

describe("DropContainerCell", () => {
    it(`Given the column is expanded, it displays the content of the cell`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column, false, {
            default: '<div class="my-slot-content"></div>',
        });

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(false);
        expect(wrapper.find(".my-slot-content").exists()).toBe(true);
    });

    it(`Given the column is collapsed, it does not display the content of the cell`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, false, {
            default: '<div class="my-slot-content"></div>',
        });

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(true);
        expect(wrapper.find(".my-slot-content").exists()).toBe(false);
    });

    it(`informs the pointerenter when the column is collapsed`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, false);

        wrapper.trigger("pointerenter");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/pointerEntersColumn", column);
    });

    it(`does not inform the pointerenter when the column is expanded`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column, false);

        wrapper.trigger("pointerenter");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`informs the pointerleave when the column is collapsed`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, false);

        wrapper.trigger("pointerleave");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/pointerLeavesColumn", column);
    });

    it(`does not inform the pointerleave when the column is expanded`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column, false);

        wrapper.trigger("pointerleave");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`when the column is collapsed and a card is being dragged,
        it won't inform the pointerleave
        because too many events are triggered and we want to keep the collapsed column styling`, () => {
        const column = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, false);
        wrapper.vm.$store.state.card_being_dragged = {
            tracker_id: 12,
            card_id: 15,
        };

        wrapper.trigger("pointerleave");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
    });

    it(`expands the column when user clicks on the collapsed column cell`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, false);

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("column/expandColumn", column);
    });

    it(`does not expand the column when user clicks on the expanded column cell`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column, false);

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
    });

    describe("renders the AddCard component only when it is possible", () => {
        it(`renders the button when the tracker of the swimlane allows to add cards in place`, () => {
            const column = { is_collapsed: false } as ColumnDefinition;
            const wrapper = getWrapper(column, true);

            expect(wrapper.findComponent(AddCard).exists()).toBe(true);
            expect(wrapper.classes("taskboard-cell-with-add-form")).toBe(true);
        });

        it(`does not render the AddCard component
            when the tracker of the swimlane disallows to add cards in place`, () => {
            const column = { is_collapsed: false } as ColumnDefinition;
            const wrapper = getWrapper(column, false);

            expect(wrapper.findComponent(AddCard).exists()).toBe(false);
            expect(wrapper.classes("taskboard-cell-with-add-form")).toBe(false);
        });

        it(`does not render the AddCard component when the column is collapsed`, () => {
            const column = { is_collapsed: true } as ColumnDefinition;
            const wrapper = getWrapper(column, true);

            expect(wrapper.findComponent(AddCard).exists()).toBe(false);
            expect(wrapper.classes("taskboard-cell-with-add-form")).toBe(false);
        });
    });
});
