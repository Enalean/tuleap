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
import DropdownMenuCurrentFolder from "./DropdownMenuCurrentFolder.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

describe("DropdownMenuCurrentFolder", () => {
    let dropdown_factory, state, store, store_options;
    beforeEach(() => {
        state = {
            current_folder: {
                id: 42,
                title: "current folder title"
            }
        };
        store_options = {
            state
        };
        store = createStoreMock(store_options);

        dropdown_factory = () => {
            return shallowMount(DropdownMenuCurrentFolder, {
                localVue,
                mocks: { $store: store }
            });
        };
    });

    it(`Given user does not have write permission on current folder
        When we display the dropdown
        Then user should not be able to create folders inside`, () => {
        store.state.current_folder.user_can_write = false;

        const wrapper = dropdown_factory();

        expect(wrapper.contains("[data-test=new-folder-creation-button]")).toBeFalsy();
    });

    it(`Given user has write permission on current folder
        When we display the dropdown
        Then user should be able to create folders inside`, () => {
        store.state.current_folder.user_can_write = true;

        const wrapper = dropdown_factory();

        expect(wrapper.contains("[data-test=new-folder-creation-button]")).toBeTruthy();
    });
});
