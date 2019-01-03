/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
import Vuex from "vuex";
import { shallowMount } from "@vue/test-utils";
import BaseTrackerWorkflowTransitions from "./BaseTrackerWorkflowTransitions.vue";
import FirstConfigurationSections from "./FirstConfigurationSections.vue";
import TransitionsConfigurationHeaderSection from "./TransitionsConfigurationHeaderSection.vue";
import TransitionsMatrixSection from "./TransitionsMatrixSection.vue";
import store_options from "../store/index.js";
import { createStoreWrapper } from "../support/store-wrapper.spec-helper.js";
import { create } from "../support/factories.js";
import localVue from "../support/local-vue.js";

describe("BaseTrackerWorkflowTransitions", () => {
    let store_wrapper;
    let wrapper;

    beforeEach(() => {
        store_wrapper = createStoreWrapper(store_options, { is_operation_running: false });
        wrapper = shallowMount(BaseTrackerWorkflowTransitions, {
            store: store_wrapper.store,
            localVue,
            propsData: { trackerId: 1 }
        });
    });

    const tracker_load_error_message_selector = '[data-test-type="tracker-load-error-message"]';
    const tracker_load_spinner_selector = '[data-test-type="tracker-load-spinner"]';

    describe("when tracker load failed", () => {
        beforeEach(() => {
            store_wrapper.state.is_current_tracker_load_failed = true;
        });

        it("shows tracker load error message", () => {
            expect(wrapper.contains(tracker_load_error_message_selector)).toBeTruthy();
        });
        it("does not show anything else", () => {
            expect(wrapper.contains(FirstConfigurationSections)).toBeFalsy();
            expect(wrapper.contains(TransitionsConfigurationHeaderSection)).toBeFalsy();
            expect(wrapper.contains(TransitionsMatrixSection)).toBeFalsy();
        });
    });

    describe("when tracker loading", () => {
        beforeEach(() => {
            store_wrapper.state.is_current_tracker_load_failed = false;
            store_wrapper.state.is_current_tracker_loading = true;
        });

        it("shows tracker load spinner", () => {
            expect(wrapper.contains(tracker_load_spinner_selector)).toBeTruthy();
        });
        it("does not show anything else", () => {
            expect(wrapper.contains(FirstConfigurationSections)).toBeFalsy();
            expect(wrapper.contains(TransitionsConfigurationHeaderSection)).toBeFalsy();
            expect(wrapper.contains(TransitionsMatrixSection)).toBeFalsy();
        });
    });

    describe("when tracker loaded", () => {
        beforeEach(() => {
            store_wrapper.state.is_current_tracker_load_failed = false;
            store_wrapper.state.is_current_tracker_loading = false;
            store_wrapper.state.current_tracker = {};
            Vue.set(store_wrapper.state, "current_tracker", {});
        });

        describe("when base field not configured", () => {
            beforeEach(() => {
                Vue.set(
                    store_wrapper.state.current_tracker,
                    "workflow",
                    create("workflow", "field_not_defined")
                );
            });
            it("shows first configuration", () => {
                expect(wrapper.contains(FirstConfigurationSections)).toBeTruthy();
            });
        });

        describe("when base field configured", () => {
            beforeEach(() => {
                Vue.set(
                    store_wrapper.state.current_tracker,
                    "workflow",
                    create("workflow", "field_defined")
                );
            });
            it("shows configuration header and matrix", () => {
                expect(wrapper.contains(TransitionsConfigurationHeaderSection)).toBeTruthy();
                expect(wrapper.contains(TransitionsMatrixSection)).toBeTruthy();
            });
        });
    });
});
