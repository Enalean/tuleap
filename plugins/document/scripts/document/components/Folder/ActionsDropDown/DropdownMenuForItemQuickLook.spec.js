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
import DropdownMenuForItemQuickLook from "./DropdownMenuForItemQuickLook.vue";
import {
    rewire as rewireEventBus,
    restore as restoreEventBus
} from "../../../helpers/event-bus.js";

describe("DropdownMenuForItemQuickLook", () => {
    let dropdown_quicklook_menu_factory, store, event_bus;
    beforeEach(() => {
        store = createStoreMock({});

        store.getters.is_item_a_folder = () => false;

        dropdown_quicklook_menu_factory = (props = {}) => {
            return shallowMount(DropdownMenuForItemQuickLook, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };

        event_bus = jasmine.createSpyObj("event_bus", ["$emit"]);
        rewireEventBus(event_bus);
    });

    afterEach(() => {
        restoreEventBus();
    });

    it(`Given item is not a folder and user can write
        When we display the menu
        Then the drop down does not display New folder/document entries`, () => {
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true
            }
        });

        expect(wrapper.contains("[data-test=dropdown-menu-folder-creation]")).toBeFalsy();
        expect(wrapper.contains("[data-test=dropdown-menu-file-creation]")).toBeFalsy();
    });

    it(`Given item is a folder and user can write
        When we display the menu
        Then the drop down enable user to create folder/document`, () => {
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my folder",
                type: "folder",
                user_can_write: true
            }
        });

        store.getters.is_item_a_folder = () => true;

        expect(wrapper.contains("[data-test=dropdown-menu-folder-creation]")).toBeTruthy();
        expect(wrapper.contains("[data-test=dropdown-menu-file-creation]")).toBeTruthy();
    });

    it(`Given item is a a file
        When the dropdown is open
        Then the dropdown should allow user to create a new version of the item`, () => {
        const wrapper = dropdown_quicklook_menu_factory({
            item: {
                id: 1,
                title: "my file",
                type: "file",
                user_can_write: true
            }
        });

        expect(
            wrapper.contains("[data-test=docman-dropdown-create-new-version-button]")
        ).toBeTruthy();
    });

    describe("Given item is a folder", () => {
        let wrapper;

        const item = {
            id: 1,
            title: "my folder",
            type: "folder",
            user_can_write: true
        };

        beforeEach(() => {
            store.getters.is_item_a_folder = () => true;
        });

        it(`When the dropdown is open
            Then user should not have the "create new version" option`, () => {
            wrapper = dropdown_quicklook_menu_factory({ item });

            expect(
                wrapper.contains("[data-test=docman-dropdown-create-new-version-button]")
            ).toBeFalsy();
        });

        it(`When user clicks on [create new folder]
            Then it should open a modal`, () => {
            wrapper = dropdown_quicklook_menu_factory({ item });

            wrapper.find("[data-test=dropdown-menu-folder-creation]").trigger("click");

            expect(event_bus.$emit).toHaveBeenCalledWith(
                "show-new-folder-modal",
                jasmine.any(Object)
            );
        });

        it(`When user clicks on [create new document]
            Then it should open a modal`, () => {
            wrapper = dropdown_quicklook_menu_factory({ item });

            wrapper.find("[data-test=dropdown-menu-file-creation]").trigger("click");

            expect(event_bus.$emit).toHaveBeenCalledWith(
                "show-new-document-modal",
                jasmine.any(Object)
            );
        });
        it(`When user cannot write and the menu is displayed
            Then the user should not be able to create folder/documents`, () => {
            item.user_can_write = false;

            wrapper = dropdown_quicklook_menu_factory({ item });

            expect(wrapper.contains("[data-test=dropdown-menu-folder-creation]")).toBeFalsy();
            expect(wrapper.contains("[data-test=dropdown-menu-file-creation]")).toBeFalsy();
        });
    });
});
