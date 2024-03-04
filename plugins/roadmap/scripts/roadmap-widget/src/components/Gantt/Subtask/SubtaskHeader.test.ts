/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import SubtaskHeader from "./SubtaskHeader.vue";
import type { Project, SubtaskRow, Task } from "../../../type";
import HeaderLink from "../Task/HeaderLink.vue";
import HeaderInvalidIcon from "../Task/HeaderInvalidIcon.vue";
import { DateTime } from "luxon";

describe("SubtaskHeader", () => {
    it("should ask to display the project if the task is not in the same project than its parent", () => {
        const wrapper = shallowMount(SubtaskHeader, {
            propsData: {
                row: {
                    parent: { project: { id: 123 } as Project } as Task,
                    subtask: { project: { id: 124 } as Project } as Task,
                } as SubtaskRow,
                popover_element_id: "id",
            },
        });

        expect(wrapper.findComponent(HeaderLink).props("should_display_project")).toBe(true);
    });

    it("should display a warning icon if task has end date < start date", () => {
        const wrapper = shallowMount(SubtaskHeader, {
            propsData: {
                row: {
                    parent: { project: { id: 123 } as Project } as Task,
                    subtask: {
                        project: { id: 124 } as Project,
                        start: DateTime.fromISO("2020-04-14T22:00:00.000Z"),
                        end: DateTime.fromISO("2020-04-10T22:00:00.000Z"),
                    } as Task,
                } as SubtaskRow,
                popover_element_id: "id",
            },
        });

        expect(wrapper.findComponent(HeaderInvalidIcon).exists()).toBe(true);
    });
});
