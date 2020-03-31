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
import VueRouter from "vue-router";
import localVue from "../../helpers/local-vue.js";

import { createStoreMock } from "../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import ChildFolder from "./ChildFolder.vue";

describe("ChildFolder", () => {
    let factory, store, router;
    beforeEach(() => {
        store = createStoreMock({});

        router = new VueRouter({
            routes: [
                {
                    path: "/folder/10",
                    name: "folder",
                },
                {
                    path: "/preview/20",
                    name: "preview",
                },
            ],
        });

        factory = (props = {}) => {
            return shallowMount(ChildFolder, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
                router,
            });
        };
    });

    it(`Given preview_id parameter is not set
        Then route only deals with tree view
        And we call loadFolder to load current folder content,
        and we remove quick look properties to be sure to have initial quick look state`, () => {
        router.push({
            name: "folder",
            params: {
                item_id: 10,
            },
        });

        factory();
        expect(store.dispatch).toHaveBeenCalledWith("loadFolder", 10);
        expect(store.dispatch).toHaveBeenCalledWith("removeQuickLook");
    });

    it(`Given a preview id and the current folder is not defined
        Then we should load folder and open document quick look`, async () => {
        router.push({
            name: "preview",
            params: {
                preview_item_id: 20,
            },
        });
        const wrapper = factory();

        store.state.currently_previewed_item = {
            id: 20,
            parent_id: 10,
        };

        expect(store.dispatch).toHaveBeenCalledWith("toggleQuickLook", 20);

        await wrapper.vm.$nextTick().then(() => {});
        expect(store.dispatch).toHaveBeenCalledWith("loadFolder", 10);
    });

    it(`Given a preview id and the current folder is defined
        Then only open document quick look`, () => {
        store.state.current_folder = { id: 10, title: "current folder" };

        router.push({
            name: "preview",
            params: {
                preview_item_id: 20,
            },
        });
        factory();

        expect(store.dispatch).toHaveBeenCalledWith("toggleQuickLook", 20);
        expect(store.dispatch).not.toHaveBeenCalledWith("loadFolder");
    });

    it(`Given route is updated to "folder" and given folder has changed (=> redirection into a folder)
        Then the folder is loaded`, async () => {
        store.state.current_folder = { id: 10, title: "current folder" };

        router.push({
            name: "preview",
            params: {
                preview_item_id: 10,
            },
        });
        const wrapper = factory();

        router.push({
            name: "folder",
            params: {
                item_id: 20,
            },
        });
        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("removeQuickLook");
        expect(store.dispatch).toHaveBeenCalledWith("loadFolder", 20);
    });

    it(`Given route is updated to "folder" and given folder is the same (=> close preview)
        Then we only close quick look`, async () => {
        store.state.current_folder = { id: 10, title: "current folder" };

        router.push({
            name: "preview",
            params: {
                preview_item_id: 20,
            },
        });
        const wrapper = factory();

        router.push({
            name: "folder",
            params: {
                preview_item_id: 20,
            },
        });
        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("removeQuickLook");
        expect(store.dispatch).not.toHaveBeenCalledWith("loadFolder");
    });
});
