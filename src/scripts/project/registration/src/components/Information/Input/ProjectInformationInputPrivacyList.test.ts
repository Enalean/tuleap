/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import type { RootState } from "../../../store/type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import ProjectInformationInputPrivacyList from "./ProjectInformationInputPrivacyList.vue";
import type { ConfigurationState } from "../../../store/configuration";
import * as list_picker from "@tuleap/list-picker";

describe("ProjectInformationInputPrivacyList", () => {
    beforeEach(() => {
        jest.spyOn(list_picker, "createListPicker").mockResolvedValue({
            destroy: () => {
                // Nothing to do since we did not really create something
            },
        });
    });

    describe("The selected default project visibility when the component is mounted -", () => {
        it("Should select the 'Public' by default", async () => {
            const configuration_state: ConfigurationState = {
                project_default_visibility: "public",
            } as ConfigurationState;

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: configuration_state,
                        } as RootState,
                    }),
                },
            });

            await wrapper.vm.$nextTick();

            expect((wrapper.get("[data-test=public]").element as HTMLOptionElement).selected).toBe(
                true
            );
        });

        it("Should select the 'Public incl. restricted' by default", async () => {
            const configuration_state: ConfigurationState = {
                project_default_visibility: "unrestricted",
                are_restricted_users_allowed: true,
            } as ConfigurationState;

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: configuration_state,
                        } as RootState,
                    }),
                },
            });
            await wrapper.vm.$nextTick();

            expect(
                (wrapper.get("[data-test=unrestricted]").element as HTMLOptionElement).selected
            ).toBe(true);
        });

        it("Should select the 'Private' by default", async () => {
            const configuration_state: ConfigurationState = {
                project_default_visibility: "private",
            } as ConfigurationState;

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: configuration_state,
                        } as RootState,
                    }),
                },
            });

            expect((wrapper.get("[data-test=private]").element as HTMLOptionElement).selected).toBe(
                true
            );
        });

        it("Should select the 'Private incl. restricted' by default", async () => {
            const configuration_state: ConfigurationState = {
                project_default_visibility: "private",
                are_restricted_users_allowed: true,
            } as ConfigurationState;

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: configuration_state,
                        } as RootState,
                    }),
                },
            });
            await wrapper.vm.$nextTick();

            expect((wrapper.get("[data-test=private]").element as HTMLOptionElement).selected).toBe(
                true
            );
        });
    });

    describe("Displayed options depends on platform configuration -", () => {
        it("Displays only public and private when platform does not allow restricted", async () => {
            const configuration_state: ConfigurationState = {
                project_default_visibility: "private",
                are_restricted_users_allowed: false,
            } as ConfigurationState;

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: configuration_state,
                        } as RootState,
                    }),
                },
            });
            expect(wrapper.find("[data-test=unrestricted]").exists()).toBe(false);
            expect(wrapper.find("[data-test=private]").exists()).toBe(true);
            expect(wrapper.find("[data-test=private-wo-restr]").exists()).toBe(false);
            expect(wrapper.find("[data-test=public]").exists()).toBe(true);
        });

        it("Displays all options when restricted are allowed", async () => {
            const configuration_state: ConfigurationState = {
                project_default_visibility: "private",
                are_restricted_users_allowed: true,
            } as ConfigurationState;

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: {
                    $store: createStoreMock({
                        state: {
                            configuration: configuration_state,
                        } as RootState,
                    }),
                },
            });
            expect(wrapper.find("[data-test=private]").exists()).toBe(true);
            expect(wrapper.find("[data-test=private-wo-restr]").exists()).toBe(true);
            expect(wrapper.find("[data-test=unrestricted]").exists()).toBe(true);
            expect(wrapper.find("[data-test=public]").exists()).toBe(true);
        });
    });
});
