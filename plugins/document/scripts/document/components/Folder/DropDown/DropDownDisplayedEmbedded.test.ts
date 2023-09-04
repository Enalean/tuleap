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
import DropDownDisplayedEmbedded from "./DropDownDisplayedEmbedded.vue";
import type { Item, RootState } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("DropDownDisplayedEmbedded", () => {
    function createWrapper(
        user_can_write: boolean,
        can_user_manage: boolean,
        parent_id: number,
        forbid_writers_to_update: boolean,
        forbid_writers_to_delete: boolean,
    ): VueWrapper<InstanceType<typeof DropDownDisplayedEmbedded>> {
        return shallowMount(DropDownDisplayedEmbedded, {
            props: { isInFolderEmptyState: false },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        currently_previewed_item: {
                            id: 42,
                            title: "embedded title",
                            user_can_write,
                            can_user_manage,
                            parent_id,
                        } as Item,
                    } as RootState,
                    modules: {
                        configuration: {
                            state: {
                                forbid_writers_to_update,
                                forbid_writers_to_delete,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it(`Given user can write item
        Then he can update its properties and delete it`, () => {
        const wrapper = createWrapper(true, false, 102, false, false);
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists(),
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-dropdown-separator]").exists()).toBeTruthy();
    });

    it(`Given user can write item, and given folder is root folder
        Then he can update its properties but he can not delete ir`, () => {
        const wrapper = createWrapper(true, false, 0, false, false);

        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists(),
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-separator]").exists()).toBeTruthy();
    });

    it(`Given user has read permission on item
        Then he can't manage document`, () => {
        const wrapper = createWrapper(false, false, 102, false, false);
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-separator]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to update properties
        And user is writer
        When we display the menu
        Then it does not display update properties entry`, () => {
        const wrapper = createWrapper(true, false, 3, true, false);

        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to update properties
        And user is manager
        When we display the menu
        Then it displays update properties entry`, () => {
        const wrapper = createWrapper(true, true, 3, true, false);

        expect(wrapper.find("[data-test=document-update-properties]").exists()).toBeTruthy();
    });

    it(`Given writers are not allowed to delete
        And user is writer
        When we display the menu
        Then it does not display delete entry`, () => {
        const wrapper = createWrapper(true, false, 3, true, true);

        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to delete
        And user is manager
        When we display the menu
        Then it displays delete entry`, () => {
        const wrapper = createWrapper(true, true, 3, true, true);

        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeTruthy();
    });
});
