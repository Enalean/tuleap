/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
 *
 */

import { mount } from "@vue/test-utils";

import RunJobAction from "./RunJobAction.vue";
import { create } from "../../../support/factories";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests.js";

describe("RunJobAction", () => {
    const post_action = create("post_action", "presented", { job_url: "https://old.example.com" });
    let wrapper;
    let mockPostAction;

    beforeEach(() => {
        mockPostAction = jest.fn();
        wrapper = mount(RunJobAction, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_tracker: create("tracker", { project: { id: 1 } }),
                    },
                    getters: {
                        is_workflow_advanced: () => true,
                    },
                    modules: {
                        transitionModal: {
                            state: {
                                current_transition: create("transition"),
                                is_modal_save_running: false,
                            },
                            mutations: {
                                updateRunJobPostActionJobUrl: mockPostAction,
                            },
                            getters: {
                                set_value_action_fields: () => [],
                                is_agile_dashboard_used: () => false,
                                is_program_management_used: () => false,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
            propsData: { post_action },
        });
    });

    describe("when modifying job url", () => {
        it("updates store", async () => {
            await wrapper.find("[data-test=run-job-input]").setValue("https://example.com");
            expect(mockPostAction).toHaveBeenCalledWith(expect.anything(), {
                post_action: post_action,
                job_url: "https://example.com",
            });
        });
    });
});
