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
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../helpers/local-vue.js";
import DropDownDisplayedEmbedded from "./DropDownDisplayedEmbedded.vue";

describe("DropDownDisplayedEmbedded", () => {
    let factory, store, state, store_options;
    beforeEach(() => {
        state = {
            currently_previewed_item: {
                id: 42,
                title: "current folder title",
            },
        };

        store_options = {
            state,
        };
        store = createStoreMock(store_options);
        factory = (props = {}) => {
            return shallowMount(DropDownDisplayedEmbedded, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given user can write item
        Then he can update its properties and delete it`, () => {
        store.state.currently_previewed_item.user_can_write = true;
        store.state.currently_previewed_item.parent_id = 102;
        const wrapper = factory();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-delete-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-separator]")).toBeTruthy();
    });

    it(`Given user can write item, and given folder is root folder
        Then he can update its properties but he can not delete ir`, () => {
        store.state.currently_previewed_item.user_can_write = true;
        const wrapper = factory();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-delete-item]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-separator]")).toBeTruthy();
    });

    it(`Given user has read permission on item
        Then he can't manage document`, () => {
        store.state.currently_previewed_item.user_can_write = false;
        const wrapper = factory();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-delete-item]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-separator]")).toBeFalsy();
    });
});
