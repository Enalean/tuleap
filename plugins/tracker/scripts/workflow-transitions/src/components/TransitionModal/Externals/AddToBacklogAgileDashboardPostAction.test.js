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
import { createLocalVueForTests } from "../../../support/local-vue.js";
import AddToBacklogAgileDashboardPostAction from "./AddToBacklogAgileDashboardPostAction.vue";
import PostAction from "../PostAction/PostAction.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("AddToBacklogAgileDashboardPostAction", () => {
    it("spawns the component", async () => {
        const store_options = {
            state: {
                transitionModal: {
                    is_split_feature_flag_enabled: false,
                },
            },
        };
        const store = createStoreMock(store_options);

        const wrapper = shallowMount(AddToBacklogAgileDashboardPostAction, {
            mocks: { $store: store },
            propsData: { post_action: { type: "add_to_backlog" } },
            localVue: await createLocalVueForTests(),
        });
        expect(wrapper.findComponent(PostAction).exists()).toBe(true);
        expect(wrapper.find("[data-test=add-to-backlog-post-action-description]").exists()).toBe(
            true
        );
    });
});
