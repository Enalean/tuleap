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
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import localVue from "../../../../helpers/local-vue.js";
import FolderDefaultPropertiesForCreate from "./FolderDefaultPropertiesForCreate.vue";

describe("FolderDefaultPropertiesForCreate", () => {
    let default_property, state, store;
    beforeEach(() => {
        state = {
            is_item_status_metadata_used: false
        };

        const store_options = { state };

        store = createStoreMock(store_options);

        default_property = (props = {}) => {
            return shallowMount(FolderDefaultPropertiesForCreate, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });

    it(`Display the update properties container when status is enabled for project`, () => {
        const wrapper = default_property({
            currentlyUpdatedItem: {
                id: 123,
                title: "My title"
            },
            parent: {
                id: 456,
                title: "My parent"
            }
        });

        store.state.is_item_status_metadata_used = true;

        expect(
            wrapper.find("[data-test=document-folder-default-properties-container]").exists()
        ).toBeTruthy();
    });

    it(`Default properties are not displayed if project does not use status`, () => {
        const wrapper = default_property({
            currentlyUpdatedItem: {}
        });

        expect(
            wrapper.find("[data-test=document-folder-default-properties-container]").exists()
        ).toBeFalsy();
    });
});
