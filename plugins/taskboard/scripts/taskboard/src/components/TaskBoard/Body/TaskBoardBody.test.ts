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
import type { VueWrapper } from "@vue/test-utils";
import TaskBoardBody from "./TaskBoardBody.vue";
import SwimlaneSkeleton from "./Swimlane/Skeleton/SwimlaneSkeleton.vue";
import CollapsedSwimlane from "./Swimlane/CollapsedSwimlane.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import * as mapper from "../../../helpers/list-value-to-column-mapper";
import InvalidMappingSwimlane from "./Swimlane/InvalidMappingSwimlane.vue";
import type { RootState } from "../../../store/type";
import type { Swimlane, ColumnDefinition } from "../../../type";
import type { SwimlaneState } from "../../../store/swimlane/type";
import type { ColumnState } from "../../../store/column/type";

describe("TaskBoardBody", () => {
    const mock_load_swimlanes = jest.fn();
    function createWrapper(
        swimlanes: Swimlane[],
        are_closed_items_displayed: boolean,
        is_loading_swimlanes: boolean,
    ): VueWrapper<InstanceType<typeof TaskBoardBody>> {
        return shallowMount(TaskBoardBody, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        are_closed_items_displayed,
                    } as RootState,
                    modules: {
                        swimlane: {
                            state: { swimlanes, is_loading_swimlanes } as SwimlaneState,
                            getters: {
                                is_there_at_least_one_children_to_display:
                                    () => (swimlane: Swimlane) =>
                                        swimlane.card.has_children,
                            },
                            actions: {
                                loadSwimlanes: mock_load_swimlanes,
                            },
                            namespaced: true,
                        },
                        column: {
                            state: { columns: [] } as ColumnState,
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    afterEach(() => {
        jest.clearAllMocks();
    });

    it("displays swimlanes for solo cards or cards with children", () => {
        const swimlanes = [
            {
                card: {
                    id: 43,
                    has_children: false,
                    is_open: true,
                    is_collapsed: false,
                },
            } as Swimlane,
            {
                card: {
                    id: 44,
                    has_children: true,
                    is_open: true,
                    is_collapsed: false,
                },
            } as Swimlane,
        ];
        jest.spyOn(mapper, "getColumnOfCard").mockReturnValue({
            id: 21,
            label: "Todo",
        } as ColumnDefinition);
        const wrapper = createWrapper(swimlanes, true, false);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays collapsed swimlanes", () => {
        const swimlanes = [
            {
                card: {
                    id: 43,
                    has_children: false,
                    is_open: true,
                    is_collapsed: true,
                },
            } as Swimlane,
        ];
        const wrapper = createWrapper(swimlanes, true, false);
        expect(wrapper.findComponent(CollapsedSwimlane).exists()).toBe(true);
    });

    it(`displays swimlanes with invalid mapping`, () => {
        const swimlanes = [
            {
                card: {
                    id: 43,
                    has_children: false,
                    is_open: true,
                    is_collapsed: false,
                },
            } as Swimlane,
        ];
        jest.spyOn(mapper, "getColumnOfCard").mockReturnValue(undefined);
        const wrapper = createWrapper(swimlanes, true, false);
        expect(wrapper.findComponent(InvalidMappingSwimlane).exists()).toBe(true);
    });

    it("does not display swimlane that are closed if user wants to hide them", () => {
        const swimlanes = [
            {
                card: {
                    id: 43,
                    has_children: false,
                    is_open: false,
                    is_collapsed: true,
                },
            } as Swimlane,
        ];
        const wrapper = createWrapper(swimlanes, false, false);
        expect(wrapper.element.children).toHaveLength(0);
    });

    it("loads all swimlanes as soon as the component is created", () => {
        createWrapper([], false, true);
        expect(mock_load_swimlanes).toHaveBeenCalled();
    });

    it("displays skeletons when swimlanes are being loaded", () => {
        const wrapper = createWrapper([], false, true);
        expect(wrapper.findComponent(SwimlaneSkeleton).exists()).toBe(true);
    });
});
