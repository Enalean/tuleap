/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { shallowMount } from "@vue/test-utils";
import { create } from "../../../support/factories.js";
import PostAction from "./PostAction.vue";
import AddToBacklogAgileDashboardPostActionOption from "../Externals/AddToBacklogAgileDashboardPostActionOption.vue";
import AddToBacklogProgramManagementPostActionOption from "../Externals/AddToBacklogProgramManagementPostActionOption.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests.js";

describe("PostAction", () => {
    const date_field_id = 43;
    const date_field = create("field", { field_id: date_field_id, type: "date" });
    const int_field_id = 44;
    const int_field = create("field", { field_id: int_field_id, type: "int" });
    const float_field_id = 45;
    const float_field = create("field", { field_id: float_field_id, type: "float" });
    const status_field_id = 46;
    const status_field = create("field", { field_id: status_field_id, type: "sb" });
    const fieldset_id = 47;
    const fieldset = create("field", { field_id: fieldset_id, type: "fieldset" });

    let current_tracker = {
        fields: [date_field, int_field, float_field, status_field, fieldset],
    };
    let is_workflow_advanced_value;
    let post_actions_value;
    let set_value_action_fields_value;
    let is_modal_save_running;

    beforeEach(() => {
        is_workflow_advanced_value = false;
        post_actions_value = [];
        set_value_action_fields_value = [date_field, int_field, float_field];
        is_modal_save_running = false;
        current_tracker = {
            fields: [date_field, int_field, float_field, status_field, fieldset],
        };
    });

    function instantiateComponent() {
        return shallowMount(PostAction, {
            propsData: { post_action: create("post_action", "presented") },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_tracker,
                    },
                    getters: {
                        current_workflow_field: () => status_field,
                        is_workflow_advanced: () => is_workflow_advanced_value,
                    },
                    modules: {
                        transitionModal: {
                            state: {
                                current_transition: create("transition"),
                                is_modal_save_running,
                            },
                            getters: {
                                set_value_action_fields: () => set_value_action_fields_value,
                                post_actions: () => post_actions_value,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    describe("Frozen field is valid", () => {
        it("should be false if workflow is advanced", () => {
            is_workflow_advanced_value = true;
            const wrapper = instantiateComponent();
            expect(wrapper.vm.frozen_fields_is_valid).toBe(false);
        });

        it("should be false if there are no writable fields available", () => {
            current_tracker.fields = [];
            const wrapper = instantiateComponent();
            expect(wrapper.vm.frozen_fields_is_valid).toBe(false);
        });

        it("should be false if the post action is already present once", () => {
            post_actions_value = [create("post_action", { type: "frozen_fields" })];
            const wrapper = instantiateComponent();
            expect(wrapper.vm.frozen_fields_is_valid).toBe(false);
        });

        it("should be true otherwise", () => {
            is_workflow_advanced_value = false;
            post_actions_value = [];
            const wrapper = instantiateComponent();
            expect(wrapper.vm.frozen_fields_is_valid).toBe(true);
        });
    });

    describe("Hidden fieldsets is valid", () => {
        it("should be false if workflow is advanced", () => {
            is_workflow_advanced_value = true;
            const wrapper = instantiateComponent();
            expect(wrapper.vm.hidden_fieldsets_is_valid).toBe(false);
        });

        it("should be false if there are no fieldsets available", () => {
            current_tracker.fields = [];
            const wrapper = instantiateComponent();
            expect(wrapper.vm.hidden_fieldsets_is_valid).toBe(false);
        });

        it("should be false if the post action is already present once", () => {
            post_actions_value = [create("post_action", { type: "hidden_fieldsets" })];
            const wrapper = instantiateComponent();
            expect(wrapper.vm.hidden_fieldsets_is_valid).toBe(false);
        });

        it("should be true otherwise", () => {
            const wrapper = instantiateComponent();
            expect(wrapper.vm.hidden_fieldsets_is_valid).toBe(true);
        });
    });

    describe("set field value is valid", () => {
        it("should be false if there are no fields available", () => {
            set_value_action_fields_value = [];
            const wrapper = instantiateComponent();
            expect(wrapper.vm.set_field_value_is_valid).toBe(false);
        });

        it("should be true otherwise", () => {
            const wrapper = instantiateComponent();
            expect(wrapper.vm.set_field_value_is_valid).toBe(true);
        });
    });

    it(`when the modal is saving, it will disable the post-action type selectbox`, () => {
        is_modal_save_running = true;
        const wrapper = instantiateComponent();
        const post_action_type_select = wrapper.get("[data-test=post-action-type-select]");
        expect(post_action_type_select.attributes("disabled")).toBe("");
    });

    describe("Spawning of the component", () => {
        it("displays the content of the PostAction component", () => {
            const wrapper = instantiateComponent();
            expect(wrapper.find("[data-test=post-action-action-card]").exists()).toBe(true);
            expect(wrapper.findComponent(AddToBacklogAgileDashboardPostActionOption).exists()).toBe(
                true,
            );
            expect(
                wrapper.findComponent(AddToBacklogProgramManagementPostActionOption).exists(),
            ).toBe(true);
        });
    });
});
