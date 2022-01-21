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
import UnlockItem from "./UnlockItem.vue";
import type { ItemFile } from "../../../type";
import type { LockInfo } from "../../../type";
import localVue from "../../../helpers/local-vue";

describe("UnlockItem", () => {
    const store = {
        dispatch: jest.fn(),
    };

    function createWrapper(item: ItemFile): Wrapper<UnlockItem> {
        return shallowMount(UnlockItem, {
            localVue,
            propsData: { item },
            mocks: {
                $store: store,
            },
        });
    }

    it(`Given document is not locked
        When we display the dropdown
        Then I should not be able to unlock it`, () => {
        const item = {
            id: 1,
            user_can_write: true,
            lock_info: null,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
    });

    it(`Given an other user has locked a document, and given I don't have admin permission
        When we display the dropdown
        Then I should not be able to unlock it`, () => {
        const item = {
            id: 1,
            user_can_write: false,
            lock_info: {
                lock_by: {},
            } as LockInfo,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
    });

    it(`Given user can write
        When we display the dropdown
        Then I should able to unlock any item locked`, () => {
        const item = {
            id: 1,
            user_can_write: true,
            lock_info: {
                lock_by: {},
            } as LockInfo,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()
        ).toBeTruthy();
    });

    it(`Given item is a file and given user can write
        Then unlock option should be displayed`, () => {
        const item = {
            id: 1,
            user_can_write: true,
            lock_info: {
                lock_by: {},
            } as LockInfo,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()
        ).toBeTruthy();
    });

    it(`unlock document on click`, () => {
        const item = {
            id: 1,
            user_can_write: true,
            lock_info: {
                lock_by: {},
            } as LockInfo,
        } as ItemFile;
        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-dropdown-menu-unlock-item]").trigger("click");

        expect(store.dispatch).toHaveBeenCalledWith("lock/unlockDocument", item);
    });
});
