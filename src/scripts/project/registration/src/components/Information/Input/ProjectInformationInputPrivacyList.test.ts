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
import { State } from "../../../store/type";
import { createStoreMock } from "../../../../../../vue-components/store-wrapper-jest";
import ProjectInformationInputPrivacyList from "./ProjectInformationInputPrivacyList.vue";

describe("ProjectInformationInputPrivacyList", () => {
    describe("The selected default project visibility when the component is mounted -", () => {
        it("Should select the 'Public' by default", async () => {
            const state = {
                project_default_visibility: "public",
                can_user_choose_project_visibility: true,
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
            });

            await wrapper.vm.$nextTick();

            expect((wrapper.get("[data-test=public]").element as HTMLOptionElement).selected).toBe(
                true
            );
        });

        it("Should select the 'Public incl. restricted' by default", async () => {
            const state = {
                project_default_visibility: "unrestricted",
                are_restricted_users_allowed: true,
                can_user_choose_project_visibility: true,
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
            });
            await wrapper.vm.$nextTick();

            expect(
                (wrapper.get("[data-test=unrestricted]").element as HTMLOptionElement).selected
            ).toBe(true);
        });

        it("Should select the 'Private' by default", async () => {
            const state = {
                project_default_visibility: "private-wo-restr",
                can_user_choose_project_visibility: true,
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
            });

            expect(
                (wrapper.get("[data-test=private-wo-restr]").element as HTMLOptionElement).selected
            ).toBe(true);
        });

        it("Should select the 'Private incl. restricted' by default", async () => {
            const state = {
                project_default_visibility: "private",
                are_restricted_users_allowed: true,
                can_user_choose_project_visibility: true,
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
            });
            await wrapper.vm.$nextTick();

            expect((wrapper.get("[data-test=private]").element as HTMLOptionElement).selected).toBe(
                true
            );
        });
    });

    describe("Displayed options depends on platform configuration -", () => {
        it("Displays only public and private when platform does not allow restricted", async () => {
            const state = {
                project_default_visibility: "private",
                are_restricted_users_allowed: false,
                can_user_choose_project_visibility: true,
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
            });
            expect(wrapper.contains("[data-test=unrestricted]")).toBe(false);
            expect(wrapper.contains("[data-test=private]")).toBe(false);
            expect(wrapper.contains("[data-test=private-wo-restr]")).toBe(true);
            expect(wrapper.contains("[data-test=public]")).toBe(true);
        });

        it("Displays all options when restricted are allowed", async () => {
            const state = {
                project_default_visibility: "private",
                are_restricted_users_allowed: true,
                can_user_choose_project_visibility: true,
            } as State;

            const store_options = { state };

            const store = createStoreMock(store_options);

            const wrapper = shallowMount(ProjectInformationInputPrivacyList, {
                localVue: await createProjectRegistrationLocalVue(),
                mocks: { $store: store },
            });

            expect(wrapper.contains("[data-test=private]")).toBe(true);
            expect(wrapper.contains("[data-test=private-wo-restr]")).toBe(true);
            expect(wrapper.contains("[data-test=unrestricted]")).toBe(true);
            expect(wrapper.contains("[data-test=public]")).toBe(true);
        });
    });
});
