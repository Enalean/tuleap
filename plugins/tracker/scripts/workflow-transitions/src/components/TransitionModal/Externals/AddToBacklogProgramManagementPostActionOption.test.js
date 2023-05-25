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

import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { shallowMount } from "@vue/test-utils";
import AddToBacklogProgramManagementPostActionOption from "./AddToBacklogProgramManagementPostActionOption.vue";
import { createLocalVueForTests } from "../../../support/local-vue.js";
import { create } from "../../../support/factories.js";

describe("AddToBacklogProgramManagementPostActionOption", () => {
    let store, wrapper;
    beforeEach(async () => {
        const store_options = {
            state: {
                transitionModal: {
                    used_service_name: [],
                },
            },
            getters: {
                "transitionModal/post_actions": [],
                "transitionModal/is_program_management_used": false,
            },
        };
        store = createStoreMock(store_options);
        wrapper = shallowMount(AddToBacklogProgramManagementPostActionOption, {
            mocks: { $store: store },
            propsData: { post_action: create("post_action", "presented") },
            localVue: await createLocalVueForTests(),
        });
    });

    it("returns the option", () => {
        store.getters["transitionModal/post_actions"] = [];
        expect(wrapper.vm.add_to_backlog_information).toStrictEqual({
            option: "Add to the top backlog",
            title: "",
            valid: true,
        });
    });

    it('returns the "already present" option because the post action is already used', () => {
        store.getters["transitionModal/post_actions"] = [
            create("post_action", { type: "program_management_add_to_top_backlog" }),
        ];
        expect(wrapper.vm.add_to_backlog_information).toStrictEqual({
            option: "Add to the top backlog (already used)",
            title: "You can only have this post-action once.",
            valid: false,
        });
    });

    it("does not display the option when the Program Management service is not used", () => {
        expect(wrapper.find("[data-test=add-to-backlog]").exists()).toBe(false);
    });
});
