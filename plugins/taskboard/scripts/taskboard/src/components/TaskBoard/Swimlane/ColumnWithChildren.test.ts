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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import { Card, ColumnDefinition, Swimlane } from "../../../type";
import ChildCard from "../Card/ChildCard.vue";
import CardSkeleton from "../Card/CardSkeleton.vue";
import ColumnWithChildren from "./ColumnWithChildren.vue";

function createWrapper(swimlane: Swimlane): Wrapper<ColumnWithChildren> {
    const todo: ColumnDefinition = {
        id: 2,
        label: "To do",
        mappings: [{ tracker_id: 7, accepts: [{ id: 49 }] }]
    } as ColumnDefinition;

    const done: ColumnDefinition = {
        id: 3,
        label: "Done",
        mappings: [{ tracker_id: 7, accepts: [{ id: 50 }] }]
    } as ColumnDefinition;

    return shallowMount(ColumnWithChildren, {
        mocks: {
            $store: createStoreMock({
                state: {
                    columns: [todo, done]
                }
            })
        },
        propsData: { swimlane, column: todo, column_index: 0 }
    });
}

describe("ColumnWithChildren", () => {
    it(`when the swimlane is loading children cards,
        and there isn't any card yet,
        it displays many skeletons`, () => {
        const swimlane: Swimlane = {
            card: { id: 43 } as Card,
            children_cards: [{ id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card],
            is_loading_children_cards: true
        } as Swimlane;
        const wrapper = createWrapper(swimlane);

        expect(wrapper.findAll(ChildCard).length).toBe(0);
        expect(wrapper.findAll(CardSkeleton).length).toBe(4);
    });

    it(`when the swimlane has not yet finished to load children cards,
        it displays card of the column and one skeleton`, () => {
        const swimlane: Swimlane = {
            card: { id: 43 } as Card,
            children_cards: [
                { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card
            ],
            is_loading_children_cards: true
        } as Swimlane;
        const wrapper = createWrapper(swimlane);

        expect(wrapper.findAll(ChildCard).length).toBe(2);
        expect(wrapper.findAll(CardSkeleton).length).toBe(1);
    });

    it(`when the swimlane has loaded children cards,
        it displays card of the column and no skeleton`, () => {
        const swimlane: Swimlane = {
            card: { id: 43 } as Card,
            children_cards: [
                { id: 95, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 102, tracker_id: 7, mapped_list_value: { id: 49 } } as Card,
                { id: 104, tracker_id: 7, mapped_list_value: { id: 50 } } as Card
            ],
            is_loading_children_cards: false
        } as Swimlane;
        const wrapper = createWrapper(swimlane);

        expect(wrapper.findAll(ChildCard).length).toBe(2);
        expect(wrapper.findAll(CardSkeleton).length).toBe(0);
    });
});
