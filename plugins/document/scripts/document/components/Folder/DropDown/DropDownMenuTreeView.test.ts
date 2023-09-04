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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DropDownMenuTreeView from "./DropDownMenuTreeView.vue";
import type { Folder, Item, ItemFile } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { nextTick } from "vue";

describe("DropDownMenuTreeView", () => {
    function createWrapper(
        item: Item,
        forbid_writers_to_update: boolean,
        forbid_writers_to_delete: boolean,
    ): VueWrapper<InstanceType<typeof DropDownMenuTreeView>> {
        return shallowMount(DropDownMenuTreeView, {
            props: { item },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            namespaced: true,
                            state: {
                                forbid_writers_to_update,
                                forbid_writers_to_delete,
                            },
                        },
                    },
                }),
            },
        });
    }
    it(`Given item is a folder and user can write
        Then the drop down enable user to add new folder and new item inside`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "folder",
                user_can_write: true,
            } as Folder,
            false,
            false,
        );
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists(),
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });
    it(`Given item is not a folder
        Then document can be locked/unlocked`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            } as ItemFile,
            false,
            false,
        );
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists(),
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });

    it(`Given item is a file
        Then it can be downloaded`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            } as ItemFile,
            false,
            false,
        );
        expect(
            wrapper.find("[data-test=document-dropdown-menu-download-file]").exists(),
        ).toBeTruthy();
    });

    it(`Given item is not a file
        Then it cannot be downloaded`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "folder",
                user_can_write: true,
            } as Folder,
            false,
            false,
        );
        expect(
            wrapper.find("[data-test=document-dropdown-menu-download-file]").exists(),
        ).toBeFalsy();
    });

    it(`Given item is not a folder and user can write
        Then user can create new version of document`, async () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            } as ItemFile,
            false,
            false,
        );
        await nextTick();

        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists(),
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });
    it(`Given user can write
        Then he can update its properties and delete it`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            } as ItemFile,
            false,
            false,
        );
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists(),
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });
    it(`Given it is a file and user has read permission
        Then he can't manage document`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: false,
            } as ItemFile,
            false,
            false,
        );
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists(),
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
    });
    it(`Given it is a folder and user has read permission
        Then he can't manage document`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "folder",
                user_can_write: false,
            } as Folder,
            false,
            false,
        );
        expect(wrapper.find("[data-test=document-folder-title]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-folder-content-creation]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-dropdown-create-new-version-button]").exists(),
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to update properties
        And user is writer
        When we display the menu
        Then it does not display update properties entry`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
                can_user_manage: false,
            } as ItemFile,
            true,
            false,
        );

        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to update properties
        And user is manager
        When we display the menu
        Then it displays update properties entry`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
                can_user_manage: true,
            } as ItemFile,
            true,
            false,
        );

        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });

    it(`Given writers are not allowed to delete
        And user is writer
        When we display the menu
        Then it does not display delete entry`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
                can_user_manage: false,
            } as ItemFile,
            true,
            true,
        );

        expect(wrapper.find("[data-test=document-dropdown-delete]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to delete
        And user is manager
        When we display the menu
        Then it displays delete entry`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
                can_user_manage: true,
            } as ItemFile,
            true,
            true,
        );

        expect(wrapper.find("[data-test=document-dropdown-delete]").exists()).toBeTruthy();
    });
});
