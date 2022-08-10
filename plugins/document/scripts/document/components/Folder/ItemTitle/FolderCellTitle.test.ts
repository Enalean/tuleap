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
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FolderCellTitle from "./FolderCellTitle.vue";
import localVue from "../../../helpers/local-vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as abort_current_uploads from "../../../helpers/abort-current-uploads";
import type { Folder, RootState } from "../../../type";
import type { Location, Route } from "vue-router/types/router";
import * as route from "../../../helpers/use-router";

describe("FolderCellTitle", () => {
    let store = {
        dispatch: jest.fn(),
        commit: jest.fn(),
    };

    const item = { id: 10 } as Folder;

    function getWrapper(is_expanded: boolean, is_uploading: boolean): Wrapper<FolderCellTitle> {
        const router = new VueRouter();
        jest.spyOn(router, "resolve").mockImplementation(() => ({
            location: {} as Location,
            route: {
                path: "/folder/42",
                name: "folder",
            } as Route,
            href: "/patch/to/embedded",
            normalizedTo: {} as Location,
            resolved: {} as Route,
        }));
        jest.spyOn(router, "push").mockImplementation(() => ({
            location: {} as Location,
            route: {
                path: "/folder/42",
                name: "folder",
            } as Route,
            href: "/patch/to/embedded",
            normalizedTo: {} as Location,
            resolved: {} as Route,
        }));
        const mocked_router = jest.spyOn(route, "useRouter");
        mocked_router.mockReturnValue(router);
        store = createStoreMock({
            state: {
                current_folder: {
                    id: 1,
                    title: "My current folder",
                } as Folder,
                files_uploads_list: [{ parent_id: 42, progress: 34 }],
            } as RootState,
            getters: {
                is_uploading,
            },
        });

        item.is_expanded = is_expanded;

        return shallowMount(FolderCellTitle, {
            localVue,
            propsData: { item },
            mocks: {
                localVue,
                $store: store,
            },
        });
    }

    it(`Given folder is open
        When we display the folder
        Then we should dynamically load its content`, async () => {
        const wrapper = await getWrapper(true, false);

        await Vue.nextTick();

        expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
        expect(store.commit).toHaveBeenCalledWith("unfoldFolderContent", item.id);

        const toggle = wrapper.get("[data-test=toggle]");
        expect(toggle.classes()).toContain("fa-caret-down");
        expect(wrapper.get("[data-test=document-folder-icon-open]").classes()).toContain(
            "fa-folder-open"
        );
    });

    it(`Given folder is collapsed
        When we display the folder
        Then we don't load anything and render directly it`, async () => {
        const wrapper = getWrapper(false, false);

        await Vue.nextTick();

        expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
        const toggle = wrapper.get("[data-test=toggle]");
        expect(toggle.classes()).toContain("fa-caret-right");

        expect(store.dispatch).not.toHaveBeenCalledWith("getSubfolderContent", expect.anything());
    });

    describe("toggle expanded folders", () => {
        it(`Given folder is expanded
        When we close it and reopened it
        Then its should open it and load its children, the user preferences is stored in backend`, async () => {
            const wrapper = getWrapper(true, false);
            wrapper.get("[data-test=toggle]").trigger("click");

            expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
            await Vue.nextTick();
            const toggle = wrapper.get("[data-test=toggle]");
            toggle.trigger("click");
            await Vue.nextTick();
            expect(toggle.classes()).toContain("fa-caret-down");

            expect(store.commit).toHaveBeenCalledWith("unfoldFolderContent", item.id);
            expect(store.commit).toHaveBeenCalledWith(
                "toggleCollapsedFolderHasUploadingContent",
                expect.anything()
            );
            expect(store.dispatch).toHaveBeenCalledWith(
                "preferencies/setUserPreferenciesForFolder",
                { folder_id: item.id, should_be_closed: false }
            );
        });

        it(`Given folder is expanded
        When we toggle it
        Then it should close it and store the new user preferences in backend`, async () => {
            const wrapper = getWrapper(true, false);
            wrapper.get("[data-test=toggle]").trigger("click");

            await Vue.nextTick();
            expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-right");
            expect(store.commit).toHaveBeenCalledWith("foldFolderContent", item.id);
            expect(store.commit).toHaveBeenCalledWith(
                "toggleCollapsedFolderHasUploadingContent",
                expect.anything()
            );
            expect(store.dispatch).toHaveBeenCalledWith(
                "preferencies/setUserPreferenciesForFolder",
                { folder_id: item.id, should_be_closed: true }
            );
        });

        it(`Given folder is closed and given its children have been loaded
        When we toggle it multiples times
        Then it save baby bears and load its content only once`, async () => {
            const wrapper = getWrapper(false, false);
            wrapper.get("[data-test=toggle]").trigger("click");
            await Vue.nextTick();

            wrapper.get("[data-test=toggle]").trigger("click");
            await Vue.nextTick();

            wrapper.get("[data-test=toggle]").trigger("click");
            await Vue.nextTick();

            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-down");

            // 1 call for getSubfolderContent and 3 calls for setUserPreferenciesForFolder
            expect(store.dispatch.mock.calls).toHaveLength(4);
        });
    });

    describe("toggle folder with uploading content", () => {
        it(`Given folder is expanded and given folder has uploading content
        When we toggle it
        Then we should store that folder is collapsed with uploading content`, async () => {
            const wrapper = await getWrapper(true, false);
            wrapper.get("[data-test=toggle]").trigger("click");

            await Vue.nextTick();
            expect(store.commit).toHaveBeenCalledWith("initializeFolderProperties", item);
            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-right");
            expect(store.commit).toHaveBeenCalledWith("foldFolderContent", item.id);
            expect(store.commit).toHaveBeenCalledWith(
                "toggleCollapsedFolderHasUploadingContent",
                expect.anything()
            );
            expect(store.dispatch).toHaveBeenCalledWith(
                "preferencies/setUserPreferenciesForFolder",
                { folder_id: item.id, should_be_closed: true }
            );
        });
    });

    describe("go to folder", () => {
        let abortCurrentUploads: jest.SpyInstance;
        beforeEach(() => {
            abortCurrentUploads = jest.spyOn(abort_current_uploads, "abortCurrentUploads");
        });

        it(`Given there is an on going upload and user refuse confirmation
            Then user won't be redirected`, () => {
            abortCurrentUploads.mockReturnValue(false);
            const wrapper = getWrapper(true, true);

            wrapper.get("[data-test=document-go-to-folder-link]").trigger("click");

            expect(store.commit).not.toHaveBeenCalledWith("appendFolderToAscendantHierarchy");
        });

        it(`Given there no upload
            Then the user is redirect to parent folder`, () => {
            abortCurrentUploads.mockReturnValue(false);
            const wrapper = getWrapper(true, false);
            wrapper.get("[data-test=document-go-to-folder-link]").trigger("click");

            expect(store.commit).toHaveBeenCalledWith(
                "appendFolderToAscendantHierarchy",
                expect.anything()
            );
        });
    });
});
