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
import { createStoreMock } from "../../../helpers/store-wrapper.spec-helper.js";
import localVue from "../../../helpers/local-vue.js";
import DropdownMenu from "./DropdownMenu.vue";

describe("DropdownMenu", () => {
    let dropdown_menu_factory;
    beforeEach(() => {
        const state = {
            max_files_dragndrop: 10,
            max_size_upload: 10000,
            project_id: 101
        };

        const store_options = {
            state
        };

        const store = createStoreMock(store_options);
        dropdown_menu_factory = (props = {}) => {
            return shallowMount(DropdownMenu, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });
    it(`Given item title should be hidden (button displayed in quick look view)
        When we display the menu
        Then the folder name should not be displayed`, () => {
        const wrapper = dropdown_menu_factory({
            hideItemTitle: true,
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                can_user_manage: false
            }
        });

        expect(wrapper.contains(".document-dropdown-menu-title")).toBeFalsy();
    });

    it(`Given item title should be displayed (button displayed in document tree view)
        When we display the menu
        Then the folder name should be displayed`, () => {
        const wrapper = dropdown_menu_factory({
            hideItemTitle: false,
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                can_user_manage: false
            }
        });

        expect(wrapper.contains(".document-dropdown-menu-title")).toBeTruthy();
    });

    it(`Given user can't write in folder
        When we display the menu
        Then the display link should not be available`, () => {
        const wrapper = dropdown_menu_factory({
            hideDetailsEntry: true,
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                can_user_manage: false
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-details]")).toBeFalsy();
    });

    it(`Given user is docman writer
        When we display the menu
        Then the display link should be available`, () => {
        const wrapper = dropdown_menu_factory({
            hideDetailsEntry: false,
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                can_user_manage: true
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-details]")).toBeTruthy();
    });

    it(`Given user is administrator
        When we display the menu
        Then the permission link should be available`, () => {
        const wrapper = dropdown_menu_factory({
            item: {
                id: 4,
                title: "my item title",
                type: "file",
                can_user_manage: true
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-permissions]")).toBeTruthy();
    });

    it(`Given user is docman reader
        When we display the menu
        Then the permission link should not be available`, () => {
        const wrapper = dropdown_menu_factory({
            item: {
                id: 4,
                title: "my item title",
                type: "file",
                can_user_manage: false
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-permissions]")).toBeFalsy();
    });

    it(`Given item type is empty
        When we display the menu
        Then the approval table link should not be available`, () => {
        const wrapper = dropdown_menu_factory({
            item: {
                id: 4,
                title: "my item title",
                type: "empty",
                can_user_manage: false
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-approval-tables]")).toBeFalsy();
    });

    it(`Given item type is a file
        When we display the menu
        Then the approval table link should be available`, () => {
        const wrapper = dropdown_menu_factory({
            item: {
                id: 4,
                title: "my item title",
                type: "file",
                can_user_manage: false
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-approval-tables]")).toBeTruthy();
    });
});
