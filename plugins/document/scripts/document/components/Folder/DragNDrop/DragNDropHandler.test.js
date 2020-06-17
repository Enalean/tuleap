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
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { TYPE_FILE, TYPE_FOLDER } from "../../../constants.js";

import Handler from "./DragNDropHandler.vue";
import EventBus from "../../../helpers/event-bus.js";

describe("DragNDropHandler", () => {
    let main, store, component_options, store_options, drop_event;

    const file1 = new File([new Blob(["Some text in a file"])], "file.txt", {
        type: "plain/text",
        endings: "native",
    });

    const file2 = new File([new Blob(["Some text in a file"])], "file2.txt", {
        type: "plain/text",
        endings: "native",
    });

    const file3 = new File([new Blob(["Some text in a file"])], "file3.txt", {
        type: "plain/text",
        endings: "native",
    });

    function getWrapper(is_changelog_proposed_after_dnd = false) {
        store_options = {
            state: {
                folder_content: [],
                max_files_dragndrop: 10,
                max_size_upload: 1000000000,
                is_changelog_proposed_after_dnd,
                current_folder: {
                    id: 999,
                    title: "workdir",
                    type: TYPE_FOLDER,
                    user_can_write: true,
                },
                user_id: 666,
            },
            getters: {
                user_can_dragndrop: true,
                current_folder_title: "workdir",
            },
        };
        store = createStoreMock(store_options);

        component_options = {
            mocks: {
                $store: store,
            },
            localVue,
        };

        const wrapper = shallowMount(Handler, component_options);
        jest.spyOn(wrapper.vm, "isDragNDropingOnAModal").mockReturnValue(false);

        return wrapper;
    }

    beforeEach(() => {
        drop_event = {
            stopPropagation: () => {},
            preventDefault: () => {},
            dataTransfer: {
                files: [],
            },
        };

        main = document.createElement("div");

        jest.spyOn(document, "querySelector").mockReturnValue(main);
    });

    describe("Errors handling", () => {
        describe("new file upload", () => {
            it("Shows an error modal if the number of files dropped exceeds the allowed size limit", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push(file1, file2, file3);

                store.state.max_files_dragndrop = 2;

                await wrapper.vm.ondrop(drop_event);

                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.MAX_FILES_ERROR);
                expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            });

            it("Shows an error modal if the file size exceeds the allowed size limit", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push(file1);

                store.state.max_size_upload = 0;

                await wrapper.vm.ondrop(drop_event);

                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.MAX_SIZE_ERROR);
                expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            });

            it("Shows an error modal if a file with the same name already exists in the current folder", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push(file1);

                store.state.folder_content.push({
                    id: 123,
                    parent_id: store.state.current_folder.id,
                    title: file1.name,
                    type: TYPE_FILE,
                });

                await wrapper.vm.ondrop(drop_event);

                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.ALREADY_EXISTS_ERROR);
                expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            });

            it("Shows an error modal if a file cannot be uploaded", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push(file1);

                store.dispatch.mockImplementation(() => {
                    throw new Error("it cannot");
                });

                await wrapper.vm.ondrop(drop_event);

                expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                    file1,
                    store.state.current_folder,
                    file1.name,
                    "",
                    true,
                ]);
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.CREATION_ERROR);
            });

            it("Shows an error if there is no file in the list", async () => {
                const wrapper = getWrapper();
                await wrapper.vm.ondrop(drop_event);

                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.DROPPED_ITEM_IS_NOT_A_FILE);
            });

            it("Shows an error if the item has the size of a default cluster size and no type, so it is probably a folder", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push({
                    type: "",
                    size: 4096,
                });
                await wrapper.vm.ondrop(drop_event);
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.DROPPED_ITEM_IS_NOT_A_FILE);
            });

            it("Does not show any error when the item is a valid file", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push({
                    type: "text/pdf",
                    size: 4096,
                });
                await wrapper.vm.ondrop(drop_event);
                expect(wrapper.vm.error_modal_shown).toBe(false);
            });
        });

        describe("New version upload", () => {
            it("Shows an error modal if a document is locked by someone else", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push(file1);

                const target_file = {
                    id: 123,
                    title: "file.txt",
                    type: TYPE_FILE,
                    user_can_write: true,
                    lock_info: {
                        locked_by: {
                            id: 753,
                            name: "some dude",
                            display_name: "Some Dude",
                            user_url: "https://example.com",
                        },
                    },
                    approval_table: null,
                };

                store.state.folder_content.push(target_file);
                wrapper.setData({ highlighted_item_id: target_file.id });

                await wrapper.vm.ondrop(drop_event);
                await wrapper.vm.$nextTick();

                expect(store.dispatch).not.toHaveBeenCalledWith("createNewFileVersion");
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.EDITION_LOCKED);
            });

            it("Shows an error modal if a document is requested to be approved", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push(file1);

                const target_file = {
                    id: 123,
                    title: "file.txt",
                    type: TYPE_FILE,
                    user_can_write: true,
                    lock_info: null,
                    approval_table: {
                        has_been_approved: false,
                        approval_state: "Not yet",
                        table_owner: {
                            display_name: "Some Dude",
                            user_url: "https://example.com",
                        },
                    },
                };

                store.state.folder_content.push(target_file);
                wrapper.setData({ highlighted_item_id: target_file.id });

                await wrapper.vm.ondrop(drop_event);
                await wrapper.vm.$nextTick();

                expect(store.dispatch).not.toHaveBeenCalledWith("createNewFileVersion");
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.DOCUMENT_NEEDS_APPROVAL);
            });

            it("Shows an error modal if the new version is too big", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push(file1);

                const target_file = {
                    id: 123,
                    title: "file.txt",
                    type: TYPE_FILE,
                    user_can_write: true,
                    lock_info: null,
                    approval_table: null,
                };

                store.state.max_size_upload = 0;
                store.state.folder_content.push(target_file);

                wrapper.setData({ highlighted_item_id: target_file.id });

                await wrapper.vm.ondrop(drop_event);

                expect(store.dispatch).not.toHaveBeenCalledWith("createNewFileVersion");
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.MAX_SIZE_ERROR);
            });

            it("Shows an error modal if the new version can't be created", async () => {
                const wrapper = getWrapper();
                drop_event.dataTransfer.files.push(file1);

                const target_file = {
                    id: 123,
                    title: "file.txt",
                    type: TYPE_FILE,
                    user_can_write: true,
                    lock_info: null,
                    approval_table: null,
                };

                store.state.folder_content.push(target_file);
                store.dispatch.mockImplementation(() => {
                    throw new Error("It cannot");
                });

                wrapper.setData({ highlighted_item_id: target_file.id });

                await wrapper.vm.ondrop(drop_event);

                expect(store.dispatch).not.toHaveBeenCalledWith("createNewFileVersion");
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.CREATION_ERROR);
            });
        });
    });

    describe("Drop multiple files", () => {
        it("Should upload each files dropped in the current folder to the current folder", async () => {
            const wrapper = getWrapper();
            drop_event.dataTransfer.files.push(file1, file2);

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file1,
                store.state.current_folder,
                file1.name,
                "",
                true,
            ]);
            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file2,
                store.state.current_folder,
                file2.name,
                "",
                true,
            ]);
        });

        it("Should upload each files dropped in the current subfolder to the current subfolder", async () => {
            const wrapper = getWrapper();
            drop_event.dataTransfer.files.push(file1, file2);

            const target_subfolder = {
                id: 456,
                title: "my subfolder",
                type: TYPE_FOLDER,
                user_can_write: true,
                is_expanded: true,
            };

            store.state.folder_content.push(target_subfolder);

            wrapper.setData({ highlighted_item_id: target_subfolder.id });

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file1,
                target_subfolder,
                file1.name,
                "",
                true,
            ]);
            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file2,
                target_subfolder,
                file2.name,
                "",
                true,
            ]);
        });
    });

    describe("Drop one file", () => {
        it("Should upload a new file if it is dropped in a folder", async () => {
            const wrapper = getWrapper();
            drop_event.dataTransfer.files.push(file1);

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file1,
                store.state.current_folder,
                file1.name,
                "",
                true,
            ]);
        });

        it("Should upload a new version if it is dropped on a file", async () => {
            const wrapper = getWrapper();
            drop_event.dataTransfer.files.push(file1);

            const target_file = {
                id: 123,
                title: "file.txt",
                type: TYPE_FILE,
                user_can_write: true,
                lock_info: {
                    locked_by: {
                        id: store.state.user_id,
                        name: "current_user",
                    },
                },
                approval_table: {
                    has_been_approved: true,
                },
            };

            store.state.folder_content.push(target_file);
            wrapper.setData({ highlighted_item_id: target_file.id });

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            expect(store.dispatch).toHaveBeenCalledWith("createNewFileVersion", [
                target_file,
                file1,
            ]);
        });

        it("Should open the changelog modal when user uploads a new version and option is enabled", async () => {
            const wrapper = getWrapper(true);
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            drop_event.dataTransfer.files.push(file1);

            const target_file = {
                id: 123,
                title: "file.txt",
                type: TYPE_FILE,
                user_can_write: true,
                lock_info: {
                    locked_by: {
                        id: store.state.user_id,
                        name: "current_user",
                    },
                },
                approval_table: {
                    has_been_approved: true,
                },
            };

            store.state.folder_content.push(target_file);
            wrapper.setData({ highlighted_item_id: target_file.id });

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            expect(store.dispatch).not.toHaveBeenCalledWith("createNewFileVersion", [
                target_file,
                file1,
            ]);

            expect(event_bus_emit).toHaveBeenCalledWith("show-changelog-modal", {
                detail: {
                    updated_file: target_file,
                    dropped_file: file1,
                },
            });
        });
    });

    describe("It shouldn't upload", () => {
        it("If the user hasn't the right to create a new file in the target folder", async () => {
            const wrapper = getWrapper();
            drop_event.dataTransfer.files.push(file1);

            const target_subfolder = {
                id: 456,
                title: "my subfolder",
                type: TYPE_FOLDER,
                user_can_write: false,
                is_expanded: true,
            };

            store.state.folder_content.push(target_subfolder);

            wrapper.setData({ highlighted_item_id: target_subfolder.id });

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile", expect.any(Array));
        });

        it("If the user hasn't the right to create a new version of the target file", async () => {
            const wrapper = getWrapper();
            drop_event.dataTransfer.files.push(file1);

            const target_file = {
                id: 123,
                title: "file.txt",
                type: TYPE_FILE,
                user_can_write: false,
                lock_info: null,
                approval_table: null,
            };

            store.state.folder_content.push(target_file);

            wrapper.setData({ highlighted_item_id: target_file.id });

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith(
                "createNewFileVersion",
                expect.any(Array)
            );
        });

        it("If the user drops his file in a modal", async () => {
            const wrapper = getWrapper();
            drop_event.dataTransfer.files.push(file1);

            wrapper.vm.isDragNDropingOnAModal.mockReturnValue(true);

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile", expect.any(Array));
        });

        it("If the user hasn't the right to write in the current folder", async () => {
            const wrapper = getWrapper();
            drop_event.dataTransfer.files.push(file1);

            store.getters.user_can_dragndrop = false;

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile", expect.any(Array));
        });
    });
});
