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

describe("TaskBoardHeader", () => {
    it("displays a header with many columns", () => {
        const wrapper = shallowMount(TaskBoardHeader, {
            propsData: { columns: [{ id: 2, label: "To do" }, { id: 3, label: "Done" }] }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});
