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
import TaskBoardHeader from "./TaskBoardHeader.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ColumnDefinition } from "../../../type";
import ExpandedHeaderCell from "./Expanded/ExpandedHeaderCell.vue";
import CollapsedHeaderCell from "./Collapsed/CollapsedHeaderCell.vue";
import type Vue from "vue";

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

function createWrapper(backlog_items_have_children: boolean): Wrapper<Vue> {
    return shallowMount(TaskBoardHeader, {
        mocks: {
            $store: createStoreMock({
                state: {
                    column: {
                        columns: [todo, ongoing, done],
                    },
                    swimlane: {},
                    backlog_items_have_children: backlog_items_have_children,
                },
                getters: {
                    "swimlane/taskboard_cell_swimlane_header_classes": [],
                },
            }),
        },
    });
}

describe("TaskBoardHeader", () => {
    it("displays a header with many columns", () => {
        const wrapper = createWrapper(true);

        const children = wrapper.findAll("*");
        expect(children.at(1).classes("taskboard-cell-swimlane-header")).toBe(true);

        expect(children.at(2).findComponent(ExpandedHeaderCell).exists()).toBe(true);
        expect(children.at(2).props("column")).toStrictEqual(todo);

        expect(children.at(3).findComponent(ExpandedHeaderCell).exists()).toBe(true);
        expect(children.at(3).props("column")).toStrictEqual(ongoing);

        expect(children.at(4).findComponent(CollapsedHeaderCell).exists()).toBe(true);
        expect(children.at(4).props("column")).toStrictEqual(done);
    });

    it("does not display swimlane header when no parent in hierarchy", () => {
        const wrapper = createWrapper(false);

        const children = wrapper.findAll("*");
        expect(wrapper.find(".taskboard-cell-swimlane-header").exists()).toBe(false);

        expect(children.at(1).findComponent(ExpandedHeaderCell).exists()).toBe(true);
        expect(children.at(1).props("column")).toStrictEqual(todo);

        expect(children.at(2).findComponent(ExpandedHeaderCell).exists()).toBe(true);
        expect(children.at(2).props("column")).toStrictEqual(ongoing);

        expect(children.at(3).findComponent(CollapsedHeaderCell).exists()).toBe(true);
        expect(children.at(3).props("column")).toStrictEqual(done);
    });
});
