/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import { createLocalVue, shallowMount, Wrapper } from "@vue/test-utils";
import EditAccessTokenGitlabModal from "./EditAccessTokenGitlabModal.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import AccessTokenFormModal from "./AccessTokenFormModal.vue";

describe("EditAccessTokenGitlabModal", () => {
    let store, localVue;

    function instantiateComponent(): Wrapper<EditAccessTokenGitlabModal> {
        store = createStoreMock({});
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        return shallowMount(EditAccessTokenGitlabModal, {
            mocks: { $store: store },
            localVue,
        });
    }

    it("When a user displays the modal ,then the AccessTokenFormModal is displayed", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            repository: {
                id: 10,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(AccessTokenFormModal).exists()).toBeTruthy();
    });

    it("When CredentialsFormModal emits on-close-modal, Then form is reset", async () => {
        const wrapper = instantiateComponent();

        wrapper.setData({
            repository: {
                id: 10,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.repository).toEqual({ id: 10 });

        wrapper.findComponent(AccessTokenFormModal).vm.$emit("on-close-modal");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.repository).toEqual(null);
    });
});
