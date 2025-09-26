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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import LockItem from "./LockItem.vue";
import type { ItemFile, LockInfo } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { DocumentLock } from "../../../../helpers/lock/document-lock";

describe("LockItem", () => {
    let document_lock: DocumentLock;

    beforeEach(() => {
        document_lock = {
            lockDocument: vi.fn(),
        };
    });

    function createWrapper(item: ItemFile): VueWrapper<InstanceType<typeof LockItem>> {
        return shallowMount(LockItem, {
            props: { item, document_lock },
            global: getGlobalTestOptions({}),
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

        expect(document_lock.lockDocument).toHaveBeenCalledWith(expect.any(Object), item);
    });
});
