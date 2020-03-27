/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import Vuex from "vuex";
import VueRouter from "vue-router";
import { shallowMount } from "@vue/test-utils";
import EmbeddedCellTitle from "./EmbeddedCellTitle.vue";
import localVue from "../../../helpers/local-vue";
import { TYPE_EMBEDDED } from "../../../constants.js";

describe("EmbeddedCellTitle", () => {
    let embedded_cell_title_factory, store;

    beforeEach(() => {
        const router = new VueRouter({
            routes: [
                {
                    path: "/folder/1/42",
                    name: "item",
                },
            ],
        });

        store = new Vuex.Store();
        store.state.current_folder = {
            id: 1,
            title: "My current folder",
        };

        store.getters.current_folder_title = "My folder";

        embedded_cell_title_factory = (props = {}) => {
            return shallowMount(EmbeddedCellTitle, {
                localVue,
                router,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given embedded_file_properties is not set
        When we display item title
        Then we should display corrupted badge`, () => {
        const item = {
            id: 42,
            title: "my corrupted embedded document",
            embedded_file_properties: null,
            type: TYPE_EMBEDDED,
        };

        const wrapper = embedded_cell_title_factory({
            item,
        });

        expect(wrapper.contains(".document-badge-corrupted")).toBeTruthy();
    });

    it(`Given embedded_file_properties is set
        When we display item title
        Then we should not display corrupted badge`, () => {
        const item = {
            id: 42,
            title: "my corrupted embedded document",
            embedded_file_properties: {
                file_type: "text/html",
                content: "<p>this is my custom embedded content</p>",
                type: TYPE_EMBEDDED,
            },
        };

        const wrapper = embedded_cell_title_factory({
            item,
        });

        expect(wrapper.contains(".document-badge-corrupted")).toBeFalsy();
    });
});
