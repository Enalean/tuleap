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
import { createLocalVueForTests } from "../../support/local-vue.js";
import PostActionsSection from "./PostActionsSection.vue";
import { createList } from "../../support/factories.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import RunJobAction from "./PostAction/RunJobAction.vue";
import SetValueAction from "./PostAction/SetValueAction.vue";
import FrozenFieldsAction from "./PostAction/FrozenFieldsAction.vue";
import HiddenFieldsetsAction from "./PostAction/HiddenFieldsetsAction.vue";
import AddToBacklogAgileDashboardPostAction from "./Externals/AddToBacklogAgileDashboardPostAction.vue";
import AddToBacklogProgramManagementPostAction from "./Externals/AddToBacklogAgileDashboardPostAction.vue";

describe("PostActionsSection", () => {
    let store;
    let wrapper;

    beforeEach(async () => {
        const store_options = {
            state: {
                transitionModal: {
                    is_loading_modal: false,
                    is_modal_save_running: false,
                },
            },
            getters: {
                "transitionModal/post_actions": createList("post_action", 2, "presented"),
            },
        };
        store = createStoreMock(store_options);
        wrapper = shallowMount(PostActionsSection, {
            mocks: {
                $store: store,
            },
            localVue: await createLocalVueForTests(),
        });
    });

    const skeleton_selector = '[data-test-type="skeleton"]';
    const empty_message_selector = '[data-test-type="empty-message"]';
    const post_action_selector = '[data-test-type="post-action"]';

    describe("when loading", () => {
        beforeEach(() => (store.state.transitionModal.is_loading_modal = true));

        it("shows only skeleton", () => {
            expect(wrapper.find(skeleton_selector).exists()).toBeTruthy();
            expect(wrapper.find(post_action_selector).exists()).toBeFalsy();
            expect(wrapper.find(empty_message_selector).exists()).toBeFalsy();
        });
    });

    describe("when loaded", () => {
        beforeEach(() => (store.state.transitionModal.is_loading_modal = false));

        describe("when no action", () => {
            beforeEach(() => (store.getters["transitionModal/post_actions"] = []));

            it("shows only empty message", () => {
                expect(wrapper.find(skeleton_selector).exists()).toBeFalsy();
                expect(wrapper.find(post_action_selector).exists()).toBeFalsy();
                expect(wrapper.find(empty_message_selector).exists()).toBeTruthy();
            });
        });
        describe("when some post actions", () => {
            beforeEach(
                () =>
                    (store.getters["transitionModal/post_actions"] = createList(
                        "post_action",
                        2,
                        "presented"
                    ))
            );

            it("shows only post actions", () => {
                expect(wrapper.find(skeleton_selector).exists()).toBeFalsy();
                expect(wrapper.find(post_action_selector).exists()).toBeTruthy();
                expect(wrapper.find(empty_message_selector).exists()).toBeFalsy();
            });
            it("shows as many post action as stored", () => {
                expect(wrapper.findAll(post_action_selector)).toHaveLength(2);
            });
        });
    });

    it(`when the modal is saving, it will disable the "Add another action" button`, async () => {
        store.state.transitionModal.is_modal_save_running = true;
        await wrapper.vm.$nextTick();
        const add_action_button = wrapper.get("[data-test=add-post-action]");
        expect(add_action_button.attributes("disabled")).toBeTruthy();
    });

    it(`when I click on the "Add another action" button, it will commit a mutation to create a new post action`, () => {
        const add_action_button = wrapper.get("[data-test=add-post-action]");
        add_action_button.trigger("click");
        expect(store.commit).toHaveBeenCalledWith("transitionModal/addPostAction");
    });

    describe("getComponent", () => {
        it("displays the components which are alreay set", async () => {
            store.getters["transitionModal/post_actions"] = [
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
            await wrapper.vm.$nextTick();
            expect(wrapper.findComponent(RunJobAction).exists()).toBe(true);
            expect(wrapper.findComponent(SetValueAction).exists()).toBe(true);
            expect(wrapper.findComponent(FrozenFieldsAction).exists()).toBe(false);
            expect(wrapper.findComponent(HiddenFieldsetsAction).exists()).toBe(true);
            expect(wrapper.findComponent(AddToBacklogAgileDashboardPostAction).exists()).toBe(
                false
            );
            expect(wrapper.findComponent(AddToBacklogProgramManagementPostAction).exists()).toBe(
                false
            );
        });
        it("displays all the component which are in the post_actions", async () => {
            store.getters["transitionModal/post_actions"] = [
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
            await wrapper.vm.$nextTick();
            expect(wrapper.findComponent(RunJobAction).exists()).toBe(true);
            expect(wrapper.findComponent(SetValueAction).exists()).toBe(true);
            expect(wrapper.findComponent(FrozenFieldsAction).exists()).toBe(true);
            expect(wrapper.findComponent(HiddenFieldsetsAction).exists()).toBe(true);
            expect(wrapper.findComponent(AddToBacklogAgileDashboardPostAction).exists()).toBe(true);
            expect(wrapper.findComponent(AddToBacklogProgramManagementPostAction).exists()).toBe(
                true
            );
        });

        it("displays nothing if there is no post action", async () => {
            store.getters["transitionModal/post_actions"] = [];
            await wrapper.vm.$nextTick();
            expect(wrapper.findComponent(RunJobAction).exists()).toBe(false);
            expect(wrapper.findComponent(SetValueAction).exists()).toBe(false);
            expect(wrapper.findComponent(FrozenFieldsAction).exists()).toBe(false);
            expect(wrapper.findComponent(HiddenFieldsetsAction).exists()).toBe(false);
            expect(wrapper.findComponent(AddToBacklogAgileDashboardPostAction).exists()).toBe(
                false
            );
            expect(wrapper.findComponent(AddToBacklogProgramManagementPostAction).exists()).toBe(
                false
            );
        });
    });
});
