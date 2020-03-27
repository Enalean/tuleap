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

import FolderContent from "./FolderContent.vue";
import { createStoreMock } from "../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";

describe("FolderContent", () => {
    let factory, state, store, item;

    beforeEach(() => {
        state = {
            project_id: 101,
        };

        const store_options = {
            state,
        };

        store = createStoreMock(store_options);

        const router = new VueRouter({
            mode: "abstract",
            routes: [
                {
                    path: "/preview/42",
                    name: "preview",
                },
                {
                    path: "/folder/100",
                    name: "folder",
                },
                {
                    path: "/",
                    name: "root_folder",
                },
            ],
        });

        factory = () => {
            return shallowMount(FolderContent, {
                localVue,
                mocks: { $store: store },
                router,
            });
        };

        item = {
            id: 42,
            title: "my item title",
        };
    });

    it(`Should not display preview when component is rendered`, () => {
        const wrapper = factory({
            project_id: 101,
            currently_previewed_item: {},
            current_folder: {},
            folder_content: [item],
        });

        expect(wrapper.contains("[data-test=document-quick-look]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-folder-owner-information]")).toBeTruthy();
    });

    describe("toggleQuickLook", () => {
        it(`Given no item is currently previewed, then it directly displays quick look`, async () => {
            store.state.currently_previewed_item = null;
            store.state.current_folder = item;

            const wrapper = factory();
            const event = {
                details: { item },
            };

            expect(wrapper.vm.$route.path).toBe("/");

            await wrapper.vm.toggleQuickLook(event);

            expect(store.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", item);
            expect(store.commit).toHaveBeenCalledWith("toggleQuickLook", true);

            expect(wrapper.vm.$route.path).toBe("/preview/42");
        });

        it(`Given user toggle quicklook from an item to an other, the it displays the quick look of new item`, async () => {
            store.state.currently_previewed_item = {
                id: 105,
                title: "my previewed item",
            };

            store.state.current_folder = item;

            const wrapper = factory();
            const event = {
                details: { item },
            };
            await wrapper.vm.toggleQuickLook(event);

            expect(store.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", item);
            expect(store.commit).toHaveBeenCalledWith("toggleQuickLook", true);
            expect(wrapper.vm.$route.path).toBe("/preview/42");
        });

        it(`Given user toggle quick look, then it open quick look`, async () => {
            store.state.currently_previewed_item = item;

            store.state.current_folder = item;
            store.state.toggle_quick_look = false;

            const wrapper = factory();
            const event = {
                details: { item },
            };
            await wrapper.vm.toggleQuickLook(event);

            expect(store.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", item);
            expect(store.commit).toHaveBeenCalledWith("toggleQuickLook", true);
            expect(wrapper.vm.$route.path).toBe("/preview/42");
        });

        it(`Given user toggle quick look on a previewed item, then it closes quick look`, async () => {
            store.state.currently_previewed_item = item;

            store.state.current_folder = item;
            store.state.toggle_quick_look = true;

            const wrapper = factory();
            const event = {
                details: { item },
            };
            await wrapper.vm.toggleQuickLook(event);

            expect(store.commit).not.toHaveBeenCalledWith("updateCurrentlyPreviewedItem", item);
            expect(store.commit).toHaveBeenCalledWith("toggleQuickLook", false);
        });
    });

    describe("closeQuickLook", () => {
        it(`Given closed quick look is called on root_folder, then it calls the "root_folder" route`, () => {
            store.state.current_folder = {
                id: 25,
                parent_id: 0,
            };

            store.state.currently_previewed_item = item;

            const wrapper = factory();
            wrapper.vm.closeQuickLook();

            expect(wrapper.vm.$route.path).toBe("/");
        });

        it(`Given closed quick look is called on a subtree item, then it calls the parent folder route`, () => {
            store.state.current_folder = {
                id: 25,
                parent_id: 100,
            };

            store.state.currently_previewed_item = item;

            const wrapper = factory();
            wrapper.vm.closeQuickLook();

            expect(wrapper.vm.$route.path).toBe("/folder/100");
        });
    });
});
