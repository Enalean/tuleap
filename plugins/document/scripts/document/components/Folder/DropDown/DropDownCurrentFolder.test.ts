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
import DropDownCurrentFolder from "./DropDownCurrentFolder.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { Folder, State } from "../../../type";

describe("DropDownCurrentFolder", () => {
    function createWrapper(
        isInFolderEmptyState: boolean,
        user_can_write: boolean,
        parent_id: number
    ): Wrapper<DropDownCurrentFolder> {
        const state = {
            current_folder: {
                id: 42,
                title: "current folder title",
                user_can_write,
                parent_id,
            } as Folder,
        } as State;
        const store_options = {
            state,
        };
        const store = createStoreMock(store_options);
        return shallowMount(DropDownCurrentFolder, {
            localVue,
            mocks: { $store: store },
            propsData: { isInFolderEmptyState },
        });
    }

    it(`Given user does not have write permission on current folder
        When we display the dropdown
        Then user should not be able to manipulate folder content`, () => {
        const wrapper = createWrapper(false, false, 3);

        expect(
            wrapper.find("[data-test=document-new-folder-creation-button]").exists()
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeFalsy();
    });

    it(`Given user has write permission on current folder
        When we display the dropdown
        Then user should be able to manipulate folder content`, () => {
        const wrapper = createWrapper(false, true, 3);

        expect(
            wrapper.find("[data-test=document-new-folder-creation-button]").exists()
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeTruthy();
    });

    it(`Given user is docman writer and the current folder is not the root folder
        When we display the menu
        Then the delete button should be available`, () => {
        const wrapper = createWrapper(false, true, 3);

        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeTruthy();
    });

    it(`Given user is docman writer and folder is root folder
        When we display the menu
        Then the delete button should NOT be available`, () => {
        const wrapper = createWrapper(false, true, 0);

        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeFalsy();
    });

    it(`Given user is NOT docman writer
        When we display the menu
        Then the delete button should NOT be available`, () => {
        const wrapper = createWrapper(false, false, 3);
        expect(wrapper.find("[data-test=document-delete-folder-button]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-folder-separator]").exists()).toBeFalsy();
    });
});
