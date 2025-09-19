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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FolderCellTitle from "./FolderCellTitle.vue";
import * as abort_current_uploads from "../../../helpers/abort-current-uploads";
import type { Folder, RootState } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import * as router from "../../../helpers/use-router";
import type { Router } from "vue-router";
import { PROJECT, USER_ID } from "../../../configuration-keys";
import { ProjectBuilder } from "../../../../tests/builders/ProjectBuilder";

vi.useFakeTimers();

describe("FolderCellTitle", () => {
    let initialize_folder_properties: vi.Mock;
    let unfold_folder_content: vi.Mock;
    let get_sub_folder_content: vi.Mock;
    let toggle_collapse_folder_has_uploading_content: vi.Mock;
    let set_user_preferences: vi.Mock;
    let fold_folder_content: vi.Mock;
    let append_folder_to_hierarchy: vi.Mock;
    const item = { id: 10 } as Folder;
    beforeEach(() => {
        const mock_resolve = vi.fn().mockReturnValue({ href: "/my-url" });
        vi.spyOn(router, "useRouter").mockImplementation(() => {
            return { resolve: mock_resolve, push: vi.fn() } as unknown as Router;
        });
        initialize_folder_properties = vi.fn();
        unfold_folder_content = vi.fn();
        get_sub_folder_content = vi.fn();
        toggle_collapse_folder_has_uploading_content = vi.fn();
        set_user_preferences = vi.fn();
        fold_folder_content = vi.fn();
        append_folder_to_hierarchy = vi.fn();
    });

    function getWrapper(
        is_expanded: boolean,
        is_uploading: boolean,
    ): VueWrapper<InstanceType<typeof FolderCellTitle>> {
        get_sub_folder_content.mockReset();

        item.is_expanded = is_expanded;

        return shallowMount(FolderCellTitle, {
            props: { item },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        preferencies: {
                            actions: { setUserPreferenciesForFolder: set_user_preferences },
                            namespaced: true,
                        },
                    },
                    state: {
                        current_folder: {
                            id: 1,
                            title: "My current folder",
                        } as Folder,
                        files_uploads_list: [{ parent_id: 42, progress: 34 }],
                    } as RootState,
                    getters: {
                        is_uploading: () => is_uploading,
                    },
                    mutations: {
                        initializeFolderProperties: initialize_folder_properties,
                        unfoldFolderContent: unfold_folder_content,
                        toggleCollapsedFolderHasUploadingContent:
                            toggle_collapse_folder_has_uploading_content,
                        foldFolderContent: fold_folder_content,
                        appendFolderToAscendantHierarchy: append_folder_to_hierarchy,
                    },
                    actions: {
                        getSubfolderContent: get_sub_folder_content,
                    },
                }),
                stubs: ["router-link", "router-view"],
                provide: {
                    [USER_ID.valueOf()]: 254,
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
        });
    }

    it(`Given folder is open
        When we display the folder
        Then we should dynamically load its content`, async () => {
        const wrapper = getWrapper(true, false);

        await vi.runOnlyPendingTimersAsync();

        expect(initialize_folder_properties).toHaveBeenCalled();
        expect(unfold_folder_content).toHaveBeenCalled();

        const toggle = wrapper.get("[data-test=toggle]");
        expect(toggle.classes()).toContain("fa-caret-down");
        expect(wrapper.get("[data-test=document-folder-icon-open]").classes()).toContain(
            "fa-folder-open",
        );
    });

    it(`Given folder is collapsed
        When we display the folder
        Then we don't load anything and render directly it`, async () => {
        const wrapper = getWrapper(false, false);

        await vi.runOnlyPendingTimersAsync();

        expect(initialize_folder_properties).toHaveBeenCalled();
        const toggle = wrapper.get("[data-test=toggle]");
        expect(toggle.classes()).toContain("fa-caret-right");

        expect(get_sub_folder_content).not.toHaveBeenCalled();
    });

    describe("toggle expanded folders", () => {
        it(`Given folder is expanded
        When we close it and reopened it
        Then its should open it and load its children, the user preferences is stored in backend`, async () => {
            const wrapper = getWrapper(true, false);
            wrapper.get("[data-test=toggle]").trigger("click");

            expect(initialize_folder_properties).toHaveBeenCalled();
            await vi.runOnlyPendingTimersAsync();
            const toggle = wrapper.get("[data-test=toggle]");
            await toggle.trigger("click");
            expect(toggle.classes()).toContain("fa-caret-down");

            expect(unfold_folder_content).toHaveBeenCalled();
            expect(toggle_collapse_folder_has_uploading_content).toHaveBeenCalled();
            expect(set_user_preferences).toHaveBeenCalledWith(expect.anything(), {
                folder_id: item.id,
                should_be_closed: false,
                user_id: 254,
                project_id: 101,
            });
        });

        it(`Given folder is expanded
        When we toggle it
        Then it should close it and store the new user preferences in backend`, async () => {
            const wrapper = getWrapper(true, false);
            await wrapper.get("[data-test=toggle]").trigger("click");

            expect(initialize_folder_properties).toHaveBeenCalled();
            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-right");
            expect(fold_folder_content).toHaveBeenCalled();
            expect(toggle_collapse_folder_has_uploading_content).toHaveBeenCalled();
            expect(set_user_preferences).toHaveBeenCalledWith(expect.anything(), {
                folder_id: item.id,
                should_be_closed: true,
                user_id: 254,
                project_id: 101,
            });
        });

        it(`Given folder is closed and given its children have been loaded
        When we toggle it multiples times
        Then it save baby bears and load its content only once`, async () => {
            const wrapper = getWrapper(false, false);
            await wrapper.get("[data-test=toggle]").trigger("click");

            await wrapper.get("[data-test=toggle]").trigger("click");

            await wrapper.get("[data-test=toggle]").trigger("click");

            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-down");

            expect(get_sub_folder_content.call).toHaveLength(1);
        });
    });

    describe("toggle folder with uploading content", () => {
        it(`Given folder is expanded and given folder has uploading content
        When we toggle it
        Then we should store that folder is collapsed with uploading content`, async () => {
            const wrapper = getWrapper(true, false);
            await wrapper.get("[data-test=toggle]").trigger("click");

            expect(initialize_folder_properties).toHaveBeenCalled();
            const toggle = wrapper.get("[data-test=toggle]");
            expect(toggle.classes()).toContain("fa-caret-right");
            expect(fold_folder_content).toHaveBeenCalled();
            expect(toggle_collapse_folder_has_uploading_content).toHaveBeenCalled();
            expect(set_user_preferences).toHaveBeenCalledWith(expect.anything(), {
                folder_id: item.id,
                should_be_closed: true,
                user_id: 254,
                project_id: 101,
            });
        });
    });

    describe("go to folder", () => {
        let abortCurrentUploads: vi.SpyInstance;
        beforeEach(() => {
            abortCurrentUploads = vi.spyOn(abort_current_uploads, "abortCurrentUploads");
        });

        it(`Given there is an on going upload and user refuse confirmation
            Then user won't be redirected`, () => {
            abortCurrentUploads.mockReturnValue(false);
            const wrapper = getWrapper(true, true);

            wrapper.get("[data-test=document-go-to-folder-link]").trigger("click");

            expect(append_folder_to_hierarchy).not.toHaveBeenCalled();
        });

        it(`Given there no upload
            Then the user is redirect to parent folder`, () => {
            abortCurrentUploads.mockReturnValue(false);
            const wrapper = getWrapper(true, false);
            wrapper.get("[data-test=document-go-to-folder-link]").trigger("click");

            expect(append_folder_to_hierarchy).toHaveBeenCalled();
        });
    });
});
