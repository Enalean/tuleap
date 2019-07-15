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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue.js";

import FolderContent from "./FolderContent.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

describe("FolderContent", () => {
    let factory, state, store;

    beforeEach(() => {
        state = {
            project_id: 101
        };

        const store_options = {
            state
        };

        store = createStoreMock(store_options);

        factory = (props = {}) => {
            return shallowMount(FolderContent, {
                localVue,
                propsData: { metadata: props },
                mocks: { $store: store }
            });
        };
    });

    it(`Should not display preview when component is rendered`, () => {
        store.state.currently_previewed_item = {};

        store.state.folder_content = [
            {
                id: 42,
                title: "my item title"
            }
        ];

        const wrapper = factory();

        expect(wrapper.contains("[data-test=document-quick-look]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-folder-owner-information]")).toBeTruthy();
    });

    it(`It toogle preview`, () => {
        const item = {
            id: 42,
            title: "my item title"
        };

        store.state.currently_previewed_item = item;

        store.state.folder_content = [
            {
                id: 42,
                title: "my item title"
            }
        ];

        const wrapper = factory();

        wrapper.vm.toggleQuickLook(item);
        expect(wrapper.contains("[data-test=document-quick-look]")).toBeTruthy();

        expect(store.commit).not.toHaveBeenCalledWith("updateCurrentlyPreviewedItem", item);
    });

    it(`It open preview on new item when there is already a previewed items`, () => {
        const item = {
            id: 42,
            title: "my item title"
        };

        const other_item = {
            id: 45,
            title: "my other item title"
        };

        store.state.currently_previewed_item = item;

        store.state.folder_content = [item, other_item];

        const wrapper = factory();

        wrapper.vm.toggleQuickLook(other_item);
        expect(wrapper.contains("[data-test=document-quick-look]")).toBeTruthy();

        expect(store.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", other_item);
    });

    it(`It close preview`, () => {
        const item = {
            id: 42,
            title: "my item title"
        };

        store.state.currently_previewed_item = item;

        store.state.folder_content = [
            {
                id: 42,
                title: "my item title"
            }
        ];

        const wrapper = factory();

        wrapper.vm.closeQuickLook(item);
        expect(wrapper.contains("[data-test=document-quick-look]")).toBeFalsy();
    });
});
