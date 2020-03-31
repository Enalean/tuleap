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
import localVue from "../../support/local-vue.js";
import PostActionsSection from "./PostActionsSection.vue";
import { createList } from "../../support/factories.js";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import RunJobAction from "./PostAction/RunJobAction.vue";
import SetValueAction from "./PostAction/SetValueAction.vue";
import FrozenFieldsAction from "./PostAction/FrozenFieldsAction.vue";
import HiddenFieldsetsAction from "./PostAction/HiddenFieldsetsAction.vue";
import AddToBacklogPostAction from "./Externals/AddToBacklogPostAction.vue";

describe("PostActionsSection", () => {
    let store;
    let wrapper;

    beforeEach(() => {
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
            localVue,
        });
    });

    const skeleton_selector = '[data-test-type="skeleton"]';
    const empty_message_selector = '[data-test-type="empty-message"]';
    const post_action_selector = '[data-test-type="post-action"]';

    describe("when loading", () => {
        beforeEach(() => (store.state.transitionModal.is_loading_modal = true));

        it("shows only skeleton", () => {
            expect(wrapper.contains(skeleton_selector)).toBeTruthy();
            expect(wrapper.contains(post_action_selector)).toBeFalsy();
            expect(wrapper.contains(empty_message_selector)).toBeFalsy();
        });
    });

    describe("when loaded", () => {
        beforeEach(() => (store.state.transitionModal.is_loading_modal = false));

        describe("when no action", () => {
            beforeEach(() => (store.getters["transitionModal/post_actions"] = []));

            it("shows only empty message", () => {
                expect(wrapper.contains(skeleton_selector)).toBeFalsy();
                expect(wrapper.contains(post_action_selector)).toBeFalsy();
                expect(wrapper.contains(empty_message_selector)).toBeTruthy();
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
                expect(wrapper.contains(skeleton_selector)).toBeFalsy();
                expect(wrapper.contains(post_action_selector)).toBeTruthy();
                expect(wrapper.contains(empty_message_selector)).toBeFalsy();
            });
            it("shows as many post action as stored", () => {
                expect(wrapper.findAll(post_action_selector).length).toBe(2);
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
            expect(wrapper.contains(RunJobAction)).toBe(true);
            expect(wrapper.contains(SetValueAction)).toBe(true);
            expect(wrapper.contains(FrozenFieldsAction)).toBe(false);
            expect(wrapper.contains(HiddenFieldsetsAction)).toBe(true);
            expect(wrapper.contains(AddToBacklogPostAction)).toBe(false);
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
            ];
            await wrapper.vm.$nextTick();
            expect(wrapper.contains(RunJobAction)).toBe(true);
            expect(wrapper.contains(SetValueAction)).toBe(true);
            expect(wrapper.contains(FrozenFieldsAction)).toBe(true);
            expect(wrapper.contains(HiddenFieldsetsAction)).toBe(true);
            expect(wrapper.contains(AddToBacklogPostAction)).toBe(true);
        });

        it("displays nothing if there is no post action", async () => {
            store.getters["transitionModal/post_actions"] = [];
            await wrapper.vm.$nextTick();
            expect(wrapper.contains(RunJobAction)).toBe(false);
            expect(wrapper.contains(SetValueAction)).toBe(false);
            expect(wrapper.contains(FrozenFieldsAction)).toBe(false);
            expect(wrapper.contains(HiddenFieldsetsAction)).toBe(false);
            expect(wrapper.contains(AddToBacklogPostAction)).toBe(false);
        });
    });
});
