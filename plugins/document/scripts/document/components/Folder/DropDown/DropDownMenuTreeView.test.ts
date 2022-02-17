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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import DropDownMenuTreeView from "./DropDownMenuTreeView.vue";
import type { Folder, Item, ItemFile } from "../../../type";

describe("DropDownMenuTreeView", () => {
    function createWrapper(item: Item): Wrapper<DropDownMenuTreeView> {
        return shallowMount(DropDownMenuTreeView, {
            localVue,
            propsData: { item },
        });
    }
    it(`Given item is a folder and user can write
        Then the drop down enable user to add new folder and new item inside`, () => {
        const wrapper = createWrapper({
            id: 1,
            title: "my item title",
            type: "folder",
            user_can_write: true,
        } as Folder);
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists()
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });
    it(`Given item is not a folder
        Then document can be locked/unlocked`, () => {
        const wrapper = createWrapper({
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: true,
        } as ItemFile);
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists()
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });
    it(`Given item is not a folder and user can write
        Then user can create new version of document`, () => {
        const wrapper = createWrapper({
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: true,
        } as ItemFile);
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists()
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });
    it(`Given user can write
        Then he can update its properties and delete it`, () => {
        const wrapper = createWrapper({
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: true,
        } as ItemFile);
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists()
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });
    it(`Given it is a file and user has read permission
        Then he can't manage document`, () => {
        const wrapper = createWrapper({
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: false,
        } as ItemFile);
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists()
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
    });
    it(`Given it is a folder and user has read permission
        Then he can't manage document`, () => {
        const wrapper = createWrapper({
            id: 1,
            title: "my item title",
            type: "folder",
            user_can_write: false,
        } as Folder);
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists()
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
    });
});
