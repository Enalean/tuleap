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
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import LockItem from "./LockItem.vue";

describe("LockItem", () => {
    let lock_factory, store;
    beforeEach(() => {
        const store_options = {};

        store = createStoreMock(store_options);

        lock_factory = (props = {}) => {
            return shallowMount(LockItem, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given document is locked
        When we display the dropdown
        Then lock option is not available`, () => {
        const wrapper = lock_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
                lock_info: {
                    id: 101,
                },
            },
        });

        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeFalsy();
    });

    it(`Given document is not locked and given user has write permission
        When we display the dropdown
        Then he should be able to lock document`, () => {
        const wrapper = lock_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
                lock_info: null,
            },
        });

        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
    });

    it(`Given document is not locked and given user has only read permission
        When we display the dropdown
        Then lock option is not available`, () => {
        const wrapper = lock_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: false,
                lock_info: null,
            },
        });

        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeFalsy();
    });

    it(`Given item is a file and given user can write
        Then lock option should be displayed`, () => {
        const wrapper = lock_factory({
            item: {
                id: 1,
                title: "my file",
                type: "file",
                user_can_write: true,
                lock_info: null,
            },
        });

        expect(wrapper.contains("[data-test=document-dropdown-menu-lock-item]")).toBeTruthy();
    });

    it(`Lock document on click`, () => {
        const item = {
            id: 1,
            title: "my file",
            type: "file",
            user_can_write: true,
            lock_info: null,
        };
        const wrapper = lock_factory({
            item,
        });

        wrapper.get("[data-test=document-dropdown-menu-lock-item]").trigger("click");

        expect(store.dispatch).toHaveBeenCalledWith("lockDocument", item);
    });
});
