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
import ChildFolder from "./ChildFolder.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { createRouter, createWebHistory } from "vue-router";
import { routes } from "../../router/router";
import type { Folder, RootState } from "../../type";
import type { Action } from "vuex";

vi.useFakeTimers();

vi.mock("@tuleap/autocomplete-for-select2", () => {
    return { autocomplete_users_for_select2: vi.fn() };
});

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
});

describe("ChildFolder", () => {
    let state: RootState;
    let load_folder: Action<RootState, RootState>,
        remove_quick_look: Action<RootState, RootState>,
        toggle_quick_look: Action<RootState, RootState>;
    let item_id: number;
    let preview_item_id: number;

    const factory = (): VueWrapper<ChildFolder> => {
        const config = getGlobalTestOptions({
            state,
            actions: {
                loadFolder: load_folder,
                removeQuickLook: remove_quick_look,
                toggleQuickLook: toggle_quick_look,
            },
        });
        if (config === undefined || config.plugins === undefined) {
            throw Error("Failed to get test config");
        }
        return shallowMount(ChildFolder, {
            props: { item_id, preview_item_id },
            global: {
                plugins: [...config.plugins, router],
            },
        });
    };

    beforeEach(() => {
        state = {} as unknown as RootState;
        load_folder = vi.fn();
        remove_quick_look = vi.fn();
        toggle_quick_look = vi.fn();
        item_id = 0;
        preview_item_id = 0;
    });

    it(`Given preview_id parameter is not set
        Then route only deals with tree view
        And we call loadFolder to load current folder content,
        and we remove quick look properties to be sure to have initial quick look state`, async () => {
        item_id = 10;
        await router.push({
            name: "folder",
            params: { item_id },
        });

        factory();
        expect(load_folder).toHaveBeenCalledWith(expect.anything(), 10);
        expect(remove_quick_look).toHaveBeenCalled();
    });

    it(`Given a preview id and the current folder is not defined
        Then we should load folder and open document quick look`, async () => {
        preview_item_id = 20;
        await router.push({
            name: "preview",
            params: { preview_item_id },
        });

        state.currently_previewed_item = {
            id: 20,
            parent_id: 10,
        } as Folder;

        factory();

        await vi.runOnlyPendingTimersAsync();
        expect(load_folder).toHaveBeenCalledWith(expect.anything(), 10);
    });

    it(`Given a preview id and the current folder is defined
        Then only open document quick look`, async () => {
        state.current_folder = { id: 10, title: "current folder" } as Folder;
        preview_item_id = 20;

        await router.push({
            name: "preview",
            params: { preview_item_id },
        });
        factory();

        expect(toggle_quick_look).toHaveBeenCalledWith(expect.anything(), 20);
        expect(load_folder).not.toHaveBeenCalled();
    });

    it(`Given route is updated to "folder" and given folder has changed (=> redirection into a folder)
        Then the folder is loaded`, async () => {
        state.current_folder = { id: 10, title: "current folder" } as Folder;
        preview_item_id = 20;
        await router.push({
            name: "preview",
            params: { preview_item_id },
        });
        const wrapper = factory();
        item_id = 20;
        await wrapper.setProps({ item_id });
        await router.push({
            name: "folder",
            params: { item_id },
        });
        expect(remove_quick_look).toHaveBeenCalled();
        expect(load_folder).toHaveBeenCalledWith(expect.anything(), 20);
    });
});
