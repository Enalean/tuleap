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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import UnlockItem from "./UnlockItem.vue";
import type { ItemFile, LockInfo } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("UnlockItem", () => {
    let unlock_document: jest.Mock;

    beforeEach(() => {
        unlock_document = jest.fn();
    });

    function createWrapper(item: ItemFile): VueWrapper<InstanceType<typeof UnlockItem>> {
        return shallowMount(UnlockItem, {
            props: { item },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        lock: {
                            actions: { unlockDocument: unlock_document },
                            namespaced: true,
                        },
                    },
                }),
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

        expect(unlock_document).toHaveBeenCalledWith(expect.any(Object), item);
    });
});
