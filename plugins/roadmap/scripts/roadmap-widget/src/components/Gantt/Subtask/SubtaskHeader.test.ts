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

import { DateTime } from "luxon";
import { shallowMount } from "@vue/test-utils";
import type { Wrapper } from "@vue/test-utils";
import type { Project, SubtaskRow, Task } from "../../../type";
import HeaderInvalidIcon from "../Task/HeaderInvalidIcon.vue";
import HeaderLink from "../Task/HeaderLink.vue";
import SubtaskHeader from "./SubtaskHeader.vue";

describe("SubtaskHeader", () => {
    let subtask: Task;

    beforeEach(() => {
        subtask = { project: { id: 124 } as Project } as Task;
    });

    function getWrapper(): Wrapper<Vue> {
        return shallowMount(SubtaskHeader, {
            propsData: {
                row: {
                    parent: { project: { id: 123 } as Project } as Task,
                    subtask,
                } as SubtaskRow,
                popover_element_id: "id",
            },
        });
    }
    it("should ask to display the project if the task is not in the same project than its parent", () => {
        expect(getWrapper().findComponent(HeaderLink).props("should_display_project")).toBe(true);
    });

    it("should display a warning icon if task has end date < start date", () => {
        subtask = {
            project: { id: 124 } as Project,
            start: DateTime.fromISO("2020-04-14T22:00:00.000Z"),
            end: DateTime.fromISO("2020-04-10T22:00:00.000Z"),
        } as Task;

        expect(getWrapper().findComponent(HeaderInvalidIcon).exists()).toBe(true);
    });
});
