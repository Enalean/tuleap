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
import { createLocalVueForTests } from "../support/local-vue.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("BaseTrackerWorkflowTransitions", () => {
    let store;

    beforeEach(() => {
        store = createStoreMock(store_options, { is_operation_running: false });
    });

    const getWrapper = async () => {
        return shallowMount(BaseTrackerWorkflowTransitions, {
            mocks: {
                $store: store,
            },
            localVue: await createLocalVueForTests(),
            propsData: { trackerId: 1 },
        });
    };

    afterEach(() => store.reset());

    describe("when tracker load failed", () => {
        beforeEach(() => {
            store.state.is_current_tracker_load_failed = true;
        });

        it("shows tracker load error message", async () => {
            const wrapper = await getWrapper();
            expect(
                wrapper.find('[data-test-type="tracker-load-error-message"]').exists()
            ).toBeTruthy();
        });
        it("does not show anything else", async () => {
            const wrapper = await getWrapper();
            expect(wrapper.findComponent(FirstConfigurationSections).exists()).toBeFalsy();
            expect(wrapper.findComponent(HeaderSection).exists()).toBeFalsy();
            expect(wrapper.findComponent(TransitionsMatrixSection).exists()).toBeFalsy();
        });
    });

    describe("when tracker loading", () => {
        beforeEach(() => {
            store.state.is_current_tracker_load_failed = false;
            store.state.is_current_tracker_loading = true;
        });

        it("shows tracker load spinner", async () => {
            const wrapper = await getWrapper();
            expect(wrapper.find('[data-test-type="tracker-load-spinner"]').exists()).toBeTruthy();
        });
        it("does not show anything else", async () => {
            const wrapper = await getWrapper();
            expect(wrapper.findComponent(FirstConfigurationSections).exists()).toBeFalsy();
            expect(wrapper.findComponent(HeaderSection).exists()).toBeFalsy();
            expect(wrapper.findComponent(TransitionsMatrixSection).exists()).toBeFalsy();
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

            it("and there is a selectbox field, then it shows first configuration", async () => {
                store.getters.has_selectbox_fields = true;

                const wrapper = await getWrapper();
                expect(wrapper.findComponent(FirstConfigurationSections).exists()).toBeTruthy();
            });

            it("does not show rules enforcement warning", async () => {
                const wrapper = await getWrapper();
                expect(
                    wrapper.findComponent(TransitionRulesEnforcementWarning).exists()
                ).toBeFalsy();
            });

            it("when base field is not configured and there is no selectbox field, then it shows that first configuration is impossible", async () => {
                store.getters.has_selectbox_fields = false;
                const wrapper = await getWrapper();
                await wrapper.vm.$nextTick();

                expect(
                    wrapper.findComponent(FirstConfigurationImpossibleWarning).exists()
                ).toBeTruthy();
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
            it("shows configuration header and matrix", async () => {
                const wrapper = await getWrapper();
                expect(wrapper.findComponent(HeaderSection).exists()).toBeTruthy();
                expect(wrapper.findComponent(TransitionsMatrixSection).exists()).toBeTruthy();
            });
            it("shows rules enforcement warning", async () => {
                const wrapper = await getWrapper();
                expect(
                    wrapper.findComponent(TransitionRulesEnforcementWarning).exists()
                ).toBeTruthy();
            });
        });
    });
});
