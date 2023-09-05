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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Card, ColumnDefinition, Swimlane } from "../../../../../type";
import ChildCard from "../Card/ChildCard.vue";
import CardSkeleton from "../Skeleton/CardSkeleton.vue";
import ChildrenCell from "./ChildrenCell.vue";
import type { RootState } from "../../../../../store/type";

function createWrapper(
    swimlane: Swimlane,
    is_collapsed: boolean,
    cards_in_cell: Card[],
): Wrapper<ChildrenCell> {
    const todo: ColumnDefinition = {
        id: 2,
        label: "To do",
        mappings: [{ tracker_id: 7, accepts: [{ id: 49 }] }],
        is_collapsed,
    } as ColumnDefinition;

    const done: ColumnDefinition = {
        id: 3,
        label: "Done",
        mappings: [{ tracker_id: 7, accepts: [{ id: 50 }] }],
        is_collapsed,
    } as ColumnDefinition;

    return shallowMount(ChildrenCell, {
        mocks: {
            $store: createStoreMock({
                state: {
                    column: { columns: [todo, done] },
                    swimlane: {},
                } as RootState,
                getters: {
                    "swimlane/cards_in_cell": (): Card[] => cards_in_cell,
                },
            }),
        },
        propsData: { swimlane, column: todo, column_index: 0 },
    });
}

describe("ChildrenCell", () => {
    it(`when the swimlane is loading children cards,
        and there isn't any card yet,
        it displays many skeletons`, () => {
        const swimlane: Swimlane = {
            card: { id: 43 } as Card,
            children_cards: [{ id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card],
            is_loading_children_cards: true,
        } as Swimlane;
        const wrapper = createWrapper(swimlane, false, []);

        expect(wrapper.findAllComponents(ChildCard)).toHaveLength(0);
        expect(wrapper.findAllComponents(CardSkeleton)).toHaveLength(4);
    });

    it(`when the swimlane has not yet finished to load children cards,
        it displays card of the column and one skeleton`, () => {
        const swimlane: Swimlane = {
            card: { id: 43 } as Card,
            children_cards: [
                { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 104, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
            ],
            is_loading_children_cards: true,
        } as Swimlane;
        const wrapper = createWrapper(swimlane, false, swimlane.children_cards);

        expect(wrapper.findAllComponents(ChildCard)).toHaveLength(3);
        expect(wrapper.findAllComponents(CardSkeleton)).toHaveLength(1);
    });

    it(`when the swimlane has loaded children cards,
        it displays card of the column and no skeleton`, () => {
        const swimlane: Swimlane = {
            card: { id: 43 } as Card,
            children_cards: [
                { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 104, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
            ],
            is_loading_children_cards: false,
        } as Swimlane;
        const wrapper = createWrapper(swimlane, false, swimlane.children_cards);

        expect(wrapper.findAllComponents(ChildCard)).toHaveLength(3);
        expect(wrapper.findAllComponents(CardSkeleton)).toHaveLength(0);
    });
});
