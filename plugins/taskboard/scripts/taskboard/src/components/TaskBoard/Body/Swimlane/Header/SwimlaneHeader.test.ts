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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SwimlaneHeader from "./SwimlaneHeader.vue";
import type { Swimlane } from "../../../../../type";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import type { RootState } from "../../../../../store/type";

const swimlane: Swimlane = {
    card: {
        color: "fiesta-red",
        label: "taskboard-swimlane",
    },
} as Swimlane;

describe("SwimlaneHeader", () => {
    const mock_collapse_swmilane = jest.fn();

    function createWrapper(
        is_fullscreen: boolean,
        backlog_items_have_children: boolean,
    ): VueWrapper<InstanceType<typeof SwimlaneHeader>> {
        return shallowMount(SwimlaneHeader, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        swimlane: {
                            getters: {
                                taskboard_cell_swimlane_header_classes: (): string[] =>
                                    is_fullscreen ? ["taskboard-fullscreen"] : [""],
                            },
                            actions: {
                                collapseSwimlane: mock_collapse_swmilane,
                            },
                            namespaced: true,
                        },
                    },
                    state: {
                        backlog_items_have_children: backlog_items_have_children,
                    } as RootState,
                }),
            },
            props: {
                swimlane,
            },
        });
    }

    it("displays a toggle icon", () => {
        const wrapper = createWrapper(false, true);

        expect(wrapper.find(".taskboard-swimlane-toggle").exists()).toBe(true);
        expect(wrapper.find(".taskboard-fullscreen").exists()).toBe(false);
    });

    it("adds fullscreen class when taskboard is in fullscreen mode", () => {
        const wrapper = createWrapper(true, true);
        expect(wrapper.find(".taskboard-fullscreen").exists()).toBe(true);
    });

    it("collapse the swimlane when user click on the toggle icon", () => {
        const wrapper = createWrapper(false, true);

        wrapper.get(".taskboard-swimlane-toggle").trigger("click");
        expect(mock_collapse_swmilane).toHaveBeenCalledWith(expect.anything(), swimlane);
    });

    it("does not display the swimline header is there is no tracker children", () => {
        const wrapper = createWrapper(false, false);
        expect(wrapper.find(".taskboard-cell-swimlane-header").exists()).toBe(false);
    });
});
