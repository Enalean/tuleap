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
import LockItem from "./LockItem.vue";
import localVue from "../../../helpers/local-vue";
import type { ItemFile, LockInfo } from "../../../type";

describe("LockItem", () => {
    const store = {
        dispatch: jest.fn(),
    };

    function createWrapper(item: ItemFile): Wrapper<LockItem> {
        return shallowMount(LockItem, {
            localVue,
            propsData: { item },
            mocks: {
                $store: store,
            },
        });
    }

    it(`Given document is locked
        When we display the dropdown
        Then lock option is not available`, () => {
        const item = {
            id: 1,
            user_can_write: true,
            lock_info: {
                lock_by: {},
            } as LockInfo,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
    });

    it(`Given document is not locked and given user has write permission
        When we display the dropdown
        Then he should be able to lock document`, () => {
        const item = {
            id: 1,
            user_can_write: true,
            lock_info: null,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
    });

    it(`Given document is not locked and given user has only read permission
        When we display the dropdown
        Then lock option is not available`, () => {
        const item = {
            id: 1,
            user_can_write: false,
            lock_info: null,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
    });

    it(`Given item is a file and given user can write
        Then lock option should be displayed`, () => {
        const item = {
            user_can_write: true,
            lock_info: null,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
    });

    it(`Lock document on click`, () => {
        const item = {
            user_can_write: true,
            lock_info: null,
        } as ItemFile;
        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-dropdown-menu-lock-item]").trigger("click");

        expect(store.dispatch).toHaveBeenCalledWith("lock/lockDocument", item);
    });
});
