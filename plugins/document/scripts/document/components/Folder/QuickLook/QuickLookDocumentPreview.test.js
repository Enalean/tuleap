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
import QuickLookDocumentPreview from "./QuickLookDocumentPreview.vue";
import { TYPE_EMBEDDED, TYPE_FILE, TYPE_LINK } from "../../../constants.js";

import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";

describe("QuickLookDocumentPreview", () => {
    let preview_factory, state, store;

    beforeEach(() => {
        state = {};

        const store_options = { state };

        store = createStoreMock(store_options);
        store.getters.is_item_a_folder = () => false;
        store.getters.is_item_an_embedded_file = () => false;

        preview_factory = (props = {}) => {
            return shallowMount(QuickLookDocumentPreview, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it("Renders an image if the item is an image", () => {
        store.state.currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_FILE,
            file_properties: {
                file_type: "image/png",
            },
        };

        const wrapper = preview_factory();

        expect(wrapper.contains(".document-quick-look-image-container")).toBeTruthy();
        expect(wrapper.contains(".document-quick-look-embedded")).toBeFalsy();
        expect(wrapper.contains(".document-quick-look-icon-container")).toBeFalsy();
    });

    it("Renders some rich text html if the item is an embedded file", () => {
        store.state.currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_EMBEDDED,
            embedded_file_properties: {
                content: "<h1>Hello world!</h1>",
            },
        };

        store.getters.is_item_an_embedded_file = () => true;

        const wrapper = preview_factory();

        expect(wrapper.contains(".document-quick-look-embedded")).toBeTruthy();
        expect(wrapper.contains(".document-quick-look-image-container")).toBeFalsy();
        expect(wrapper.contains(".document-quick-look-icon-container")).toBeFalsy();
    });

    it("Displays the icon passed in props otherwise", () => {
        store.state.currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_LINK,
        };

        const wrapper = preview_factory({ iconClass: "fa-link" });

        expect(wrapper.contains(".fa-link")).toBeTruthy();
        expect(wrapper.contains(".document-quick-look-icon-container")).toBeTruthy();
        expect(wrapper.contains(".document-quick-look-image-container")).toBeFalsy();
        expect(wrapper.contains(".document-quick-look-embedded")).toBeFalsy();
    });

    it("Display spinner when embedded file is loaded", async () => {
        store.state.currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_EMBEDDED,
        };
        store.state.is_loading_currently_previewed_item = true;

        const wrapper = preview_factory({ iconClass: "fa-link" });

        expect(wrapper.contains("[data-test=document-preview-spinner]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-quick-look-embedded]")).toBeFalsy();

        store.state.currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_EMBEDDED,
            embedded_file_properties: {
                content: "custom content",
            },
        };
        await wrapper.vm.$nextTick();
        store.state.is_loading_currently_previewed_item = false;
        await wrapper.vm.$nextTick();
        expect(wrapper.contains("[data-test=document-preview-spinner]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-quick-look-embedded]")).toBeTruthy();
    });

    it("Do not display spinner for other types", () => {
        store.state.currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_FILE,
        };
        store.state.is_loading_currently_previewed_item = true;

        const wrapper = preview_factory({ iconClass: "fa-link" });

        expect(wrapper.contains("[data-test=document-preview-spinner]")).toBeFalsy();

        store.state.is_loading_currently_previewed_item = false;
        expect(wrapper.contains("[data-test=document-preview-spinner]")).toBeFalsy();
    });
});
