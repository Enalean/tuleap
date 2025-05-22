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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FolderHeaderAction from "./FolderHeaderAction.vue";
import type { Folder } from "../../type";

describe("FolderHeaderAction", () => {
    function createWrapper(item: Folder): VueWrapper<InstanceType<typeof FolderHeaderAction>> {
        return shallowMount(FolderHeaderAction, {
            props: { item },
        });
    }

    it(`Given user does not have write permission on current folder
        When we display the dropdown
        Then user should not be able to create folders inside`, () => {
        const item = {
            id: 42,
            title: "current folder title",
            user_can_write: false,
        } as Folder;

        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-item-action-new-button]").exists()).toBeFalsy();
    });

    it(`Given user has write permission on current folder
        When we display the dropdown
        Then user should be able to create folders inside`, () => {
        const item = {
            id: 42,
            title: "current folder title",
            user_can_write: true,
        } as Folder;

        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-item-action-new-button]").exists()).toBeTruthy();
    });
});
