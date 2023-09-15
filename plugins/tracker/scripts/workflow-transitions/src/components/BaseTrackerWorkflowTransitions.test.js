/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";

import BaseTrackerWorkflowTransitions from "./BaseTrackerWorkflowTransitions.vue";
import FirstConfigurationImpossibleWarning from "./FirstConfiguration/FirstConfigurationImpossibleWarning.vue";
import FirstConfigurationSections from "./FirstConfiguration/FirstConfigurationSections.vue";
import HeaderSection from "./Header/HeaderSection.vue";
import TransitionsMatrixSection from "./TransitionsMatrixSection.vue";
import TransitionRulesEnforcementWarning from "./TransitionRulesEnforcementWarning.vue";
import { create } from "../support/factories.js";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests.js";

describe("BaseTrackerWorkflowTransitions", () => {
    let is_operation_running,
        is_current_tracker_load_failed,
        is_current_tracker_loading,
        has_selectbox_fields_getter,
        current_tracker;
    beforeEach(() => {
        is_operation_running = false;
        is_current_tracker_load_failed = false;
        is_current_tracker_loading = false;
        has_selectbox_fields_getter = false;
        current_tracker = create("workflow", "field_not_defined");
    });
    const getWrapper = () => {
        return shallowMount(BaseTrackerWorkflowTransitions, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        is_operation_running,
                        is_current_tracker_load_failed,
                        is_current_tracker_loading,
                        current_tracker,
                    },
                    getters: {
                        has_selectbox_fields: () => has_selectbox_fields_getter,
                    },
                    actions: {
                        loadTracker: jest.fn(),
                    },
                    modules: {
                        transitionModal: {
                            actions: {
                                setUsedServiceName: jest.fn(),
                                setIsSplitFeatureFlagEnabled: jest.fn(),
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
            propsData: { trackerId: 1 },
        });
    };

    describe("when tracker load failed", () => {
        it("shows tracker load error message", () => {
            is_current_tracker_load_failed = true;
            const wrapper = getWrapper();
            expect(
                wrapper.find('[data-test-type="tracker-load-error-message"]').exists(),
            ).toBeTruthy();
        });
        it("does not show anything else", () => {
            is_current_tracker_load_failed = true;
            const wrapper = getWrapper();
            expect(wrapper.findComponent(FirstConfigurationSections).exists()).toBeFalsy();
            expect(wrapper.findComponent(HeaderSection).exists()).toBeFalsy();
            expect(wrapper.findComponent(TransitionsMatrixSection).exists()).toBeFalsy();
        });
    });

    describe("when tracker loading", () => {
        it("shows tracker load spinner", () => {
            is_current_tracker_loading = true;
            const wrapper = getWrapper();
            expect(wrapper.find('[data-test-type="tracker-load-spinner"]').exists()).toBeTruthy();
        });
        it("does not show anything else", () => {
            is_current_tracker_loading = true;
            const wrapper = getWrapper();
            expect(wrapper.findComponent(FirstConfigurationSections).exists()).toBeFalsy();
            expect(wrapper.findComponent(HeaderSection).exists()).toBeFalsy();
            expect(wrapper.findComponent(TransitionsMatrixSection).exists()).toBeFalsy();
        });
    });

    describe("when tracker loaded", () => {
        describe("when base field is not configured", () => {
            it("and there is a selectbox field, then it shows first configuration", () => {
                has_selectbox_fields_getter = true;

                const wrapper = getWrapper();
                expect(wrapper.findComponent(FirstConfigurationSections).exists()).toBeTruthy();
            });

            it("does not show rules enforcement warning", () => {
                const wrapper = getWrapper();
                expect(
                    wrapper.findComponent(TransitionRulesEnforcementWarning).exists(),
                ).toBeFalsy();
            });

            it("when base field is not configured and there is no selectbox field, then it shows that first configuration is impossible", () => {
                has_selectbox_fields_getter = false;
                const wrapper = getWrapper();

                expect(
                    wrapper.findComponent(FirstConfigurationImpossibleWarning).exists(),
                ).toBeTruthy();
            });
        });

        describe("when base field is configured", () => {
            it("shows configuration header and matrix", () => {
                current_tracker = create("workflow", "field_defined");
                current_tracker.workflow = { field_id: 1 };
                const wrapper = getWrapper();
                expect(wrapper.find("[data-test=header-section]").exists()).toBe(true);
                expect(wrapper.find("[data-test=transition-matrix-section]").exists()).toBe(true);
            });
            it("shows rules enforcement warning", () => {
                current_tracker = create("workflow", "field_defined");
                const wrapper = getWrapper();
                expect(
                    wrapper.find("[data-test=configuration-impossible-warning]").exists(),
                ).toBeTruthy();
            });
        });
    });
});
