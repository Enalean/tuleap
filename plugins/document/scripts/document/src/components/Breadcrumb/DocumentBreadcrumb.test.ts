/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import DocumentBreadcrumb from "./DocumentBreadcrumb.vue";
import type { VueWrapper } from "@vue/test-utils";
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import type { Embedded, Folder, Item, RootState } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { ConfigurationState } from "../../store/configuration";

describe("DocumentBreadcrumb", () => {
    function createWrapper(
        user_is_admin: boolean,
        current_folder_ascendant_hierarchy: Array<Folder>,
        is_loading_ascendant_hierarchy: boolean,
        currently_previewed_item: null | Item,
        project_icon = "",
    ): VueWrapper<InstanceType<typeof DocumentBreadcrumb>> {
        return shallowMount(DocumentBreadcrumb, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                user_is_admin,
                                project_icon,
                                project_url: " /project",
                                privacy: "private",
                                project_flags: [],
                                project_id: "101",
                                project_public_name: "My project",
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                    state: {
                        current_folder_ascendant_hierarchy,
                        is_loading_ascendant_hierarchy,
                        current_folder: { id: 1, title: "My first folder", parent_id: 0 } as Folder,
                        currently_previewed_item,
                    } as RootState,
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });
    }

    it(`Given user is docman administrator
        When we display the breadcrumb
        Then user should have an administration link`, () => {
        const wrapper = createWrapper(true, [], false, {} as Item);
        expect(wrapper.find("[data-test=breadcrumb-administrator-link]").exists()).toBeTruthy();
    });

    it(`Given user is regular user
        When we display the breadcrumb
        Then he should not have administrator link`, () => {
        const wrapper = createWrapper(false, [], false, {} as Item);
        expect(wrapper.find("[data-test=breadcrumb-administrator-link]").exists()).toBeFalsy();
    });
    it(`displays the project icon`, () => {
        const wrapper = createWrapper(false, [], false, {} as Item, "🏰");
        expect(wrapper.find("[data-test=project-icon]").exists()).toBe(true);
    });
    it(`Given ascendant hierarchy has more than 5 ascendants
        When we display the breadcrumb
        Then an ellipsis is displayed so breadcrumb won't break page display`, () => {
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder" } as Folder,
            { id: 2, title: "My second folder" } as Folder,
            { id: 3, title: "My third folder" } as Folder,
            { id: 4, title: "My fourth folder" } as Folder,
            { id: 5, title: "My fifth folder" } as Folder,
            { id: 6, title: "My sixth folder" } as Folder,
            { id: 7, title: "My seventh folder" } as Folder,
        ];

        const wrapper = createWrapper(false, current_folder_ascendant_hierarchy, false, {} as Item);
        expect(wrapper.find("[data-test=breadcrumb-ellipsis]").exists()).toBeTruthy();
    });

    it(`Given ascendant hierarchy has more than 5 ascendants and given we're still loading the ascendent hierarchy
        When we display the breadcrumb
        Then ellipsis is not displayed`, () => {
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder" } as Folder,
            { id: 2, title: "My second folder" } as Folder,
            { id: 3, title: "My third folder" } as Folder,
            { id: 4, title: "My fourth folder" } as Folder,
            { id: 5, title: "My fifth folder" } as Folder,
            { id: 6, title: "My sixth folder" } as Folder,
            { id: 7, title: "My seventh folder" } as Folder,
        ];

        const wrapper = createWrapper(false, current_folder_ascendant_hierarchy, true, {} as Item);

        expect(wrapper.find("[data-test=breadcrumb-ellipsis]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-breadcrumb-skeleton]").exists()).toBeTruthy();
    });

    it(`Given a list of folders which are in different hierarchy level
        When we display the breadcrumb
        Then folders which are in the root folder (parent_id === 0) are removed`, () => {
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder", parent_id: 0 } as Folder,
            { id: 2, title: "My second folder", parent_id: 0 } as Folder,
            { id: 3, title: "My third folder", parent_id: 1 } as Folder,
            { id: 4, title: "My fourth folder", parent_id: 2 } as Folder,
            { id: 5, title: "My fifth folder", parent_id: 2 } as Folder,
        ];

        const wrapper = createWrapper(false, current_folder_ascendant_hierarchy, false, {} as Item);

        expect(wrapper.find("[data-test=breadcrumb-ellipsis]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-breadcrumb-skeleton]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=breadcrumb-element-0]").exists()).toBeFalsy();
    });

    it(`Given a list of folders and the current document which is displayed
    When we display the breadcrumb
    Then the breadcrumb display the current folder`, () => {
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder", parent_id: 0 } as Folder,
            { id: 2, title: "My second folder", parent_id: 0 } as Folder,
            { id: 3, title: "My third folder", parent_id: 1 } as Folder,
            { id: 4, title: "My fourth folder", parent_id: 2 } as Folder,
            { id: 5, title: "My fifth folder", parent_id: 2 } as Folder,
        ];
        const currently_previewed_item = {
            id: 6,
            title: "My embedded content",
            parent_id: 0,
        } as Embedded;

        const wrapper = createWrapper(
            false,
            current_folder_ascendant_hierarchy,
            false,
            currently_previewed_item,
        );

        expect(wrapper.find("[data-test=breadcrumb-current-document]").exists()).toBeTruthy();
    });
});
