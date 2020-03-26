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
import TaskBoardHeader from "./TaskBoardHeader.vue";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { ColumnDefinition } from "../../../type";
import ExpandedHeaderCell from "./Expanded/ExpandedHeaderCell.vue";
import CollapsedHeaderCell from "./Collapsed/CollapsedHeaderCell.vue";

describe("TaskBoardHeader", () => {
    it("displays a header with many columns", () => {
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

        const wrapper = shallowMount(TaskBoardHeader, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        column: {
                            columns: [todo, ongoing, done],
                        },
                        swimlane: {},
                    },
                    getters: {
                        "swimlane/taskboard_cell_swimlane_header_classes": [],
                    },
                }),
            },
        });

        const children = wrapper.findAll("*");
        expect(children.at(1).is(".taskboard-cell-swimlane-header")).toBe(true);

        expect(children.at(2).is(ExpandedHeaderCell)).toBe(true);
        expect(children.at(2).props("column")).toStrictEqual(todo);

        expect(children.at(3).is(ExpandedHeaderCell)).toBe(true);
        expect(children.at(3).props("column")).toStrictEqual(ongoing);

        expect(children.at(4).is(CollapsedHeaderCell)).toBe(true);
        expect(children.at(4).props("column")).toStrictEqual(done);
    });
});
