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

import Vue from "vue";
import { shallowMount } from "@vue/test-utils";

import BaseTrackerWorkflowTransitions from "./BaseTrackerWorkflowTransitions.vue";
import FirstConfigurationImpossibleWarning from "./FirstConfiguration/FirstConfigurationImpossibleWarning.vue";
import FirstConfigurationSections from "./FirstConfiguration/FirstConfigurationSections.vue";
import HeaderSection from "./Header/HeaderSection.vue";
import TransitionsMatrixSection from "./TransitionsMatrixSection.vue";
import TransitionRulesEnforcementWarning from "./TransitionRulesEnforcementWarning.vue";
import store_options from "../store/index.js";
import { create } from "../support/factories.js";
import localVue from "../support/local-vue.js";
import { createStoreMock } from "../../../../../../src/www/scripts/vue-components/store-wrapper-jest";

describe("BaseTrackerWorkflowTransitions", () => {
    let store;
    let wrapper;

    beforeEach(() => {
        store = createStoreMock(store_options, { is_operation_running: false });
        wrapper = shallowMount(BaseTrackerWorkflowTransitions, {
            mocks: {
                $store: store,
            },
            localVue,
            propsData: { trackerId: 1 },
        });
    });

    afterEach(() => store.reset());

    const tracker_load_error_message_selector = '[data-test-type="tracker-load-error-message"]';
    const tracker_load_spinner_selector = '[data-test-type="tracker-load-spinner"]';

    describe("when tracker load failed", () => {
        beforeEach(() => {
            store.state.is_current_tracker_load_failed = true;
        });

        it("shows tracker load error message", () => {
            expect(wrapper.contains(tracker_load_error_message_selector)).toBeTruthy();
        });
        it("does not show anything else", () => {
            expect(wrapper.contains(FirstConfigurationSections)).toBeFalsy();
            expect(wrapper.contains(HeaderSection)).toBeFalsy();
            expect(wrapper.contains(TransitionsMatrixSection)).toBeFalsy();
        });
    });

    describe("when tracker loading", () => {
        beforeEach(() => {
            store.state.is_current_tracker_load_failed = false;
            store.state.is_current_tracker_loading = true;
        });

        it("shows tracker load spinner", () => {
            expect(wrapper.contains(tracker_load_spinner_selector)).toBeTruthy();
        });
        it("does not show anything else", () => {
            expect(wrapper.contains(FirstConfigurationSections)).toBeFalsy();
            expect(wrapper.contains(HeaderSection)).toBeFalsy();
            expect(wrapper.contains(TransitionsMatrixSection)).toBeFalsy();
        });
    });

    describe("when tracker loaded", () => {
        beforeEach(() => {
            store.state.is_current_tracker_load_failed = false;
            store.state.is_current_tracker_loading = false;
            store.state.current_tracker = {};
            Vue.set(store.state, "current_tracker", {});
        });

        describe("when base field is not configured", () => {
            beforeEach(() => {
                Vue.set(
                    store.state.current_tracker,
                    "workflow",
                    create("workflow", "field_not_defined")
                );
            });

            it("and there is a selectbox field, then it shows first configuration", () => {
                store.getters.has_selectbox_fields = true;

                expect(wrapper.contains(FirstConfigurationSections)).toBeTruthy();
            });

            it("does not show rules enforcement warning", () => {
                expect(wrapper.contains(TransitionRulesEnforcementWarning)).toBeFalsy();
            });

            it("when base field is not configured and there is no selectbox field, then it shows that first configuration is impossible", async () => {
                store.getters.has_selectbox_fields = false;
                await wrapper.vm.$nextTick();

                expect(wrapper.contains(FirstConfigurationImpossibleWarning)).toBeTruthy();
            });
        });

        describe("when base field is configured", () => {
            beforeEach(() => {
                Vue.set(
                    store.state.current_tracker,
                    "workflow",
                    create("workflow", "field_defined")
                );
            });
            it("shows configuration header and matrix", () => {
                expect(wrapper.contains(HeaderSection)).toBeTruthy();
                expect(wrapper.contains(TransitionsMatrixSection)).toBeTruthy();
            });
            it("shows rules enforcement warning", () => {
                expect(wrapper.contains(TransitionRulesEnforcementWarning)).toBeTruthy();
            });
        });
    });
});
