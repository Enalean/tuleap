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
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../helpers/local-vue.js";
import DropDownMenuTreeView from "./DropDownMenuTreeView.vue";

describe("DropDownMenuTreeView", () => {
    let dropdown_quicklook_menu_factory, store;
    beforeEach(() => {
        store = createStoreMock({});
        dropdown_quicklook_menu_factory = (props = {}) => {
            return shallowMount(DropDownMenuTreeView, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });
    it(`Given item is a folder and user can write
        Then the drop down enable user to add new folder and new item inside`, () => {
        store.getters.is_item_a_folder = () => true;
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "folder",
                user_can_write: true,
            },
        });
        expect(wrapper.contains("[data-test=document-folder-title]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-folder-content-creation]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeFalsy();
        expect(
            wrapper.contains("[data-test=document-dropdown-create-new-version-button]")
        ).toBeFalsy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeTruthy();
    });
    it(`Given item is not a folder
        Then document can be locked/unlocked`, () => {
        store.getters.is_item_a_folder = () => false;
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            },
        });
        expect(wrapper.contains("[data-test=document-folder-title]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-folder-content-creation]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeTruthy();
        expect(
            wrapper.contains("[data-test=document-dropdown-create-new-version-button]")
        ).toBeTruthy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeTruthy();
    });
    it(`Given item is not a folder and user can write
        Then user can create new version of document`, () => {
        store.getters.is_item_a_folder = () => false;
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            },
        });
        expect(wrapper.contains("[data-test=document-folder-title]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-folder-content-creation]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeTruthy();
        expect(
            wrapper.contains("[data-test=document-dropdown-create-new-version-button]")
        ).toBeTruthy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeTruthy();
    });
    it(`Given user can write
        Then he can update its properties and delete it`, () => {
        store.getters.is_item_a_folder = () => false;
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            },
        });
        expect(wrapper.contains("[data-test=document-folder-title]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-folder-content-creation]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeTruthy();
        expect(
            wrapper.contains("[data-test=document-dropdown-create-new-version-button]")
        ).toBeTruthy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeTruthy();
    });
    it(`Given it is a file and user has read permission
        Then he can't manage document`, () => {
        store.getters.is_item_a_folder = () => false;
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: false,
            },
        });
        expect(wrapper.contains("[data-test=document-folder-title]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-folder-content-creation]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeTruthy();
        expect(
            wrapper.contains("[data-test=document-dropdown-create-new-version-button]")
        ).toBeFalsy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeFalsy();
    });
    it(`Given it is a folder and user has read permission
        Then he can't manage document`, () => {
        store.getters.is_item_a_folder = () => true;
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "folder",
                user_can_write: false,
            },
        });
        expect(wrapper.contains("[data-test=document-folder-title]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-folder-content-creation]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeFalsy();
        expect(
            wrapper.contains("[data-test=document-dropdown-create-new-version-button]")
        ).toBeFalsy();
        expect(wrapper.contains("[data-test=document-update-properties]")).toBeFalsy();
    });
});
