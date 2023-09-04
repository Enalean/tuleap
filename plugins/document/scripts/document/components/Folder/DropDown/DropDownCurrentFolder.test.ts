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
import DropDownCurrentFolder from "./DropDownCurrentFolder.vue";
import type { Folder, RootState } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("DropDownCurrentFolder", () => {
    function createWrapper(
        isInFolderEmptyState: boolean,
        user_can_write: boolean,
        can_user_manage: boolean,
        parent_id: number,
        forbid_writers_to_update: boolean,
        forbid_writers_to_delete: boolean,
    ): VueWrapper<InstanceType<typeof DropDownCurrentFolder>> {
        return shallowMount(DropDownCurrentFolder, {
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
                    state: {
                        current_folder: {
                            id: 42,
                            title: "current folder title",
                            user_can_write,
                            can_user_manage,
                            parent_id,
                        } as Folder,
                    } as unknown as RootState,
                }),
            },
            props: { isInFolderEmptyState },
        });
    }

    it(`Given user does not have write permission on current folder
        When we display the dropdown
        Then user should not be able to manipulate folder content`, () => {
        const wrapper = createWrapper(false, false, false, 3, false, false);

        expect(
            wrapper.find("[data-test=document-new-folder-creation-button]").exists(),
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeFalsy();
    });

    it(`Given user is docman writer and the current folder is not the root folder
        When we display the menu
        Then the delete button should be available`, () => {
        const wrapper = createWrapper(false, true, false, 3, false, false);

        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeTruthy();
    });

    it(`Given user is docman writer and folder is root folder
        When we display the menu
        Then the delete button should NOT be available`, () => {
        const wrapper = createWrapper(false, true, false, 0, false, false);

        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeFalsy();
    });

    it(`Given user is NOT docman writer
        When we display the menu
        Then the delete button should NOT be available`, () => {
        const wrapper = createWrapper(false, false, false, 3, false, false);
        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to update properties
        And user is writer
        When we display the menu
        Then it does not display update properties entry`, () => {
        const wrapper = createWrapper(false, true, false, 3, true, false);

        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to update properties
        And user is manager
        When we display the menu
        Then it displays update properties entry`, () => {
        const wrapper = createWrapper(false, true, true, 3, true, false);

        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });

    it(`Given writers are not allowed to delete
        And user is writer
        When we display the menu
        Then it does not display delete entry`, () => {
        const wrapper = createWrapper(false, true, false, 3, true, true);

        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to delete
        And user is manager
        When we display the menu
        Then it displays delete entry`, () => {
        const wrapper = createWrapper(false, true, true, 3, true, true);

        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeTruthy();
    });
});
