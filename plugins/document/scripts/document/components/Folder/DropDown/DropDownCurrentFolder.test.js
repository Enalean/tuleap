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
import DropDownCurrentFolder from "./DropDownCurrentFolder.vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

describe("DropDownCurrentFolder", () => {
    let dropdown_factory, state, store, store_options;
    beforeEach(() => {
        state = {
            current_folder: {
                id: 42,
                title: "current folder title",
            },
        };

        store_options = {
            state,
        };
        store = createStoreMock(store_options);

        dropdown_factory = () => {
            return shallowMount(DropDownCurrentFolder, {
                localVue,
                mocks: { $store: store },
            });
        };
    });

    it(`Given user does not have write permission on current folder
        When we display the dropdown
        Then user should not be able to manipulate folder content`, () => {
        store.state.current_folder.user_can_write = false;

        const wrapper = dropdown_factory();

        expect(wrapper.contains("[data-test=document-new-folder-creation-button]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-delete-folder-button]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-delete-folder-separator]")).toBeFalsy();
    });

    it(`Given user has write permission on current folder
        When we display the dropdown
        Then user should be able to manipulate folder content`, () => {
        store.state.current_folder.user_can_write = true;
        store.state.current_folder.parent_id = 3;

        const wrapper = dropdown_factory();

        expect(wrapper.contains("[data-test=document-new-folder-creation-button]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-delete-folder-button]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-delete-folder-separator]")).toBeTruthy();
    });

    it(`Given user is docman writer and the current folder is not the root folder
        When we display the menu
        Then the delete button should be available`, () => {
        store.state.current_folder.user_can_write = true;
        store.state.current_folder.parent_id = 3;

        const wrapper = dropdown_factory();
        expect(wrapper.contains("[data-test=document-delete-folder-button]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-delete-folder-separator]")).toBeTruthy();
    });

    it(`Given user is docman writer and folder is root folder
        When we display the menu
        Then the delete button should NOT be available`, () => {
        store.state.current_folder.user_can_write = true;
        const wrapper = dropdown_factory();

        expect(wrapper.contains("[data-test=document-delete-folder-button]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-delete-folder-separator]")).toBeFalsy();
    });

    it(`Given user is NOT docman writer
        When we display the menu
        Then the delete button should NOT be available`, () => {
        store.state.current_folder.user_can_write = false;

        const wrapper = dropdown_factory();
        expect(wrapper.contains("[data-test=document-delete-folder-button]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-delete-folder-separator]")).toBeFalsy();
    });
});
