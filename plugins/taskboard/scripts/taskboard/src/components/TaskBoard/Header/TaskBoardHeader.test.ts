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
import TaskBoardHeader from "./TaskBoardHeader.vue";
import type { ColumnDefinition } from "../../../type";
import ExpandedHeaderCell from "./Expanded/ExpandedHeaderCell.vue";
import CollapsedHeaderCell from "./Collapsed/CollapsedHeaderCell.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import type { RootState } from "../../../store/type";

const todo: ColumnDefinition = {
    id: 2,
    label: "To do",
    is_collapsed: false,
} as ColumnDefinition;
const ongoing: ColumnDefinition = {
    id: 3,
    label: "Ongoing",
    is_collapsed: false,
} as ColumnDefinition;
const done: ColumnDefinition = {
    id: 4,
    label: "Done",
    is_collapsed: true,
} as ColumnDefinition;

function createWrapper(
    backlog_items_have_children: boolean,
): VueWrapper<InstanceType<typeof TaskBoardHeader>> {
    return shallowMount(TaskBoardHeader, {
        global: {
            ...getGlobalTestOptions({
                state: {
                    backlog_items_have_children: backlog_items_have_children,
                } as RootState,
                modules: {
                    column: {
                        state: {
                            columns: [todo, ongoing, done],
                        },
                        namespaced: true,
                    },
                    swimlane: {
                        getters: {
                            taskboard_cell_swimlane_header_classes: (): string[] => [],
                        },
                        namespaced: true,
                    },
                },
            }),
        },
    });
}

describe("TaskBoardHeader", () => {
    it("displays a header with many columns", () => {
        const wrapper = createWrapper(true);

        const children = wrapper.findAll("*");
        expect(children.at(1)?.classes("taskboard-cell-swimlane-header")).toBe(true);
        expect(children.at(2)?.findComponent(ExpandedHeaderCell).exists()).toBe(true);
        expect(children.at(3)?.findComponent(ExpandedHeaderCell).exists()).toBe(true);
        expect(children.at(4)?.findComponent(CollapsedHeaderCell).exists()).toBe(true);
    });

    it("does not display swimlane header when no parent in hierarchy", () => {
        const wrapper = createWrapper(false);

        const children = wrapper.findAll("*");
        expect(wrapper.find(".taskboard-cell-swimlane-header").exists()).toBe(false);
        expect(children.at(1)?.findComponent(ExpandedHeaderCell).exists()).toBe(true);
        expect(children.at(2)?.findComponent(ExpandedHeaderCell).exists()).toBe(true);
        expect(children.at(3)?.findComponent(CollapsedHeaderCell).exists()).toBe(true);
    });
});
