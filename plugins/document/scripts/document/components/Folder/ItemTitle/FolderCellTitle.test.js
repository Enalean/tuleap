/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import Vue from "vue";
import VueRouter from "vue-router";
import { shallowMount } from "@vue/test-utils";
import FolderCellTitle from "./FolderCellTitle.vue";
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import * as abort_current_uploads from "../../../helpers/abort-current-uploads.js";

describe("FolderCellTitle", () => {
    let router, item, component_options, store_options, store;
    beforeEach(() => {
        router = new VueRouter({
            routes: [
                {
                    path: "/folder/42",
                    name: "folder",
                },
            ],
        });

        item = {
            id: 42,
            title: "my folder name",
        };

        store_options = {
            state: {
                files_uploads_list: [],
            },
        };
        store = createStoreMock(store_options);

        component_options = {
            localVue,
            router,
            propsData: {
                item,
            },
            mocks: { $store: store },
        };
    });
    it(`Given folder is open
        When we display the folder
        Then we should dynamically load its content`, async () => {
        item.is_expanded = true;
        const wrapper = shallowMount(FolderCellTitle, component_options);
        wrapper.setData({ is_closed: false });
        await Vue.nextTick();

        expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
        expect(store.dispatch).toHaveBeenCalledWith("getSubfolderContent", item.id);
        expect(store.commit).toHaveBeenCalledWith("unfoldFolderContent", item.id);
        await Vue.nextTick();
        const toggle = wrapper.get("[data-test=toggle]");
        expect(toggle.classes()).toContain("fa-caret-down");
        expect(wrapper.get("[data-test=document-folder-icon-open]").classes()).toContain(
            "fa-folder-open"
        );

        expect(wrapper.vm.is_loading).toBeFalsy();
        expect(wrapper.vm.have_children_been_loaded).toBeTruthy();
    });

    it(`Given folder is collapsed
        When we display the folder
        Then we don't load anything and render directly it`, async () => {
        item.is_expanded = false;
        const wrapper = shallowMount(FolderCellTitle, component_options);

        expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
        await Vue.nextTick();
        const toggle = wrapper.get("[data-test=toggle]");
        expect(toggle.classes()).toContain("fa-caret-right");

        expect(wrapper.vm.is_loading).toBeFalsy();
        expect(store.dispatch).not.toHaveBeenCalledWith("getSubfolderContent", expect.anything());
    });

    describe("toggle expanded folders", () => {
        let router, item, component_options, store_options;
        beforeEach(() => {
            router = new VueRouter({
                routes: [
                    {
                        path: "/folder/42",
                        name: "folder",
                    },
                ],
            });

            item = {
                id: 42,
                title: "my folder name",
            };

            store_options = {
                state: {
                    files_uploads_list: [],
                },
            };

            store = createStoreMock(store_options);

            component_options = {
                localVue,
                router,
                propsData: {
                    item,
                },
                mocks: { $store: store },
            };
        });

        it(`Given folder is expanded
        When we close it and reopened it
        Then its should open it and load its children, the user preferences is stored in backend`, async () => {
            item.is_expanded = true;
            const wrapper = shallowMount(FolderCellTitle, component_options);
            wrapper.get("[data-test=toggle]").trigger("click");

            expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
            await Vue.nextTick();
            const toggle = wrapper.get("[data-test=toggle]");
            toggle.trigger("click");
            await Vue.nextTick();
            expect(toggle.classes()).toContain("fa-caret-down");

            expect(wrapper.vm.is_loading).toBeFalsy();
            expect(wrapper.vm.have_children_been_loaded).toBeTruthy();

            expect(store.commit).toHaveBeenCalledWith("unfoldFolderContent", item.id);
            expect(store.commit).toHaveBeenCalledWith("toggleCollapsedFolderHasUploadingContent", [
                item,
                false,
            ]);
            expect(store.dispatch).toHaveBeenCalledWith("setUserPreferenciesForFolder", [
                item.id,
                false,
            ]);
        });

        it(`Given folder is expanded
        When we toggle it
        Then it should close it and store the new user preferences in backend`, async () => {
            item.is_expanded = true;
            const wrapper = shallowMount(FolderCellTitle, component_options);
            wrapper.get("[data-test=toggle]").trigger("click");

            await Vue.nextTick();
            expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-right");
            expect(store.commit).toHaveBeenCalledWith("foldFolderContent", item.id);
            expect(store.commit).toHaveBeenCalledWith("toggleCollapsedFolderHasUploadingContent", [
                item,
                undefined,
            ]);
            expect(store.dispatch).toHaveBeenCalledWith("setUserPreferenciesForFolder", [
                item.id,
                true,
            ]);
        });

        it(`Given folder is closed and given its children have been loaded
        When we toogle it multiples times
        Then it save baby bears and load its content only once`, async () => {
            item.is_expanded = false;
            const wrapper = shallowMount(FolderCellTitle, component_options);
            wrapper.setData({ have_children_been_loaded: true });
            wrapper.get("[data-test=toggle]").trigger("click");

            await Vue.nextTick();
            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-down");
            expect(store.dispatch).not.toHaveBeenCalledWith(
                "getSubfolderContent",
                expect.anything()
            );
        });
    });

    describe("toggle folder with uploading content", () => {
        let router, item, component_options, store_options;
        beforeEach(() => {
            router = new VueRouter({
                routes: [
                    {
                        path: "/folder/42",
                        name: "folder",
                    },
                ],
            });

            item = {
                id: 42,
                title: "my folder name",
                is_expanded: true,
            };

            store_options = {
                state: {
                    files_uploads_list: [{ parent_id: 42, progress: 34 }],
                },
            };

            store = createStoreMock(store_options);

            component_options = {
                localVue,
                router,
                propsData: {
                    item,
                },
                mocks: { $store: store },
            };
        });

        it(`Given folder is expanded and given folder has uploading content
        When we toggle it
        Then we should store that folder is collapsed with uploading content`, async () => {
            const wrapper = shallowMount(FolderCellTitle, component_options);
            wrapper.get("[data-test=toggle]").trigger("click");

            await Vue.nextTick();
            expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-right");
            expect(store.commit).toHaveBeenCalledWith("foldFolderContent", item.id);
            expect(store.commit).toHaveBeenCalledWith("toggleCollapsedFolderHasUploadingContent", [
                item,
                { parent_id: 42, progress: 34 },
            ]);
            expect(store.dispatch).toHaveBeenCalledWith("setUserPreferenciesForFolder", [
                item.id,
                true,
            ]);
        });
    });

    describe("go to folder", () => {
        let router, item, component_options, store_options, abortCurrentUploads;
        beforeEach(() => {
            router = new VueRouter({
                routes: [
                    {
                        path: "/folder/42",
                        name: "folder",
                    },
                ],
            });

            item = {
                id: 42,
                title: "my folder name",
                is_expanded: true,
            };

            store_options = {
                state: {},
            };

            store = createStoreMock(store_options);

            component_options = {
                localVue,
                router,
                propsData: {
                    item,
                },
                mocks: { $store: store },
            };

            abortCurrentUploads = jest.spyOn(abort_current_uploads, "abortCurrentUploads");
        });

        it(`Given there is an on going upload and user refuse confirmation
            Then user won't be redirected`, () => {
            store.getters.is_uploading = true;
            abortCurrentUploads.mockReturnValue(false);
            const wrapper = shallowMount(FolderCellTitle, component_options);
            wrapper.get("[data-test=document-go-to-folder-link]").trigger("click");

            expect(store.commit).not.toHaveBeenCalledWith("appendFolderToAscendantHierarchy");
        });

        it(`Given there no upload
            Then the user is redirect to parent folder`, () => {
            store.getters.is_uploading = false;
            abortCurrentUploads.mockReturnValue(false);
            const wrapper = shallowMount(FolderCellTitle, component_options);
            wrapper.get("[data-test=document-go-to-folder-link]").trigger("click");

            expect(store.commit).toHaveBeenCalledWith("appendFolderToAscendantHierarchy", item);
        });
    });
});
