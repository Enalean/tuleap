/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 */

import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import { shallowMount } from "@vue/test-utils";
import ListProjectsModal from "./ListProjectsModal.vue";
import localVue from "../../support/local-vue";

describe("ListProjectsModal", () => {
    let store_options, store, propsData;
    beforeEach(() => {
        store_options = {
            state: {
                used_service_name: [],
                is_first_load_done: true,
            },
            getters: {
                areExternalUsedServices: false,
                isCurrentRepositoryListEmpty: false,
                isInitialLoadingDoneWithoutError: true,
            },
        };
    });

    function instantiateComponent() {
        store = createStoreMock(store_options);
        return shallowMount(ListProjectsModal, {
            propsData,
            mocks: { $store: store },
            localVue,
        });
    }

    it("When there is no project, Then an empty message is displayed", () => {
        propsData = { projects: [] };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=gitlab-empty-projects]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=gitlab-empty-projects]").text()).toEqual(
            "No project is available with your GitLab account"
        );
    });

    it("When there are projects, Then no empty message is displayed and projects are displayed", () => {
        propsData = {
            projects: [
                { id: 10, name_with_namespace: "My Path / Project" },
                { id: 11, name_with_namespace: "My Second / Project", avatar_url: "example.com" },
            ],
        };
        const wrapper = instantiateComponent();

        expect(wrapper.find("[data-test=gitlab-empty-projects]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=gitlab-projects-displayed-10]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=gitlab-projects-displayed-11]").exists()).toBeTruthy();
    });

    it("When no project is selected, Then integrate button is disabled", async () => {
        propsData = {
            projects: [
                { id: 10, name_with_namespace: "My Path / Project" },
                { id: 11, name_with_namespace: "My Second / Project", avatar_url: "example.com" },
            ],
        };
        const wrapper = instantiateComponent();

        wrapper.setData({
            selected_project: null,
        });
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button_integrate_gitlab_project]").attributes().disabled
        ).toBeTruthy();

        wrapper.setData({
            selected_project: { id: 10, name_with_namespace: "My Path / Project" },
        });

        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button_integrate_gitlab_project]").attributes().disabled
        ).toBeFalsy();
    });

    it("When user clicks on back button, Then event is emitted", async () => {
        const wrapper = instantiateComponent();

        wrapper.find("[data-test=gitlab-button-back]").trigger("click");

        await wrapper.vm.$nextTick();
        expect(wrapper.emitted("to-back-button")).toBeTruthy();
    });
});
