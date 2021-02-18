/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../support/local-vue.js";
import AddToBacklogProgramManagementPostAction from "./AddToBacklogProgramManagementPostAction.vue";
import PostAction from "../PostAction/PostAction.vue";

describe("AddToBacklogProgramManagementPostAction", () => {
    it("spawns the component", () => {
        const wrapper = shallowMount(AddToBacklogProgramManagementPostAction, {
            propsData: { post_action: { type: "add_to_top_backlog_program_management" } },
            localVue,
        });
        expect(wrapper.findComponent(PostAction).exists()).toBe(true);
        expect(
            wrapper
                .find("[data-test=add-to-backlog-program-management-post-action-description]")
                .exists()
        ).toBe(true);
    });
});
