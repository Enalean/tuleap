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

import Vuex from "vuex";
import { shallowMount } from "@vue/test-utils";
import Preview from "./QuickLookDocumentPreview.vue";
import { TYPE_FILE, TYPE_EMBEDDED, TYPE_LINK } from "../../../constants.js";

import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../helpers/store-wrapper.spec-helper.js";

describe("QuickLookDocumentPreview", () => {
    let store_options, store;

    beforeEach(() => {
        store_options = {};

        store = createStoreMock(store_options);
    });

    it("Renders an image if the item is an image", () => {
        const item = {
            id: 42,
            parent_id: 66,
            type: TYPE_FILE,
            file_properties: {
                file_type: "image/png"
            }
        };
        const component_options = {
            localVue,
            propsData: {
                item
            }
        };

        const wrapper = shallowMount(Preview, { store, ...component_options });

        expect(wrapper.contains(".document-quick-look-image-container")).toBeTruthy();
        expect(wrapper.contains(".document-quick-look-embedded")).toBeFalsy();
        expect(wrapper.contains(".document-quick-look-icon-container")).toBeFalsy();
    });

    it("Renders some rich text html if the item is an embedded file", () => {
        const item = {
            id: 42,
            parent_id: 66,
            type: TYPE_EMBEDDED,
            embedded_file_properties: {
                content: "<h1>Hello world!</h1>"
            }
        };
        const component_options = {
            localVue,
            propsData: {
                item
            }
        };

        const store = new Vuex.Store({
            state: {}
        });
        const wrapper = shallowMount(Preview, { store, ...component_options });

        expect(wrapper.contains(".document-quick-look-embedded")).toBeTruthy();
        expect(wrapper.contains(".document-quick-look-image-container")).toBeFalsy();
        expect(wrapper.contains(".document-quick-look-icon-container")).toBeFalsy();
    });

    it("Displays the icon passed in props otherwise", () => {
        const item = {
            id: 42,
            parent_id: 66,
            type: TYPE_LINK
        };
        const component_options = {
            localVue,
            propsData: {
                item,
                iconClass: "fa-link"
            }
        };

        const store = new Vuex.Store({
            state: {}
        });
        const wrapper = shallowMount(Preview, { store, ...component_options });

        expect(wrapper.contains(".fa-link")).toBeTruthy();
        expect(wrapper.contains(".document-quick-look-icon-container")).toBeTruthy();
        expect(wrapper.contains(".document-quick-look-image-container")).toBeFalsy();
        expect(wrapper.contains(".document-quick-look-embedded")).toBeFalsy();
    });
});
