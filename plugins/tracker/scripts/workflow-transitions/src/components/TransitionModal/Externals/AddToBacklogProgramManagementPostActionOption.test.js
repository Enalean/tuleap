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
import AddToBacklogProgramManagementPostActionOption from "./AddToBacklogProgramManagementPostActionOption.vue";
import { create } from "../../../support/factories.js";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests.js";

describe("AddToBacklogProgramManagementPostActionOption", () => {
    let wrapper, post_actions_value;
    beforeEach(() => {
        post_actions_value = [];
        wrapper = shallowMount(AddToBacklogProgramManagementPostActionOption, {
            propsData: { post_action: create("post_action", "presented") },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        transitionModal: {
                            state: {
                                used_service_name: [],
                            },
                            getters: {
                                post_actions: () => post_actions_value,
                                is_program_management_used: () => false,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    });

    it("returns the option", () => {
        expect(wrapper.vm.add_to_backlog_information).toStrictEqual({
            option: "Add to the backlog",
            title: "",
            valid: true,
        });
    });

    it('returns the "already present" option because the post action is already used', () => {
        post_actions_value = [
            create("post_action", { type: "program_management_add_to_top_backlog" }),
        ];
        expect(wrapper.vm.add_to_backlog_information).toStrictEqual({
            option: "Add to the backlog (already used)",
            title: "You can only have this post-action once.",
            valid: false,
        });
    });

    it("does not display the option when the Program Management service is not used", () => {
        expect(wrapper.find("[data-test=add-to-backlog]").exists()).toBe(false);
    });
});
