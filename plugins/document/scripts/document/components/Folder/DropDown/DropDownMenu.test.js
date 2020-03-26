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
import DropDownMenu from "./DropDownMenu.vue";

describe("DropDownMenu", () => {
    let dropdown_menu_factory, store;
    beforeEach(() => {
        const state = { max_files_dragndrop: 10, max_size_upload: 10000, project_id: 101 };
        const store_options = { state };
        store = createStoreMock(store_options);
        dropdown_menu_factory = (props = {}) => {
            return shallowMount(DropDownMenu, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
        store.getters.is_item_an_empty_document = () => false;
    });

    describe("Approval table menu option -", () => {
        it(`Given item type is empty
            When we display the menu
            Then the approval table link should not be available`, async () => {
            const wrapper = dropdown_menu_factory({
                item: {
                    id: 4,
                    title: "my item title",
                    type: "empty",
                    can_user_manage: false,
                },
            });
            store.getters.is_item_an_empty_document = () => true;
            await wrapper.vm.$nextTick();
            expect(wrapper.contains("[data-test=document-dropdown-approval-tables]")).toBeFalsy();
        });
        it(`Given item type is a file
            When we display the menu
            Then the approval table link should be available`, () => {
            const wrapper = dropdown_menu_factory({
                item: {
                    id: 4,
                    title: "my item title",
                    type: "file",
                    can_user_manage: false,
                },
            });
            expect(wrapper.contains("[data-test=document-dropdown-approval-tables]")).toBeTruthy();
        });
    });
});
