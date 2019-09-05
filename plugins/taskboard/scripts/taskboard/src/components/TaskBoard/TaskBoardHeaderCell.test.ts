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
import TaskBoardHeaderCell from "./TaskBoardHeaderCell.vue";

describe("TaskBoardHeaderCell", () => {
    it("displays a cell without color", () => {
        const wrapper = shallowMount(TaskBoardHeaderCell, {
            propsData: { column: { id: 2, label: "To do", color: "" } }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
    it("displays a cell with color", () => {
        const wrapper = shallowMount(TaskBoardHeaderCell, {
            propsData: { column: { id: 2, label: "To do", color: "fiesta-red" } }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});
