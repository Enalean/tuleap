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
import localVue from "../../helpers/local-vue.js";
import FolderHeaderAction from "./FolderHeaderAction.vue";
import { createStoreMock } from "../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";

describe("FolderHeaderAction", () => {
    let dropdown_factory, state, store, store_options;
    beforeEach(() => {
        state = {};
        store_options = {
            state,
        };
        store = createStoreMock(store_options);

        dropdown_factory = (props = {}) => {
            return shallowMount(FolderHeaderAction, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given user does not have write permission on current folder
        When we display the dropdown
        Then user should not be able to create folders inside`, () => {
        const item = {
            id: 42,
            title: "current folder title",
            user_can_write: false,
        };

        const wrapper = dropdown_factory({ item });

        expect(wrapper.contains("[data-test=document-item-action-new-button]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-item-action-details-button]")).toBeTruthy();
    });

    it(`Given user has write permission on current folder
        When we display the dropdown
        Then user should be able to create folders inside`, () => {
        const item = {
            id: 42,
            title: "current folder title",
            user_can_write: true,
        };

        const wrapper = dropdown_factory({ item });

        expect(wrapper.contains("[data-test=document-item-action-new-button]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-item-action-details-button]")).toBeFalsy();
    });
});
