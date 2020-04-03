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
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";

describe("DisplayEmbeddedContent", () => {
    let factory, store;

    beforeEach(() => {
        const store_options = {};

        store = createStoreMock(store_options);

        factory = (props = {}) => {
            return shallowMount(DisplayEmbeddedContent, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`renders an embedded document in narrow view`, () => {
        store.state.is_embedded_in_large_view = false;
        store.state.currently_previewed_item = {
            id: 42,
            title: "My embedded content",
            embedded_file_properties: {
                content: "My content",
            },
        };
        const wrapper = factory({
            isInLargeView: false,
        });

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toEqual(["tlp-pane", "embedded-document", "narrow"]);
    });

    it(`renders an embedded document in large view`, () => {
        store.state.is_embedded_in_large_view = true;
        store.state.currently_previewed_item = {
            id: 42,
            title: "My embedded content",
            embedded_file_properties: {
                content: "My content",
            },
        };
        const wrapper = factory({
            isInLargeView: true,
        });

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toEqual(["tlp-pane", "embedded-document"]);
    });

    it(`does not throw error if embedded_file_properties key is missing`, () => {
        store.state.is_embedded_in_large_view = true;
        store.state.currently_previewed_item = {
            id: 42,
            title: "My embedded content",
        };
        const wrapper = factory({
            isInLargeView: true,
        });

        const element = wrapper.get("[data-test=display-embedded-content]");
        expect(element.classes()).toEqual(["tlp-pane", "embedded-document"]);
    });
});
