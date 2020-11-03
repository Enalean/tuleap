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

import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import UnlinkRepositoryGitlabModal from "./UnlinkRepositoryGitlabModal.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";

describe("UnlinkRepositoryGitlabModal", () => {
    let store_options, store, propsData, localVue;
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
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });
        return shallowMount(UnlinkRepositoryGitlabModal, {
            propsData,
            mocks: { $store: store },
            localVue,
        });
    }

    it("When the component is diplayed, Then confirmation message contains the label of repository", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            repository: {
                id: 10,
                label: "My project",
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=confirm-unlink-gitlab-message]").text()).toEqual(
            "Wow, wait a minute. You are about to unlink the GitLab repository My project. Please confirm your action."
        );
    });
});
