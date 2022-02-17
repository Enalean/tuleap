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
import localVue from "../../../helpers/local-vue";
import DropDownDisplayedEmbedded from "./DropDownDisplayedEmbedded.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { Item, State } from "../../../type";

describe("DropDownDisplayedEmbedded", () => {
    function createWrapper(
        user_can_write: boolean,
        parent_id: number
    ): Wrapper<DropDownDisplayedEmbedded> {
        const state = {
            currently_previewed_item: {
                id: 42,
                title: "embedded title",
                user_can_write,
                parent_id,
            } as Item,
        } as State;
        const store_options = {
            state,
        };
        const store = createStoreMock(store_options);
        return shallowMount(DropDownDisplayedEmbedded, {
            localVue,
            mocks: { $store: store },
            propsData: { isInFolderEmptyState: false },
        });
    }

    it(`Given user can write item
        Then he can update its properties and delete it`, () => {
        const wrapper = createWrapper(true, 102);
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-dropdown-separator]").exists()).toBeTruthy();
    });

    it(`Given user can write item, and given folder is root folder
        Then he can update its properties but he can not delete ir`, () => {
        const wrapper = createWrapper(true, 0);

        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-separator]").exists()).toBeTruthy();
    });

    it(`Given user has read permission on item
        Then he can't manage document`, () => {
        const wrapper = createWrapper(false, 102);
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-separator]").exists()).toBeFalsy();
    });
});
