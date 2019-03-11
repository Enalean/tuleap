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
import { createStoreMock } from "../../../helpers/store-wrapper.spec-helper.js";
import { TYPE_FILE, TYPE_FOLDER } from "../../../constants.js";

import Handler from "./DragNDropHandler.vue";

describe("DragNDropHandler", () => {
    let main, store, wrapper, component_options, store_options, drop_event;

    const file1 = { name: "file.txt", type: "text", size: 1000000 };
    const file2 = { name: "file2.txt", type: "text", size: 1000000 };
    const file3 = { name: "file3.txt", type: "text", size: 1000000 };

    beforeEach(() => {
        store_options = {
            state: {
                folder_content: [],
                max_files_dragndrop: 10,
                max_size_upload: 1000000000,
                current_folder: {
                    id: 999,
                    title: "workdir",
                    type: TYPE_FOLDER,
                    user_can_write: true
                },
                user_id: 666
            },
            getters: {
                user_can_dragndrop: true,
                current_folder_title: "workdir"
            }
        };
        store = createStoreMock(store_options);

        component_options = {
            mocks: {
                $store: store
            },
            localVue
        };

        drop_event = {
            stopPropagation: () => {},
            preventDefault: () => {},
            dataTransfer: {
                files: []
            }
        };

        main = document.createElement("div");

        spyOn(document, "querySelector").and.returnValue(main);

        wrapper = shallowMount(Handler, component_options);

        spyOn(wrapper.vm, "isDragNDropingOnAModal").and.returnValue(false);
    });

    describe("Errors handling", () => {
        describe("new file upload", () => {
            it("Shows an error modal if the number of files dropped exceeds the allowed size limit", () => {
                drop_event.dataTransfer.files.push(file1, file2, file3);

                store.state.max_files_dragndrop = 2;

                wrapper.vm.ondrop(drop_event);

                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.MAX_FILES_ERROR);
                expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            });

            it("Shows an error modal if the file size exceeds the allowed size limit", () => {
                drop_event.dataTransfer.files.push(file1);

                store.state.max_size_upload = 1000;

                wrapper.vm.ondrop(drop_event);

                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.MAX_SIZE_ERROR);
                expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            });

            it("Shows an error modal if a file with the same name already exists in the current folder", () => {
                drop_event.dataTransfer.files.push(file1);

                store.state.folder_content.push({
                    id: 123,
                    parent_id: store.state.current_folder.id,
                    title: file1.name,
                    type: TYPE_FILE
                });

                wrapper.vm.ondrop(drop_event);

                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.ALREADY_EXISTS_ERROR);
                expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            });

            it("Shows an error modal if a file cannot be uploaded", () => {
                drop_event.dataTransfer.files.push(file1);

                store.dispatch.and.throwError("it cannot");

                wrapper.vm.ondrop(drop_event);

                expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                    file1,
                    store.state.current_folder,
                    file1.name,
                    "",
                    true
                ]);
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.CREATION_ERROR);
            });
        });

        describe("New version upload", () => {
            it("Shows an error modal if a document is locked by someone else", () => {
                drop_event.dataTransfer.files.push(file1);

                const target_file = {
                    id: 123,
                    title: "file.txt",
                    type: TYPE_FILE,
                    user_can_write: true,
                    lock_info: {
                        locked_by: {
                            id: 753,
                            name: "some dude"
                        }
                    },
                    approval_table: null
                };

                store.state.folder_content.push(target_file);
                wrapper.setData({ highlighted_item_id: target_file.id });

                wrapper.vm.ondrop(drop_event);

                expect(store.dispatch).not.toHaveBeenCalledWith("updateFile");
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.EDITION_LOCKED);
            });

            it("Shows an error modal if a document is requested to be approved", () => {
                drop_event.dataTransfer.files.push(file1);

                const target_file = {
                    id: 123,
                    title: "file.txt",
                    type: TYPE_FILE,
                    user_can_write: true,
                    lock_info: null,
                    approval_table: {
                        has_been_approved: false
                    }
                };

                store.state.folder_content.push(target_file);
                wrapper.setData({ highlighted_item_id: target_file.id });

                wrapper.vm.ondrop(drop_event);

                expect(store.dispatch).not.toHaveBeenCalledWith("updateFile");
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.DOCUMENT_NEEDS_APPROVAL);
            });

            it("Shows an error modal if the new version is too big", () => {
                drop_event.dataTransfer.files.push(file1);

                const target_file = {
                    id: 123,
                    title: "file.txt",
                    type: TYPE_FILE,
                    user_can_write: true,
                    lock_info: null,
                    approval_table: null
                };

                store.state.max_size_upload = 1000;
                store.state.folder_content.push(target_file);

                wrapper.setData({ highlighted_item_id: target_file.id });

                wrapper.vm.ondrop(drop_event);

                expect(store.dispatch).not.toHaveBeenCalledWith("updateFile");
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.MAX_SIZE_ERROR);
            });

            it("Shows an error modal if the new version can't be created", () => {
                drop_event.dataTransfer.files.push(file1);

                const target_file = {
                    id: 123,
                    title: "file.txt",
                    type: TYPE_FILE,
                    user_can_write: true,
                    lock_info: null,
                    approval_table: null
                };

                store.state.folder_content.push(target_file);
                store.dispatch.and.throwError("It cannot");

                wrapper.setData({ highlighted_item_id: target_file.id });

                wrapper.vm.ondrop(drop_event);

                expect(store.dispatch).not.toHaveBeenCalledWith("updateFile");
                expect(wrapper.vm.error_modal_shown).toEqual(wrapper.vm.CREATION_ERROR);
            });
        });
    });

    describe("Drop multiple files", () => {
        it("Should upload each files dropped in the current folder to the current folder", async () => {
            drop_event.dataTransfer.files.push(file1, file2);

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file1,
                store.state.current_folder,
                file1.name,
                "",
                true
            ]);
            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file2,
                store.state.current_folder,
                file2.name,
                "",
                true
            ]);
        });

        it("Should upload each files dropped in the current subfolder to the current subfolder", async () => {
            drop_event.dataTransfer.files.push(file1, file2);

            const target_subfolder = {
                id: 456,
                title: "my subfolder",
                type: TYPE_FOLDER,
                user_can_write: true,
                is_expanded: true
            };

            store.state.folder_content.push(target_subfolder);

            wrapper.setData({ highlighted_item_id: target_subfolder.id });

            await wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file1,
                target_subfolder,
                file1.name,
                "",
                true
            ]);
            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file2,
                target_subfolder,
                file2.name,
                "",
                true
            ]);
        });
    });

    describe("Drop one file", () => {
        it("Should upload a new file if it is dropped in a folder", () => {
            drop_event.dataTransfer.files.push(file1);

            wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).toHaveBeenCalledWith("addNewUploadFile", [
                file1,
                store.state.current_folder,
                file1.name,
                "",
                true
            ]);
        });

        it("Should upload a new version if it is dropped on a file", () => {
            drop_event.dataTransfer.files.push(file1);

            const target_file = {
                id: 123,
                title: "file.txt",
                type: TYPE_FILE,
                user_can_write: true,
                lock_info: {
                    locked_by: {
                        id: store.state.user_id,
                        name: "current_user"
                    }
                },
                approval_table: {
                    has_been_approved: true
                }
            };

            store.state.folder_content.push(target_file);
            wrapper.setData({ highlighted_item_id: target_file.id });

            wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
            expect(store.dispatch).toHaveBeenCalledWith("updateFile", [target_file, file1]);
        });
    });

    describe("It shouldn't upload", () => {
        it("If the user hasn't the right to create a new file in the target folder", () => {
            drop_event.dataTransfer.files.push(file1);

            const target_subfolder = {
                id: 456,
                title: "my subfolder",
                type: TYPE_FOLDER,
                user_can_write: false,
                is_expanded: true
            };

            store.state.folder_content.push(target_subfolder);

            wrapper.setData({ highlighted_item_id: target_subfolder.id });

            wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
        });

        it("If the user hasn't the right to create a new version of the target file", () => {
            drop_event.dataTransfer.files.push(file1);

            const target_file = {
                id: 123,
                title: "file.txt",
                type: TYPE_FILE,
                user_can_write: false,
                lock_info: null,
                approval_table: null
            };

            store.state.folder_content.push(target_file);

            wrapper.setData({ highlighted_item_id: target_file.id });

            wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("updateFile");
        });

        it("If the dropped item isn't a file", () => {
            drop_event.dataTransfer.files.push("some text I've just selected somewhere");

            wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
        });

        it("If the user drops his file in a modal", () => {
            drop_event.dataTransfer.files.push(file1);

            wrapper.vm.isDragNDropingOnAModal.and.returnValue(true);

            wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
        });

        it("If the user hasn't the right to write in the current folder", () => {
            drop_event.dataTransfer.files.push(file1);

            store.getters.user_can_dragndrop = false;

            wrapper.vm.ondrop(drop_event);

            expect(store.dispatch).not.toHaveBeenCalledWith("addNewUploadFile");
        });
    });
});
