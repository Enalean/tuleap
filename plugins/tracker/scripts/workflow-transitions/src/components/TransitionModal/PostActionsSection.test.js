/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import PostActionsSection from "./PostActionsSection.vue";
import { createList } from "../../support/factories.js";
import RunJobAction from "./PostAction/RunJobAction.vue";
import SetValueAction from "./PostAction/SetValueAction.vue";
import FrozenFieldsAction from "./PostAction/FrozenFieldsAction.vue";
import HiddenFieldsetsAction from "./PostAction/HiddenFieldsetsAction.vue";
import AddToBacklogAgileDashboardPostAction from "./Externals/AddToBacklogAgileDashboardPostAction.vue";
import AddToBacklogProgramManagementPostAction from "./Externals/AddToBacklogProgramManagementPostAction.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests.js";

describe("PostActionsSection", () => {
    let is_loading_modal, is_modal_save_running, post_actions_value;
    let addPostActionMock = jest.fn();

    beforeEach(() => {
        is_loading_modal = false;
        is_modal_save_running = false;
        post_actions_value = createList("post_action", 2, "presented");
    });

    function instantiateComponent() {
        return shallowMount(PostActionsSection, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        transitionModal: {
                            state: {
                                is_loading_modal,
                                is_modal_save_running,
                            },
                            getters: {
                                post_actions: () => post_actions_value,
                            },
                            mutations: {
                                addPostAction: addPostActionMock,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    const skeleton_selector = '[data-test-type="skeleton"]';
    const empty_message_selector = '[data-test-type="empty-message"]';
    const post_action_selector = '[data-test-type="post-action"]';

    describe("when loading", () => {
        beforeEach(() => (is_loading_modal = true));

        it("shows only skeleton", () => {
            const wrapper = instantiateComponent();
            expect(wrapper.find(skeleton_selector).exists()).toBeTruthy();
            expect(wrapper.find(post_action_selector).exists()).toBeFalsy();
            expect(wrapper.find(empty_message_selector).exists()).toBeFalsy();
        });
    });

    describe("when loaded", () => {
        describe("when no action", () => {
            beforeEach(() => (post_actions_value = []));

            it("shows only empty message", () => {
                const wrapper = instantiateComponent();
                expect(wrapper.find(skeleton_selector).exists()).toBeFalsy();
                expect(wrapper.find(post_action_selector).exists()).toBeFalsy();
                expect(wrapper.find(empty_message_selector).exists()).toBeTruthy();
            });
        });
        describe("when some post actions", () => {
            beforeEach(() => (post_actions_value = createList("post_action", 2, "presented")));

            it("shows only post actions", () => {
                const wrapper = instantiateComponent();
                expect(wrapper.find(skeleton_selector).exists()).toBeFalsy();
                expect(wrapper.find(post_action_selector).exists()).toBeTruthy();
                expect(wrapper.find(empty_message_selector).exists()).toBeFalsy();
            });
            it("shows as many post action as stored", () => {
                const wrapper = instantiateComponent();
                expect(wrapper.findAll(post_action_selector)).toHaveLength(2);
            });
        });
    });

    it(`when the modal is saving, it will disable the "Add another action" button`, () => {
        is_modal_save_running = true;
        const wrapper = instantiateComponent();
        const add_action_button = wrapper.get("[data-test=add-post-action]");
        expect(add_action_button.attributes("disabled")).toBe("");
    });

    it(`when I click on the "Add another action" button, it will commit a mutation to create a new post action`, () => {
        const wrapper = instantiateComponent();
        const add_action_button = wrapper.get("[data-test=add-post-action]");
        add_action_button.trigger("click");
        expect(addPostActionMock).toHaveBeenCalled();
    });

    describe("getComponent", () => {
        it("displays the components which are alreay set", () => {
            post_actions_value = [
                {
                    type: "run_job",
                    unique_id: "new_1",
                },
                {
                    type: "set_field_value",
                    unique_id: "new_2",
                },
                {
                    type: "hidden_fieldsets",
                    unique_id: "new_4",
                },
            ];
            const wrapper = instantiateComponent();
            expect(wrapper.findComponent(RunJobAction).exists()).toBe(true);
            expect(wrapper.findComponent(SetValueAction).exists()).toBe(true);
            expect(wrapper.findComponent(FrozenFieldsAction).exists()).toBe(false);
            expect(wrapper.findComponent(HiddenFieldsetsAction).exists()).toBe(true);
            expect(wrapper.findComponent(AddToBacklogAgileDashboardPostAction).exists()).toBe(
                false,
            );
            expect(wrapper.findComponent(AddToBacklogProgramManagementPostAction).exists()).toBe(
                false,
            );
        });
        it("displays all the component which are in the post_actions", () => {
            post_actions_value = [
                {
                    type: "run_job",
                    unique_id: "new_1",
                },
                {
                    type: "set_field_value",
                    unique_id: "new_2",
                },
                {
                    type: "hidden_fieldsets",
                    unique_id: "new_4",
                },
                {
                    type: "frozen_fields",
                    unique_id: "new_6",
                },
                {
                    type: "add_to_top_backlog",
                    unique_id: "new_10",
                },
                {
                    type: "program_management_add_to_top_backlog",
                    unique_id: "new_11",
                },
            ];
            const wrapper = instantiateComponent();
            expect(wrapper.findComponent(RunJobAction).exists()).toBe(true);
            expect(wrapper.findComponent(SetValueAction).exists()).toBe(true);
            expect(wrapper.findComponent(FrozenFieldsAction).exists()).toBe(true);
            expect(wrapper.findComponent(HiddenFieldsetsAction).exists()).toBe(true);
            expect(wrapper.findComponent(AddToBacklogAgileDashboardPostAction).exists()).toBe(true);
            expect(wrapper.findComponent(AddToBacklogProgramManagementPostAction).exists()).toBe(
                true,
            );
        });

        it("displays nothing if there is no post action", () => {
            post_actions_value = [];
            const wrapper = instantiateComponent();
            expect(wrapper.findComponent(RunJobAction).exists()).toBe(false);
            expect(wrapper.findComponent(SetValueAction).exists()).toBe(false);
            expect(wrapper.findComponent(FrozenFieldsAction).exists()).toBe(false);
            expect(wrapper.findComponent(HiddenFieldsetsAction).exists()).toBe(false);
            expect(wrapper.findComponent(AddToBacklogAgileDashboardPostAction).exists()).toBe(
                false,
            );
            expect(wrapper.findComponent(AddToBacklogProgramManagementPostAction).exists()).toBe(
                false,
            );
        });
    });
});
