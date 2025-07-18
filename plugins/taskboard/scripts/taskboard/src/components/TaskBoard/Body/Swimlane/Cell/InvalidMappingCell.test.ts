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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Card, ColumnDefinition, Swimlane } from "../../../../../type";
import type { RootState } from "../../../../../store/type";
import InvalidMappingCell from "./InvalidMappingCell.vue";
import AddCard from "../Card/Add/AddCard.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";

describe(`InvalidMappingCell`, () => {
    const column_done = { id: 3, label: "Done", is_collapsed: false } as ColumnDefinition;
    const mock_pointer_enter_columns = jest.fn();
    const mock_pointer_leaves_columns = jest.fn();
    const mock_expand_columns = jest.fn();
    function createWrapper(
        swimlane: Swimlane,
        is_collapsed: boolean,
        can_add_in_place_result = false,
    ): VueWrapper<InstanceType<typeof InvalidMappingCell>> {
        column_done.is_collapsed = is_collapsed;

        return shallowMount(InvalidMappingCell, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        column: {
                            state: {
                                columns: [column_done],
                            },
                            mutations: {
                                pointerEntersColumn: mock_pointer_enter_columns,
                                pointerLeavesColumn: mock_pointer_leaves_columns,
                            },
                            actions: {
                                expandColumn: mock_expand_columns,
                            },
                            namespaced: true,
                        },
                    },
                    state: {
                        card_being_dragged: null,
                    } as RootState,
                    getters: {
                        can_add_in_place: () => () => can_add_in_place_result,
                    },
                }),
            },
            props: { swimlane, column: column_done },
        });
    }

    it(`When the column is collapsed,
        Then the the cell is marked as collapsed`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(true);
    });

    it(`informs the pointerenter when the column is collapsed`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);

        wrapper.trigger("pointerenter");
        expect(mock_pointer_enter_columns).toHaveBeenCalledWith(expect.anything(), column_done);
    });

    it(`informs the pointerleave`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);

        wrapper.trigger("pointerleave");
        expect(mock_pointer_leaves_columns).toHaveBeenCalled();
    });

    it(`expands the column when user clicks on the collapsed column cell`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, true);

        wrapper.trigger("click");
        expect(mock_expand_columns).toHaveBeenCalledWith(expect.anything(), column_done);
    });

    it(`Allows to add cards`, () => {
        const wrapper = createWrapper({ card: { id: 43 } as Card } as Swimlane, false, true);

        expect(wrapper.findComponent(AddCard).exists()).toBe(true);
    });
});
