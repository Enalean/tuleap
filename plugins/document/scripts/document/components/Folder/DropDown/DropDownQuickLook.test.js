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
import DropDownQuickLook from "./DropDownQuickLook.vue";

describe("DropDownQuickLook", () => {
    let factory, store;
    beforeEach(() => {
        store = createStoreMock({});

        store.getters.is_item_a_folder = () => false;

        factory = (props = {}) => {
            return shallowMount(DropDownQuickLook, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given item is not a folder and user can write
        When we display the menu
        Then the drop down does not display New folder/document entries`, () => {
        const wrapper = factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            },
        });

        expect(wrapper.contains("[data-test=dropdown-menu-folder-creation]")).toBeFalsy();
        expect(wrapper.contains("[data-test=dropdown-menu-file-creation]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
    });

    it(`Given item is not a folder and user can read
        When we display the menu
        Then does not display lock informations`, () => {
        const wrapper = factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: false,
            },
        });

        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-dropdown-menu-unlock-item]")).toBeFalsy();
    });

    describe("Given item is a folder", () => {
        let wrapper;

        const item = {
            id: 1,
            title: "my folder",
            type: "folder",
            user_can_write: true,
        };

        beforeEach(() => {
            store.getters.is_item_a_folder = () => true;
        });

        it(`When the dropdown is open
            Then user should not have the "create new version" option`, () => {
            wrapper = factory({ item });

            expect(
                wrapper.contains("[data-test=document-quicklook-action-button-new-item]")
            ).toBeTruthy();
            expect(
                wrapper.contains("[data-test=document-quicklook-action-button-new-version]")
            ).toBeFalsy();
            expect(
                wrapper.contains("[data-test=document-dropdown-menu-update-properties]")
            ).toBeTruthy();
            expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeFalsy();
        });

        it(`When user cannot write and the menu is displayed
            Then the user should not be able to create folder/documents`, () => {
            item.user_can_write = false;

            wrapper = factory({ item });

            expect(wrapper.contains("[data-test=dropdown-menu-folder-creation]")).toBeFalsy();
            expect(wrapper.contains("[data-test=dropdown-menu-file-creation]")).toBeFalsy();
            expect(
                wrapper.contains("[data-test=document-dropdown-menu-update-properties]")
            ).toBeFalsy();
            expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeFalsy();
        });
    });
});
