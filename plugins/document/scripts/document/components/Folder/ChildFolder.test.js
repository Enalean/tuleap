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

import { shallowMount } from "@vue/test-utils";
import ChildFolder from "./ChildFolder.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { createRouter, createWebHistory } from "vue-router";

import { routes } from "../../router/router";
import { nextTick } from "vue";

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
});

describe("ChildFolder", () => {
    let state, load_folder, remove_quick_look, toggle_quick_look;

    const factory = () => {
        const config = getGlobalTestOptions({
            state,
            actions: {
                loadFolder: load_folder,
                removeQuickLook: remove_quick_look,
                toggleQuickLook: toggle_quick_look,
            },
        });
        return shallowMount(ChildFolder, {
            global: {
                plugins: [...config.plugins, router],
            },
        });
    };

    beforeEach(() => {
        state = {};
        load_folder = jest.fn();
        remove_quick_look = jest.fn();
        toggle_quick_look = jest.fn();
    });

    it(`Given preview_id parameter is not set
        Then route only deals with tree view
        And we call loadFolder to load current folder content,
        and we remove quick look properties to be sure to have initial quick look state`, async () => {
        await router.push({
            name: "folder",
            params: {
                item_id: 10,
            },
        });

        factory();
        expect(load_folder).toHaveBeenCalledWith(expect.anything(), 10);
        expect(remove_quick_look).toHaveBeenCalled();
    });

    it(`Given a preview id and the current folder is not defined
        Then we should load folder and open document quick look`, async () => {
        await router.push({
            name: "preview",
            params: {
                preview_item_id: 20,
            },
        });

        state.currently_previewed_item = {
            id: 20,
            parent_id: 10,
        };

        factory();

        await nextTick();
        await nextTick();
        expect(load_folder).toHaveBeenCalledWith(expect.anything(), 10);
    });

    it(`Given a preview id and the current folder is defined
        Then only open document quick look`, async () => {
        state.current_folder = { id: 10, title: "current folder" };

        await router.push({
            name: "preview",
            params: {
                preview_item_id: 20,
            },
        });
        factory();

        expect(toggle_quick_look).toHaveBeenCalledWith(expect.anything(), 20);
        expect(load_folder).not.toHaveBeenCalled();
    });

    it(`Given route is updated to "folder" and given folder has changed (=> redirection into a folder)
        Then the folder is loaded`, async () => {
        state.current_folder = { id: 10, title: "current folder" };
        await router.push({
            name: "preview",
            params: {
                preview_item_id: 10,
            },
        });
        factory();
        await router.push({
            name: "folder",
            params: {
                item_id: 20,
            },
        });
        expect(remove_quick_look).toHaveBeenCalled();
        expect(load_folder).toHaveBeenCalledWith(expect.anything(), 20);
    });
});
