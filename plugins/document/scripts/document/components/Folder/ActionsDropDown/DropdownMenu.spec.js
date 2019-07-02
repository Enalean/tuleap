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
import localVue from "../../../helpers/local-vue.js";
import DropdownMenu from "./DropdownMenu.vue";
import { restore, rewire$redirectToUrl } from "../../../helpers/location-helper";

describe("DropdownMenu", () => {
    let dropdown_menu_factory, store;
    beforeEach(() => {
        const state = {
            max_files_dragndrop: 10,
            max_size_upload: 10000,
            project_id: 101
        };

        const store_options = {
            state
        };

        store = createStoreMock(store_options);
        dropdown_menu_factory = (props = {}) => {
            return shallowMount(DropdownMenu, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };

        store.getters.is_item_an_empty_document = () => false;
    });
    afterEach(() => {
        restore();
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

    it(`Given an user who wants to update wiki properties
        When we display the menu
        Then the user should be redirected to the old UI`, () => {
        const redirect_to_url = jasmine.createSpy("redirectToUrl");
        rewire$redirectToUrl(redirect_to_url);

        const wrapper = dropdown_menu_factory({
            hideDetailsEntry: false,
            item: {
                id: 1,
                title: "my item title",
                type: "wiki",
                can_user_manage: true
            }
        });
        expect(wrapper.contains("[data-test=docman-dropdown-details]")).toBeTruthy();

        wrapper.find("[data-test=docman-dropdown-details]").trigger("click");

        expect(redirect_to_url).toHaveBeenCalled();
    });

    it(`Given an user who wants to update file properties
        When we display the menu
        Then the user should see the update properties modal`, () => {
        const wrapper = dropdown_menu_factory({
            hideDetailsEntry: false,
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                can_user_manage: true
            }
        });
        spyOn(document, "dispatchEvent");

        expect(wrapper.contains("[data-test=docman-dropdown-details]")).toBeTruthy();

        wrapper.find("[data-test=docman-dropdown-details]").trigger("click");

        expect(document.dispatchEvent).toHaveBeenCalledWith(
            new CustomEvent("show-update-item-metadata-modal")
        );
    });

    it(`Given user is docman writer and the current folder is not the root folder
        When we display the menu
        Then the delete button should be available`, () => {
        const wrapper = dropdown_menu_factory({
            hideDetailsEntry: false,
            item: {
                user_can_write: true,
                parent_id: 1789
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-delete]")).toBeTruthy();
    });

    it(`Given user is docman writer
        When we display the menu
        Then the delete button should be available`, () => {
        const wrapper = dropdown_menu_factory({
            hideDetailsEntry: false,
            item: {
                user_can_write: true,
                parent_id: 127
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-delete]")).toBeTruthy();
    });

    it(`Given user is NOT docman writer
        When we display the menu
        Then the delete button should NOT be available`, () => {
        const wrapper = dropdown_menu_factory({
            hideDetailsEntry: false,
            item: {
                user_can_write: false
            }
        });

        expect(wrapper.contains("[data-test=docman-dropdown-delete]")).toBeFalsy();
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

        store.getters.is_item_an_empty_document = () => true;

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

    describe("Lock/Unlock options", () => {
        it(`Given item type is a file not locked
        When we display the menu
        Then lock button should be displayed`, () => {
            const wrapper = dropdown_menu_factory({
                item: {
                    id: 4,
                    title: "my item title",
                    type: "file",
                    user_can_write: true,
                    lock_info: null
                }
            });

            expect(wrapper.contains("[data-test=dropdown-menu-lock-item]")).toBeTruthy();
        });

        it(`Given item type is locked
        When we display the menu
        Then lock button should be displayed`, () => {
            const wrapper = dropdown_menu_factory({
                item: {
                    id: 4,
                    title: "my item title",
                    type: "file",
                    user_can_write: true,
                    lock_info: {
                        owner_id: 102
                    }
                }
            });

            expect(wrapper.contains("[data-test=dropdown-menu-unlock-item]")).toBeTruthy();
        });

        it(`Given dropdown is in tree mode
        When we display the menu
        Then lock do not add an additional separator`, () => {
            const wrapper = dropdown_menu_factory({
                item: {
                    id: 4,
                    title: "my item title",
                    type: "file",
                    user_can_write: true,
                    lock_info: null
                },
                hideItemTitle: false
            });

            expect(wrapper.contains("[data-test=docman-lock-separator]")).toBeFalsy();
        });

        it(`Given dropdown is in overview mode
        When we display the menu
        Then lock add an additional separator`, () => {
            const wrapper = dropdown_menu_factory({
                item: {
                    id: 4,
                    title: "my item title",
                    type: "file",
                    user_can_write: true,
                    lock_info: null
                },
                hideItemTitle: true
            });

            expect(wrapper.contains("[data-test=docman-lock-separator]")).toBeTruthy();
        });
    });
});
