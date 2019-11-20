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
import CardWithChildren from "./CardWithChildren.vue";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { Card, ColumnDefinition, Swimlane } from "../../../../type";
import { RootState } from "../../../../store/type";

function createWrapper(swimlane: Swimlane): Wrapper<CardWithChildren> {
    return shallowMount(CardWithChildren, {
        mocks: {
            $store: createStoreMock({
                state: {
                    column: {
                        columns: [
                            {
                                id: 2,
                                label: "To do",
                                mappings: [{ tracker_id: 7, accepts: [{ id: 49 }] }]
                            } as ColumnDefinition,
                            {
                                id: 3,
                                label: "Done",
                                mappings: [{ tracker_id: 7, accepts: [{ id: 50 }] }]
                            } as ColumnDefinition
                        ]
                    }
                } as RootState
            })
        },
        propsData: { swimlane }
    });
}

describe("CardWithChildren", () => {
    it(`when the swimlane is loading children cards,
        it displays the parent card in its own cell with columns skeletons`, () => {
        const swimlane: Swimlane = {
            card: { id: 43 } as Card,
            children_cards: [],
            is_loading_children_cards: true
        };
        const wrapper = createWrapper(swimlane);

        expect(wrapper.element).toMatchSnapshot();
    });
});
