/*
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

import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import GitlabProjectModal from "./GitlabProjectModal.vue";

describe("GitlabProjectModal", () => {
    let store_options;
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
        const store = createStoreMock(store_options);
        return shallowMount(GitlabProjectModal, {
            mocks: { $store: store },
            localVue,
        });
    }

    it("When the user clicked on the button, Then the submit button is disabled ans icon changed", async () => {
        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain("fa-arrow-right");

        wrapper.setData({
            is_loading: true,
            gitlab_server: "https://example.com",
            gitlab_token_user: "AFREZF546",
        });

        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button_add_gitlab_project]").attributes().disabled
        ).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").classes()).toContain("fa-sync-alt");
    });
});
