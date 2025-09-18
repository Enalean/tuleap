/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { RouterViewStub, shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import DocumentBreadcrumb from "./Breadcrumb/DocumentBreadcrumb.vue";
import PermissionError from "./Folder/Error/PermissionError.vue";
import ItemPermissionError from "./Folder/Error/ItemPermissionError.vue";
import LoadingError from "./Folder/Error/LoadingError.vue";
import SwitchToOldUI from "./Folder/SwitchToOldUI.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-test";
import { CAN_USER_SWITCH_TO_OLD_UI } from "../configuration-keys";

describe("App", () => {
    let factory: () => VueWrapper<InstanceType<typeof App>>;
    const set_root_title = vi.fn();
    let has_folder_permission_error: boolean,
        has_folder_loading_error: boolean,
        has_document_permission_error: boolean,
        has_document_loading_error: boolean,
        has_document_lock_error: boolean,
        can_user_switch_to_old_ui: boolean;

    beforeEach(() => {
        const default_prop = {
            csrf_token: "challenge_value",
            csrf_token_name: "challenge_name",
        };

        has_folder_permission_error = false;
        has_folder_loading_error = false;
        has_document_permission_error = false;
        has_document_loading_error = false;
        has_document_lock_error = false;
        can_user_switch_to_old_ui = false;

        factory = (): VueWrapper<InstanceType<typeof App>> => {
            return shallowMount(App, {
                props: default_prop,
                global: {
                    ...getGlobalTestOptions({
                        modules: {
                            error: {
                                state: {
                                    has_folder_permission_error,
                                    has_folder_loading_error,
                                    has_document_permission_error,
                                    has_document_loading_error,
                                    has_document_lock_error,
                                },
                                namespaced: true,
                            },
                        },
                        getters: {
                            is_uploading: () => false,
                        },
                        mutations: {
                            setRootTitle: set_root_title,
                        },
                    }),
                    stubs: {
                        RouterView: RouterViewStub,
                    },
                    provide: {
                        [CAN_USER_SWITCH_TO_OLD_UI.valueOf()]: can_user_switch_to_old_ui,
                    },
                },
            });
        };
    });

    it(`Displays folder permission error if user can't access to a folder`, () => {
        has_folder_permission_error = true;
        const wrapper = factory();

        expect(wrapper.findComponent(DocumentBreadcrumb).exists()).toBeFalsy();
        expect(wrapper.findComponent(PermissionError).exists()).toBeTruthy();
        expect(wrapper.findComponent(ItemPermissionError).exists()).toBeFalsy();
        expect(wrapper.findComponent(LoadingError).exists()).toBeFalsy();
    });

    it(`Displays loading error if folder fails to load itself`, () => {
        has_folder_loading_error = true;
        const wrapper = factory();

        expect(wrapper.findComponent(DocumentBreadcrumb).exists()).toBeTruthy();
        expect(wrapper.findComponent(PermissionError).exists()).toBeFalsy();
        expect(wrapper.findComponent(ItemPermissionError).exists()).toBeFalsy();
        expect(wrapper.findComponent(LoadingError).exists()).toBeTruthy();
    });

    it(`Displays item permission error if user can't access to a document`, () => {
        has_document_permission_error = true;
        const wrapper = factory();

        expect(wrapper.findComponent(DocumentBreadcrumb).exists()).toBeTruthy();
        expect(wrapper.findComponent(PermissionError).exists()).toBeFalsy();
        expect(wrapper.findComponent(ItemPermissionError).exists()).toBeTruthy();
        expect(wrapper.findComponent(LoadingError).exists()).toBeFalsy();
    });

    it(`Displays item loading error if document load fails`, () => {
        has_document_loading_error = true;
        const wrapper = factory();

        expect(wrapper.findComponent(DocumentBreadcrumb).exists()).toBeTruthy();
        expect(wrapper.findComponent(PermissionError).exists()).toBeFalsy();
        expect(wrapper.findComponent(ItemPermissionError).exists()).toBeFalsy();
        expect(wrapper.findComponent(LoadingError).exists()).toBeTruthy();
    });

    it(`Displays item loading error if document is locked`, () => {
        has_document_lock_error = true;
        const wrapper = factory();

        expect(wrapper.findComponent(DocumentBreadcrumb).exists()).toBeTruthy();
        expect(wrapper.findComponent(PermissionError).exists()).toBeFalsy();
        expect(wrapper.findComponent(ItemPermissionError).exists()).toBeFalsy();
        expect(wrapper.findComponent(LoadingError).exists()).toBeTruthy();
    });

    it(`Does not display link back to old UI if user is not allowed to`, () => {
        const wrapper = factory();

        expect(wrapper.findComponent(SwitchToOldUI).exists()).toBeFalsy();
    });

    it(`Displays a switch back link if user is allowed to`, () => {
        can_user_switch_to_old_ui = true;
        const wrapper = factory();

        expect(wrapper.vm.can_user_switch_to_old_ui).toBeTruthy();
    });
});
