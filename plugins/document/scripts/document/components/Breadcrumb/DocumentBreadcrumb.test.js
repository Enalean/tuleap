/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
import { createStoreMock } from "../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import DocumentBreadcrumb from "./DocumentBreadcrumb.vue";

describe("DocumentBreadcrumb", () => {
    let store_options, state, component_options, store;
    beforeEach(() => {
        state = {};
        store_options = {
            state,
        };
        store = createStoreMock(store_options);

        component_options = {
            localVue,
            propsData: {},
            mocks: { $store: store },
        };
    });
    it(`Given user is docman administrator
        When we display the breadcrumb
        Then user should have an administration link`, () => {
        store.state.is_user_administrator = true;
        store.state.current_folder_ascendant_hierarchy = [];

        const wrapper = shallowMount(DocumentBreadcrumb, component_options);
        expect(wrapper.contains("[data-test=breadcrumb-administrator-link]")).toBeTruthy();
    });

    it(`Given user is regular user
        When we display the breadcrumb
        Then he should not have administrator link`, () => {
        store.state.is_user_administrator = false;
        store.state.current_folder_ascendant_hierarchy = [];

        const wrapper = shallowMount(DocumentBreadcrumb, component_options);
        expect(wrapper.contains("[data-test=breadcrumb-administrator-link]")).toBeFalsy();
    });

    it(`Given ascendant hierarchy has more than 5 ascendants
        When we display the breadcrumb
        Then an ellipsis is displayed so breadcrumb won't break page display`, () => {
        store.state.is_user_administrator = false;
        store.state.is_loading_ascendant_hierarchy = false;
        store.state.current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder" },
            { id: 2, title: "My second folder" },
            { id: 3, title: "My third folder" },
            { id: 4, title: "My fourth folder" },
            { id: 5, title: "My fifth folder" },
            { id: 6, title: "My sixth folder" },
            { id: 7, title: "My seventh folder" },
        ];

        const wrapper = shallowMount(DocumentBreadcrumb, component_options);

        expect(wrapper.contains("[data-test=breadcrumb-ellipsis]")).toBeTruthy();
    });

    it(`Given ascendant hierarchy has more than 5 ascendants and given we're still loading the ascendent hierarchy
        When we display the breadcrumb
        Then ellipsis is not displayed`, () => {
        store.state.is_user_administrator = false;
        store.state.is_loading_ascendant_hierarchy = true;
        store.state.current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder" },
            { id: 2, title: "My second folder" },
            { id: 3, title: "My third folder" },
            { id: 4, title: "My fourth folder" },
            { id: 5, title: "My fifth folder" },
            { id: 6, title: "My sixth folder" },
            { id: 7, title: "My seventh folder" },
        ];

        const wrapper = shallowMount(DocumentBreadcrumb, component_options);

        expect(wrapper.contains("[data-test=breadcrumb-ellipsis]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-breadcrumb-skeleton]")).toBeTruthy();
    });

    it(`Given a list of folders which are in different hierarchy level
        When we display the breadcrumb
        Then folders which are in the root folder (parent_id === 0) are removed`, () => {
        store.state.is_user_administrator = false;
        store.state.is_loading_ascendant_hierarchy = false;
        store.state.current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder", parent_id: 0 },
            { id: 2, title: "My second folder", parent_id: 0 },
            { id: 3, title: "My third folder", parent_id: 1 },
            { id: 4, title: "My fourth folder", parent_id: 2 },
            { id: 5, title: "My fifth folder", parent_id: 2 },
        ];

        const wrapper = shallowMount(DocumentBreadcrumb, component_options);

        expect(wrapper.vm.current_folder_ascendant_hierarchy_to_display).not.toContain(
            expect.objectContaining({ parent_id: 0 })
        );
    });
    it(`Given a list of folders and not the current document
    When we display the breadcrumb
    Then the breadcrumb display the current folder`, () => {
        store.state.current_folder = { id: 1, title: "My first folder", parent_id: 0 };
        store.state.currently_previewed_item = null;
        store.state.is_user_administrator = false;
        store.state.is_loading_ascendant_hierarchy = false;
        store.state.current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder", parent_id: 0 },
            { id: 2, title: "My second folder", parent_id: 0 },
            { id: 3, title: "My third folder", parent_id: 1 },
            { id: 4, title: "My fourth folder", parent_id: 2 },
            { id: 5, title: "My fifth folder", parent_id: 2 },
        ];

        const wrapper = shallowMount(DocumentBreadcrumb, component_options);

        expect(wrapper.contains("[data-test=breadcrumb-current-document]")).toBeFalsy();
    });

    it(`Given a list of folders and the current document which is displayed
    When we display the breadcrumb
    Then the breadcrumb display the current folder`, () => {
        store.state.current_folder = { id: 1, title: "My first folder", parent_id: 0 };
        store.state.currently_previewed_item = {
            id: 6,
            title: "My embedded content",
            parent_id: 0,
        };
        store.state.is_user_administrator = false;
        store.state.is_loading_ascendant_hierarchy = false;
        store.state.current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder", parent_id: 0 },
            { id: 2, title: "My second folder", parent_id: 0 },
            { id: 3, title: "My third folder", parent_id: 1 },
            { id: 4, title: "My fourth folder", parent_id: 2 },
            { id: 5, title: "My fifth folder", parent_id: 2 },
        ];

        const wrapper = shallowMount(DocumentBreadcrumb, component_options);

        expect(wrapper.contains("[data-test=breadcrumb-current-document]")).toBeTruthy();
    });
});
